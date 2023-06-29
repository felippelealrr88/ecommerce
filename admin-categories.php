<?php
use \Hcode\PageAdmin;
use \Hcode\Page;
use \Hcode\Model\Category;
use \Hcode\Model\User;

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

	//carrega do banco convertendo
	$category->getCategory((int)$idcategory);

	//seta os dados com dados do formulario
	$category->setData($_POST);

	$category->saveCategory();

	header("Location: /admin/categories");
	exit;

});

$app->get("/categories/:idcategory", function($idcategory){

	$category = new Category();
	//carrega a categoria
	$category->get((int)$idcategory);

	$page = new Page();

	//carrega o template category
	$page->setTpl("category", [
		'category'=>$category->getValues(),
		'products'=>[]
	]);

});

?>