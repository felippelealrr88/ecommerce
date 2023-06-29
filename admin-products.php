<?php
use \Hcode\Model\User;
use Hcode\Model\Product;
use Hcode\PageAdmin;


$app->get("/admin/products", function(){

	User::verifyLogin();

	$page = new PageAdmin();

	$products = Product::listAll();	

	$page->setTpl("products", [
		"products"=>$products
	]);

});

$app->get("/admin/products/create", function(){

	User::verifyLogin();

	$page = new PageAdmin();

	$page->setTpl("products-create");
});

$app->post("/admin/products/create", function(){

	User::verifyLogin();

	$product = new Product();

	$product->setData($_POST);

	$product->saveProduct();

	header("Location: /admin/products");
	exit;
});

$app->get("/admin/products/:idproduct/delete", function($idproduct){

	User::verifyLogin();

	$product = new Product();

	//carrega para ter certeza que existe no banco
	$product->getProduct((int)$idproduct);

	$product->deleteProduct();

	header("Location: /admin/products");
	exit;
});

$app->get("/admin/products/:idproduct", function($idproduct){

	User::verifyLogin();

	$product = new Product();

	//carrega para ter certeza que existe no banco
	$product->getProduct((int)$idproduct);

	$page = new PageAdmin();

	//Converte o objeto para array e envia para o template
	$page->setTpl("products-update", array(
		"product"=>$product->getValues()
	));
});

$app->post("/admin/products/:idproduct", function($idproduct){

	User::verifyLogin();

	$product = new Product();

	//carrega do banco convertendo
	$product->getProduct((int)$idproduct);

	//seta os dados com dados do formulario
	$product->setData($_POST);

	$product->saveProduct();

	//upload do arquivo
	$product->setPhoto($_FILES["file"]);

	header("Location: /admin/products");
	exit;
});

?>