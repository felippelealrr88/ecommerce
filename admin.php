<?php
use Hcode\PageAdmin;
use \Hcode\Model\User;

$app->get("/admin", function() { 
	//verifica se está logado
	User::verifyLogin();
    //instancia uma nova Page (chama o construtor e add o header na tela)
	$page = new PageAdmin(); 

	//chama setTPL passando "index" como paranetro, esse arquivo será desenhado na tela
	$page->setTpl("index"); 

});


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


$app->get("/admin/logout", function() {

	User::logout();

	header("Location:/admin/login");
	exit;

});

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
	//carrega os dados do usuário convertendo
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
?>