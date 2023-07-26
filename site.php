<?php

use Hcode\Model\Cart;
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

//categoria de produtos
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

//Página de detalhes do Produto
$app->get("/products/:desurl", function($desurl){

	$product = new Product();

	//carrega os dados do banco de um único produto
	$product->getFromUrl($desurl);
	
	$page = new Page();

	$page->setTpl("product-detail", [
		'product'=>$product->getValues(),
		'categories'=>$product->getCategories()
	]);
	//var_dump($page);
});

//Carrinho de compras
$app->get("/cart", function(){
	
	//verifica se o carrinho existe
	$cart = Cart::getFromSession();
	
	$page = new Page();

	//passa as informações para o template
	$page->setTpl("cart", [
		'cart'=>$cart->getValues(),
		'products'=>$cart->getProducts()
	]);
});

//Adiciona produtos ao carrinho
$app->get("/cart/:idproduct/add", function($idproduct){
	
	$product = new Product();

	//busca no banco os produtos cadastrados
	$product->getProduct((int)$idproduct);

	//recupera o carrinho da sessão (recupera a sessão ou cria uma nova)
	$cart = Cart::getFromSession();

	//pega a quantidade vinda do form (1 por padrão)
	$qtd = (isset($_GET['qtd'])) ? (int)$_GET['qtd'] : 1;

	for ($i=0; $i < $qtd; $i++) { 
		//adiciona o produto no carrinho
		$cart->addProduct($product);

	}

	header("Location: /cart");
	exit;

});

//Remove um produto do carrinho
$app->get("/cart/:idproduct/minus", function($idproduct){
	
	$product = new Product();

	//busca no banco os produtos cadastrados
	$product->getProduct((int)$idproduct);

	//recupera o carrinho da sessão (recupera a sessão ou cria uma nova)
	$cart = Cart::getFromSession();

	//adiciona o produto no carrinho
	$cart->removeProduct($product);

	header("Location: /cart");
	exit;

});

//Remove todos os produtos do carrinho
$app->get("/cart/:idproduct/remove", function($idproduct){
	
	$product = new Product();

	//busca no banco os produtos cadastrados
	$product->getProduct((int)$idproduct);

	//recupera o carrinho da sessão (recupera a sessão ou cria uma nova)
	$cart = Cart::getFromSession();

	//adiciona o produto no carrinho
	$cart->removeProduct($product, true);

	header("Location: /cart");
	exit;

});

?>