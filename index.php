<?php 

require_once("vendor/autoload.php");
use \Slim\Slim;
use \Hcode\Page;

//instancia o Slim framework (ROTAS)
$app = new Slim(); 

$app->config('debug', true); //Configuração

//passa pelo método get ROTA ( \ )
$app->get('/', function() { 
    //instancia uma nova Page (chama o construtor e add o header na tela)
	$page = new Page(); 

	//chama setTPL passando "index" como paranetro, esse arquivo será desenhado na tela
	$page->setTpl("index"); 

}); 
//apos o fim da execução chama o destruct com o footer da página

$app->run(); //roda o Slim

 ?>