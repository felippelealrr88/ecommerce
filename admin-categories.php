<?php
use \Hcode\PageAdmin;
use \Hcode\Model\Category;
use Hcode\Model\Product;
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


$app->get("/admin/categories/:idcategory/products", function($idcategory){

	User::verifyLogin();

	$category = new Category();

	//carrega a categoria
	$category->getCategory((int)$idcategory);

	$page = new PageAdmin();

	//carrega o template categories-product passando os arrays para o front
	$page->setTpl("categories-products", [
		'category'=>$category->getValues(),
		'productsRelated'=>$category->getProducts(true),
		'productsNotRelated'=>$category->getProducts(false)
	]);

});

$app->get("/admin/categories/:idcategory/products/:idproduct/add", function($idcategory, $idproduct){

	User::verifyLogin();

	$category = new Category();

	//carrega a categoria
	$category->getCategory((int)$idcategory);

	$product = new Product();

	$product->getProduct((int)$idproduct);

	$category->addProduct($product);

	header("Location: /admin/categories/".$idcategory."/products");
	exit;

});

$app->get("/admin/categories/:idcategory/products/:idproduct/remove", function($idcategory, $idproduct){

	User::verifyLogin();

	$category = new Category();

	//carrega a categoria
	$category->getCategory((int)$idcategory);

	$product = new Product();

	$product->getProduct((int)$idproduct);

	$category->removeProduct($product);

	header("Location: /admin/categories/".$idcategory."/products");
	exit;

});




?>