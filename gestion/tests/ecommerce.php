<?php
declare(strict_types=1);
putenv('STRUCH_TESTING=1');require dirname(__DIR__).'/scripts/migrate.php';
$c=require dirname(__DIR__).'/config/bootstrap.php';$db=Core\Database::connect($c['database']);$tag='commerce-'.bin2hex(random_bytes(5));
$check=static function(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);};
$reject=static function(callable $op):void{try{$op();}catch(DomainException){return;}throw new RuntimeException('Oferta inválida aceptada');};
$db->prepare('INSERT INTO users(username,name,email,password_hash) VALUES (?,?,?,?)')->execute([$tag,$tag,$tag.'@example.test',password_hash($tag,PASSWORD_DEFAULT)]);
$user=(int)$db->lastInsertId();$id=null;$hash=hash('sha256',$tag);$service=new App\Service\CatalogService($db);
try{
 $base=['code'=>$tag,'name'=>$tag,'price'=>'100.00','unit'=>'unidad','min_stock'=>'0','active'=>1,'publish_online'=>1,'web_group'=>'alimentos','pet_type'=>'perros','offer_price'=>'75.00','offer_from'=>'2026-01-01','offer_until'=>'2026-12-31'];
 $id=$service->save('productos',$base,null,$user);$p=(new App\Model\Catalog($db))->find('products',$id);
 foreach(['2025-12-31'=>'100.00','2026-01-01'=>'75.00','2026-06-01'=>'75.00','2026-12-31'=>'75.00','2027-01-01'=>'100.00'] as $day=>$price)$check(App\Service\WebPricing::apply($p,$day)['price']===$price,'Vigencia incorrecta '.$day);
 $check(App\Service\WebPricing::apply($p,'2026-06-01')['discount_percent']===25,'Porcentaje incorrecto');
 $tiny = App\Service\WebPricing::apply([...$p,'offer_price'=>'99.99'],'2026-06-01');
 $check($tiny['on_sale'] && $tiny['price']==='99.99' && $tiny['discount_percent']===0,'Oferta menor al uno por ciento incorrecta');
 foreach([['offer_price'=>'100'],['offer_price'=>'101'],['offer_price'=>'0'],['offer_from'=>'2026-12-31','offer_until'=>'2026-01-01'],['web_group'=>'hack'],['pet_type'=>'hack'],['offer_from'=>'2026-02-30']] as $invalid)$reject(fn()=>$service->save('productos',[...$base,...$invalid],$id,$user));
 $db->prepare('INSERT INTO stock_lots(product_id,lot_code,cost,quantity) VALUES (?,?,1,10)')->execute([$id,$tag]);$service->save('productos',[...$base,'offer_from'=>'','offer_until'=>''],$id,$user);
 $requests=new App\Service\WebRequestService($db);$input=['kind'=>'order','request_key'=>hash('sha256',$tag.'request'),'name'=>$tag,'phone'=>'000000000','privacy'=>true,'items'=>[['id'=>$id,'quantity'=>'2','price'=>'0.01']]];
 $reference=$requests->submit($input,$hash);$q=$db->prepare('SELECT payload FROM web_requests WHERE reference=?');$q->execute([$reference]);$payload=json_decode($q->fetchColumn(),true);
 $check($payload['total']==='150.00','Importe del pedido incorrecto');$check($requests->submit($input,$hash)===$reference,'Pedido duplicado');
 $check((string)$db->query('SELECT quantity FROM stock_lots WHERE product_id='.$id)->fetchColumn()==='10.000','Stock modificado');
 $service->save('productos',[...$base,'offer_price'=>'','offer_from'=>'','offer_until'=>''],$id,$user);$check(!App\Service\WebPricing::apply((new App\Model\Catalog($db))->find('products',$id))['on_sale'],'Oferta no retirada');
 $routes=FastRoute\simpleDispatcher(require dirname(__DIR__).'/core/app/routes.php');
 foreach([['POST','/catalogos/productos'],['GET','/catalogos/productos/nuevo'],['POST','/catalogos/categorias'],['POST','/catalogos/marcas'],['POST','/productos/1/imagen'],['POST','/catalogos/productos/1/retirar']] as [$method,$url]){$r=$routes->dispatch($method,$url);$check(($r[1][2]??'')==='products.manage','Permiso administrativo ausente: '.$url);}
 echo "OK: ofertas, vigencia, categorías, retiro, precio del servidor, stock, idempotencia y permisos administrativos.\n";
}finally{
 $db->prepare('DELETE FROM web_requests WHERE name=?')->execute([$tag]);$db->prepare('DELETE FROM web_submission_limits WHERE bucket=?')->execute([$hash]);$db->prepare('DELETE FROM audit_events WHERE user_id=?')->execute([$user]);
 if($id){$db->prepare('DELETE FROM stock_lots WHERE product_id=?')->execute([$id]);$db->prepare('DELETE FROM products WHERE id=?')->execute([$id]);}$db->prepare('DELETE FROM users WHERE id=?')->execute([$user]);
}
