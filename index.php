<?php 

session_start();

require_once("vendor/autoload.php");
//require_once("functions.php");

use \Slim\Slim;
use \Hcode\Page;
use Hcode\PageAdmin;
use \Hcode\Model\User;

//instancia o Slim framework (ROTAS)
$app = new Slim(); 

$app->config('debug', true); //Configuração

//Rota do Home
$app->get('/', function() { 
    //instancia uma nova Page (chama o construtor e add o header na tela)
	$page = new Page(); 

	//chama setTPL passando "index" como paranetro, esse arquivo será desenhado na tela
	$page->setTpl("index"); 

});

//Rota do Admin
$app->get('/admin', function() { 
	//verifica se está logado
	User::verifyLogin();
    //instancia uma nova Page (chama o construtor e add o header na tela)
	$page = new PageAdmin(); 

	//chama setTPL passando "index" como paranetro, esse arquivo será desenhado na tela
	$page->setTpl("index"); 

});

//Rota do Login Admin
$app->get('/admin/login', function() { 
    //instancia uma nova Page (passa o cabeçalho e rodapé como falso para o PageAdmin)
	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
	]); 
//chama o template
	$page->setTpl("login");

});

//Rota do Login Admin POST
$app->post('/admin/login', function() { 
	//valida usuário (recebe usuário e senha)
    User::login($_POST["login"], $_POST["password"]);
	//se não der erro carrega a página do admin
	header("Location: /admin");
	exit;
});

//rota de logout
$app->get('/admin/logout', function() {

	User::logout();

	header("Location: /admin/login");
	exit;

});

//apos o fim da execução chama o destruct com o footer da página

$app->run(); //roda o Slim

 ?>