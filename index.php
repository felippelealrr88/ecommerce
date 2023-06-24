<?php 

session_start();

require_once("vendor/autoload.php");
//require_once("functions.php");

use \Slim\Slim;
use \Hcode\Page;
use Hcode\PageAdmin;
use \Hcode\Model\User;
use \Hcode\Model\Category;

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

//============================================== CRUD USUARIOS ==================================================================================

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

$app->get("/admin/forgot/reset", function(){
	//Identificação do usuário
	$user = User::validForgotDecrypt($_GET["code"]);

	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
	]);

	//passa um array para o template
	$page->setTpl("forgot-reset", array(
		"name"=>$user["desperson"],
		"code"=>$_GET["code"]
	));

});

//Seta que o código foi usado
$app->post("/admin/forgot/reset", function(){

	$forgot = User::validForgotDecrypt($_POST["code"]);

	//Recuperação já usada
	User::setForgotUsed($forgot["idrecovery"]);

	$user = new User();
	//carrega os dados do usuário
	$user->get((int)$forgot["iduser"]);

	$password = password_hash($_POST["password"], PASSWORD_DEFAULT, [
		"cost"=>12
	]);

	//seta a nova senha informada no banco (hash)
	$user->setPassword($password);

	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
	]);

	$page->setTpl("forgot-reset-success");

});

//====================== CRUD CATEGORIAS ===================================================================

$app->get("/admin/categories", function(){

	User::verifyLogin();

	$categories = Category::listAll();
	
	$page = new PageAdmin();

	//passa um array de categorias para o template
	$page->setTpl("categories",[
		"categories"=>$categories
	]);

});

$app->get("/admin/categories/create", function(){

	User::verifyLogin();

	$page = new PageAdmin();

	$page->setTpl("categories-create");
});

$app->post("/admin/categories/create", function(){

	User::verifyLogin();

	$category = new Category();

	//Seta o array Global Post (model)
	$category->setData($_POST);

	$category->saveCategory();

	header("Location: /admin/categories");
	exit;
});

$app->get("/admin/categories/:idcategory/delete", function($idcategory){

	User::verifyLogin();

	$category = new Category();

	//carrega para ter certeza que existe no banco
	$category->getCategory((int)$idcategory);

	$category->deleteCategory();

	header("Location: /admin/categories");
	exit;
});

$app->get("/admin/categories/:idcategory", function($idcategory){

	User::verifyLogin();

	$category = new Category();

	//Converte da URL (string) em inteiro
	$category->getCategory((int)$idcategory);

	$page = new PageAdmin();

	//converte objeto para array e manda para o template
	$page->setTpl("categories-update", array(
		"category"=>$category->getValues()
	));
});

$app->post("/admin/categories/:idcategory", function($idcategory){

	User::verifyLogin();

	$category = new Category();

	//Converte da URL (string) em inteiro
	$category->getCategory((int)$idcategory);

	//seta os dados com dados do formulario
	$category->setData($_POST);

	$category->saveCategory();

	header("Location: /admin/categories");
	exit;

});

//=======================================================================================


//apos o fim da execução chama o destruct com o footer da página
$app->run(); //roda o Slim

 ?>