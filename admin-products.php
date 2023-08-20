<?php
use \Hcode\Model\User;
use Hcode\Model\Product;
use Hcode\PageAdmin;


$app->get("/admin/products", function()
{

    User::verifyLogin();

    $search = (isset($_GET['search'])) ? $_GET['search'] : ""; // Obtém o termo de pesquisa (se houver).
    $page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1; // Obtém o número da página atual.

    if ($search != '') {
        // Se houver um termo de pesquisa, obtém a página de produtos com base na pesquisa.
        $pagination = Product::getPageSearch($search, $page);
    } else {
        // Caso contrário, obtém a página de produtos sem pesquisa.
        $pagination = Product::getPage($page);
    }

    $pages = [];

    // Gera links para as páginas da lista de produtos.
    for ($x = 0; $x < $pagination['pages']; $x++)
    {
        array_push($pages, [
            'href'=>'/admin/products?'.http_build_query([
                'page'=>$x+1,
                'search'=>$search
            ]),
            'text'=>$x+1
        ]);
    }

	//Lista todos os protudos
    $products = Product::listAll(); 

    $page = new PageAdmin(); 

    // Passa os dados ao template
    $page->setTpl("products", [
        "products"=>$pagination['data'], // Produtos da página atual.
        "search"=>$search, // Termo de pesquisa.
        "pages"=>$pages // Links para as páginas.
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

//=======================================================
$app->get("/logout", function()
{

	User::logout();

	header("Location: /admin/login");
	exit;

});

?>