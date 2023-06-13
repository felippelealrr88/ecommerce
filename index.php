<?php 

session_start();

require_once("vendor/autoload.php");
//require_once("functions.php");

use \Slim\Slim;
use \Hcode\Page;
use Hcode\PageAdmin;
use \Hcode\Model\User;

//====================== CONFIGURAÇÕES DE ROTAS DO SLIM =======================================================
$app = new Slim(); 

$app->config("debug", true); //Configuração

//Rota do Home
$app->get("/", function() { 
    //instancia uma nova Page (chama o construtor e add o header na tela)
	$page = new Page(); 

	//chama setTPL passando "index" como paranetro, esse arquivo será desenhado na tela
	$page->setTpl("index"); 

});

//================================== ADMIN ==================================================================

$app->get("/admin", function() { 
	//verifica se está logado
	User::verifyLogin();
    //instancia uma nova Page (chama o construtor e add o header na tela)
	$page = new PageAdmin(); 

	//chama setTPL passando "index" como paranetro, esse arquivo será desenhado na tela
	$page->setTpl("index"); 

});

//==================================== LOGIN ===================================================================
$app->get("/admin/login", function() { 
    
	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
	]); 

	$page->setTpl("login");

});


$app->post("/admin/login", function() { 
	//valida usuário (recebe usuário e senha)
    User::login($_POST["login"], $_POST["password"]);
	//se não der erro carrega a página do admin
	header("Location: /admin");
	exit;
});
//================================================= LOGOUT =================================================================================

$app->get("/admin/logout", function() {

	User::logout();

	header("Location:/admin/login");
	exit;

});

//============================================== USUARIOS ==================================================================================

//LISTAR
$app->get("/admin/users", function() {
	
	User::verifyLogin();

	$users = User::listAll();

//instancia uma nova Page (chama o construtor e add o header na tela)
	$page = new PageAdmin(); 
//chama o template
	$page->setTpl("users", array(
		"users"=>$users
	));
});

//CRIAR
	$app->get("/admin/users/create", function() { 
    
	//verifica se está logado
	User::verifyLogin();

	//instancia uma nova Page (chama o construtor e add o header na tela)
	$page = new PageAdmin();
	
	//chama setTPL passando "users" como paranetro, esse arquivo será desenhado na tela
	$page->setTpl("users-create"); 

});

$app->post("/admin/users/create", function () {

	User::verifyLogin();

   $user = new User();

   //Verifica se o inadmin foi definido = 1 se não 0
	$_POST["inadmin"] = (isset($_POST["inadmin"])) ? 1 : 0;

	$_POST['despassword'] = password_hash($_POST["despassword"], PASSWORD_DEFAULT, [

		"cost"=>12

	]);

   //cria um atributo para cada valor do array
	$user->setData($_POST);

   $user->save();

   header("Location:/admin/users");
	exit;

});

//DELETAR
$app->get("/admin/users/:iduser/delete", function($iduser) {
	
	User::verifyLogin();

	$user = new User();

	//carrega o usuário para ver se ainda existe
	$user->getUser((int)$iduser);

	$user->delete();

	header("Location:/admin/users");
	exit;


});

//EDITAR
$app->get("/admin/users/:iduser", function($iduser) {

	User::verifyLogin();

	$user = new User();

	$user->getUser((int)$iduser);

	$page = new PageAdmin();

	$page->setTpl("users-update", array(
		"user"=>$user->getValues()
	));

});

$app->post("/admin/users/:iduser", function($iduser) {
	
	User::verifyLogin();

	$user = new User();

	//Verifica se o inadmin foi definido = 1 se não 0
	$_POST["inadmin"] = (isset($_POST["inadmin"])) ? 1 : 0;

	$user->getUser((int)$iduser);

	$user->setData($_POST);

	$user->update();

	header("Location:/admin/users");
	exit;
});

//============================================= ESQUECEU A SENHA ========================================================

//FORGOT
$app->get("/admin/forgot", function(){

	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
	]);

	$page->setTpl("forgot");
});

$app->post("/admin/forgot", function(){

	$user = User::getForgot($_POST["email"]);

	header("Location:/admin/forgot/sent");
	exit;

});

//ENVIADO
$app->get("/admin/forgot/sent", function(){

	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
	]);

	$page->setTpl("forgot-sent");

});

//apos o fim da execução chama o destruct com o footer da página
$app->run(); //roda o Slim

 ?>