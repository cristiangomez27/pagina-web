<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/web_helpers.php';
header('Content-Type: application/json; charset=utf-8');
function out(array $d){ echo json_encode($d, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit; }
$pdo = function_exists('sw_web_db_pdo') ? sw_web_db_pdo() : null;
if(!$pdo) out(['ok'=>false,'mensaje'=>'BD no disponible.']);
$in=json_decode((string)file_get_contents('php://input'),true); if(!is_array($in)) out(['ok'=>false,'mensaje'=>'Payload inválido.']);
$client=sw_client_current();
$items=$in['carrito']??[]; if(!is_array($items)||!$items) out(['ok'=>false,'mensaje'=>'Carrito vacío.']);
$cuponCode=strtoupper(trim((string)($in['cupon_codigo']??'')));
try{
 $pdo->beginTransaction();
 $normalized=[]; $subtotal=0.0;
 foreach($items as $it){ $pid=(int)($it['id']??0); $qty=max(1,(int)($it['qty']??1)); if($pid<=0) continue; $st=$pdo->prepare('SELECT id,nombre,slug,sku,precio,precio_oferta,activo FROM web_productos WHERE id=:id AND activo=1 LIMIT 1'); $st->execute([':id'=>$pid]); $p=$st->fetch(PDO::FETCH_ASSOC); if(!$p) continue; $price=(float)($p['precio_oferta']>0?$p['precio_oferta']:$p['precio']); $line=$price*$qty; $subtotal+=$line; $normalized[]=['producto_id'=>$pid,'nombre'=>$p['nombre'],'sku'=>$p['sku']??'','qty'=>$qty,'price'=>$price,'subtotal'=>$line,'size'=>(string)($it['size']??''),'color'=>(string)($it['color']??'')]; }
 if(!$normalized){ $pdo->rollBack(); out(['ok'=>false,'mensaje'=>'No hay productos válidos.']); }
 $discount=0.0; $cuponId=null;
 if($cuponCode!==''){ $st=$pdo->prepare('SELECT * FROM web_cupones WHERE codigo=:c AND activo=1 LIMIT 1'); $st->execute([':c'=>$cuponCode]); $c=$st->fetch(PDO::FETCH_ASSOC); if($c){ $okDate=(empty($c['fecha_inicio'])||strtotime($c['fecha_inicio'])<=time())&&(empty($c['fecha_fin'])||strtotime($c['fecha_fin'])>=time()); $okUses=$c['usos_maximos']===null||((int)$c['usos_actuales']<(int)$c['usos_maximos']); if($okDate&&$okUses&&$subtotal>=(float)$c['monto_minimo']){ $discount=$c['tipo_descuento']==='porcentaje'?($subtotal*((float)$c['valor']/100)):(float)$c['valor']; $discount=max(0,min($subtotal,$discount)); $cuponId=(int)$c['id']; } }}
 $total=max(0,$subtotal-$discount);
 $folio='WEB-'.date('YmdHis').'-'.strtoupper(bin2hex(random_bytes(2)));
 $clienteNombre=(string)($in['cliente']['nombre']??($client['nombre']??'Cliente web'));
 $clienteCorreo=(string)($in['cliente']['correo']??($client['correo']??''));
 $clienteTelefono=(string)($in['cliente']['telefono']??($client['whatsapp']??''));
 $clienteDireccion=(string)($in['cliente']['direccion']??'');
 $cid=($client && !empty($client['id']) && (int)$client['id']>0) ? (int)$client['id'] : null;
 $st=$pdo->prepare('INSERT INTO web_pedidos (folio,cliente_id,cupon_id,estado,moneda,subtotal,descuento,costo_envio,total,nombre_cliente,email_cliente,telefono_cliente,direccion_envio,notas_cliente,created_at,updated_at) VALUES (:f,:cid,:cup,"pendiente_pago","MXN",:sub,:des,0,:tot,:n,:e,:t,:d,:no,NOW(),NOW())');
 $st->execute([':f'=>$folio,':cid'=>$cid,':cup'=>$cuponId,':sub'=>$subtotal,':des'=>$discount,':tot'=>$total,':n'=>$clienteNombre,':e'=>$clienteCorreo,':t'=>$clienteTelefono,':d'=>$clienteDireccion,':no'=>(string)($in['cliente']['notas']??'')]);
 $pedidoId=(int)$pdo->lastInsertId();
 $sti=$pdo->prepare('INSERT INTO web_pedido_items (pedido_id,producto_id,sku,nombre_producto,cantidad,precio_unitario,subtotal,talla,color,created_at,updated_at) VALUES (:pid,:pr,:sku,:n,:q,:pu,:sub,:ta,:co,NOW(),NOW())');
 foreach($normalized as $r){ $sti->execute([':pid'=>$pedidoId,':pr'=>$r['producto_id'],':sku'=>$r['sku'],':n'=>$r['nombre'],':q'=>$r['qty'],':pu'=>$r['price'],':sub'=>$r['subtotal'],':ta'=>$r['size'],':co'=>$r['color']]); }
 $stp=$pdo->prepare('INSERT INTO web_pagos (pedido_id,proveedor,metodo,estado,monto,moneda,referencia_externa,payload_json,created_at,updated_at) VALUES (:pid,"manual_web","pendiente","pendiente",:m,"MXN",:r,:pl,NOW(),NOW())');
 $stp->execute([':pid'=>$pedidoId,':m'=>$total,':r'=>(string)($in['referencia_pago']??''),':pl'=>json_encode(['origen'=>'web_publica'])]);
 if($cuponId){ $pdo->prepare('UPDATE web_cupones SET usos_actuales=usos_actuales+1, updated_at=NOW() WHERE id=:id')->execute([':id'=>$cuponId]); }
 if($cid){ $pdo->prepare("UPDATE web_carritos SET estado='convertido', updated_at=NOW() WHERE cliente_id=:c AND estado='activo'")->execute([':c'=>$cid]); }
 $pdo->commit();
 out(['ok'=>true,'pedido_id'=>$pedidoId,'folio'=>$folio,'total'=>$total,'estado'=>'pendiente_pago','mensaje'=>'Pedido web creado correctamente.']);
}catch(Throwable $e){ if($pdo->inTransaction())$pdo->rollBack(); error_log('[web-orden] '.$e->getMessage()); out(['ok'=>false,'mensaje'=>'No se pudo crear el pedido web.']); }
