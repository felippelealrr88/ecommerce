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

	//Recebe a página atual, se não foi definido é a página 1
	$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

	$category = new Category();
	//carrega a categoria
	$category->getCategory((int)$idcategory);

	//usa a pagina recebida pelo $_GET como parametro para getProductsPage
	$pagination = $category->getProductsPage($page);

	$pages = [];
	
	// menor ou igual o tot de páginas
	for($i = 1; $i <= $pagination['pages']; $i++){

		//add outro array em $pages cm as informações que o front precisa
		array_push($pages, [
			//caminho que vou mandar o usuário ao clicar no link
			'link'=>'/categories/'.$category->getidcategory(). '?page=' . $i,
			'page'=>$i
		]);
	}

	$page = new Page();

	//carrega o template category
	$page->setTpl("category", [
		'category'=>$category->getValues(),
		'products'=>$pagination["data"],
		'pages'=>$pages
	]);

});


?>