<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/web_helpers.php';
header('Content-Type: application/json; charset=utf-8');
$pdo = function_exists('sw_web_db_pdo') ? sw_web_db_pdo() : null;
if (!$pdo) { echo json_encode(['ok'=>false,'message'=>'BD no disponible.']); exit; }
$input = json_decode((string)file_get_contents('php://input'), true); if(!is_array($input)) $input=$_POST;
$code = strtoupper(trim((string)($input['codigo'] ?? '')));
$subtotal = max(0, (float)($input['subtotal'] ?? 0));
if ($code==='') { echo json_encode(['ok'=>false,'message'=>'Código inválido.']); exit; }
try {
 $st=$pdo->prepare("SELECT * FROM web_cupones WHERE codigo=:c LIMIT 1"); $st->execute([':c'=>$code]); $c=$st->fetch(PDO::FETCH_ASSOC);
 if(!$c || (int)$c['activo']!==1){ echo json_encode(['ok'=>false,'message'=>'Cupón no disponible.']); exit; }
 $now=time(); if(!empty($c['fecha_inicio']) && strtotime($c['fecha_inicio'])>$now){ echo json_encode(['ok'=>false,'message'=>'Cupón aún no disponible.']); exit; }
 if(!empty($c['fecha_fin']) && strtotime($c['fecha_fin'])<$now){ echo json_encode(['ok'=>false,'message'=>'Cupón vencido.']); exit; }
 if($c['usos_maximos']!==null && (int)$c['usos_actuales'] >= (int)$c['usos_maximos']){ echo json_encode(['ok'=>false,'message'=>'Cupón sin usos disponibles.']); exit; }
 if($subtotal < (float)$c['monto_minimo']){ echo json_encode(['ok'=>false,'message'=>'No alcanza el monto mínimo.']); exit; }
 $discount = $c['tipo_descuento']==='porcentaje' ? ($subtotal*((float)$c['valor']/100)) : (float)$c['valor'];
 $discount=max(0,min($subtotal,$discount));
 echo json_encode(['ok'=>true,'codigo'=>$code,'descuento'=>$discount,'tipo'=>$c['tipo_descuento'],'valor'=>(float)$c['valor']]);
} catch(Throwable $e){ error_log('[web-cupon] '.$e->getMessage()); echo json_encode(['ok'=>false,'message'=>'No se pudo validar cupón.']); }
