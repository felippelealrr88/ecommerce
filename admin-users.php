<?php

use Hcode\PageAdmin;
use \Hcode\Model\User;

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


$app->get("/admin/users/:iduser/delete", function($iduser) {
	
	User::verifyLogin();

	$user = new User();

	//carrega o usuário para ver se ainda existe
	$user->getUser((int)$iduser);

	$user->delete();

	header("Location:/admin/users");
	exit;


});


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

?>