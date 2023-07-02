<?php 

session_start();

require_once("vendor/autoload.php");
//require_once("functions.php");

use \Slim\Slim;

//====================== CONFIGURAÇÕES DE ROTAS DO SLIM =======================================================
$app = new Slim(); 

$app->config("debug", true); //Configuração

require_once("site.php");
require_once("admin.php");
require_once("admin-users.php");
require_once("admin-categories.php");
require_once("admin-products.php");
require_once("functions.php");

//apos o fim da execução chama o destruct com o footer da página
$app->run(); //roda o Slim

 ?>