<?php 

require_once("vendor/autoload.php");

//new app
$app = new \Slim\Slim();

//new debug
$app->config('debug', true);

//rout to index
$app->get('/', function() {
    
	$sql = new Hcode\DB\Sql();
	$results = $sql->select("SELECT * FROM tb_users");
	echo json_encode($results);
});

//run app
$app->run();

 ?>