<?php

use Hcode\Model\Product;
use \Hcode\Page;
use \Hcode\Model\Category;

//Rota do Home
$app->get("/", function() { 

	$products = Product::listAll();

    //instancia uma nova Page (chama o construtor e add o header na tela)
	$page = new Page(); 

	//chama setTPL passando "index" como paranetro, esse arquivo será desenhado na tela
	$page->setTpl("index", [
		"products"=>Product::checkList($products)
	]); 

});

$app->get("/categories/:idcategory", function($idcategory){

	$category = new Category();
	//carrega a categoria
	$category->getCategory((int)$idcategory);

	//var_dump($category->getProducts()); exit;

	$page = new Page();

	//carrega o template category
	$page->setTpl("category", [
		'category'=>$category->getValues(),
		'products'=>Product::checkList($category->getProducts())
	]);

});


?>