<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/web_helpers.php';
header('Content-Type: application/json; charset=utf-8');

function out(array $d): void { echo json_encode($d, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit; }
function cart_db(): ?PDO { return function_exists('sw_web_db_pdo') ? sw_web_db_pdo() : null; }

$client = sw_client_current();
if (!$client || empty($client['id']) || (int)$client['id'] <= 0) out(['ok'=>true,'logged_in'=>false,'items'=>[]]);
$cid = (int)$client['id'];
$pdo = cart_db();
if (!$pdo) out(['ok'=>false,'logged_in'=>true,'items'=>[],'message'=>'BD no disponible.']);

function get_active_cart(PDO $pdo, int $cid): int {
  $st=$pdo->prepare("SELECT id FROM web_carritos WHERE cliente_id=:c AND estado='activo' ORDER BY id DESC LIMIT 1");
  $st->execute([':c'=>$cid]);
  $id=(int)($st->fetchColumn()?:0);
  if($id>0) return $id;
  $st=$pdo->prepare("INSERT INTO web_carritos (cliente_id,estado,moneda,created_at,updated_at) VALUES (:c,'activo','MXN',NOW(),NOW())");
  $st->execute([':c'=>$cid]);
  return (int)$pdo->lastInsertId();
}
function list_items(PDO $pdo, int $cartId): array {
  $sql="SELECT i.id,i.producto_id as id_producto,i.cantidad as qty,i.talla as size,i.color,p.nombre as name,COALESCE(NULLIF(p.precio_oferta,0),p.precio,0) as price,p.imagen_principal as image,p.slug FROM web_carrito_items i INNER JOIN web_productos p ON p.id=i.producto_id WHERE i.carrito_id=:id AND p.activo=1 ORDER BY i.id DESC";
  $st=$pdo->prepare($sql);$st->execute([':id'=>$cartId]);$rows=$st->fetchAll(PDO::FETCH_ASSOC)?:[];
  return array_map(fn($r)=>['item_id'=>(int)$r['id'],'id'=>(string)$r['id_producto'],'name'=>(string)$r['name'],'price'=>(float)$r['price'],'image'=>sw_public_asset_url((string)$r['image']),'url'=>'/producto/'.(int)$r['id_producto'].'-'.sw_slug((string)($r['slug']??'producto')),'size'=>(string)($r['size']??''),'color'=>(string)($r['color']??''),'qty'=>(int)$r['qty']],$rows);
}

$method=$_SERVER['REQUEST_METHOD']??'GET';
$input=[]; if($method!=='GET'){ $raw=file_get_contents('php://input'); $j=json_decode((string)$raw,true); if(is_array($j))$input=$j; if(!$input)$input=$_POST; }
try{
  $cartId=get_active_cart($pdo,$cid);
  if($method==='GET') out(['ok'=>true,'logged_in'=>true,'items'=>list_items($pdo,$cartId)]);

  if($method==='POST' || $method==='PUT' || $method==='PATCH'){
    $pid=(int)($input['product_id']??$input['id']??0); $qty=max(1,(int)($input['qty']??1));
    $size=trim((string)($input['size']??'')); $color=trim((string)($input['color']??''));
    if($pid<=0) out(['ok'=>false,'message'=>'Producto inválido.']);
    $chk=$pdo->prepare('SELECT id FROM web_productos WHERE id=:id AND activo=1 LIMIT 1'); $chk->execute([':id'=>$pid]); if(!(int)$chk->fetchColumn()) out(['ok'=>false,'message'=>'Producto no disponible.']);
    $st=$pdo->prepare('INSERT INTO web_carrito_items (carrito_id,producto_id,cantidad,precio_unitario,talla,color,created_at,updated_at) SELECT :c,:p,:q,COALESCE(NULLIF(precio_oferta,0),precio,0),:s,:co,NOW(),NOW() FROM web_productos WHERE id=:p ON DUPLICATE KEY UPDATE cantidad=:q, updated_at=NOW()');
    $st->execute([':c'=>$cartId,':p'=>$pid,':q'=>$qty,':s'=>$size,':co'=>$color]);
    out(['ok'=>true,'logged_in'=>true,'items'=>list_items($pdo,$cartId)]);
  }

  if($method==='DELETE'){
    if(!empty($input['clear'])){ $pdo->prepare('DELETE FROM web_carrito_items WHERE carrito_id=:c')->execute([':c'=>$cartId]); out(['ok'=>true,'logged_in'=>true,'items'=>[]]); }
    $itemId=(int)($input['item_id']??0); $pid=(int)($input['product_id']??0);
    if($itemId>0) $pdo->prepare('DELETE FROM web_carrito_items WHERE id=:id AND carrito_id=:c')->execute([':id'=>$itemId,':c'=>$cartId]);
    elseif($pid>0) $pdo->prepare('DELETE FROM web_carrito_items WHERE producto_id=:p AND carrito_id=:c')->execute([':p'=>$pid,':c'=>$cartId]);
    out(['ok'=>true,'logged_in'=>true,'items'=>list_items($pdo,$cartId)]);
  }
  out(['ok'=>false,'message'=>'Método no soportado.']);
}catch(Throwable $e){ error_log('[web-carrito] '.$e->getMessage()); out(['ok'=>false,'message'=>'No se pudo procesar el carrito.']); }
