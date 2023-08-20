<?php
use \Hcode\PageAdmin;
use \Hcode\Model\Category;
use Hcode\Model\Product;
use \Hcode\Model\User;

//Principal de categorias
$app->get("/admin/categories", function()
{

	// Verifica o login do usuário
    User::verifyLogin(); 

	//Valida os campos search e page
    $search = (isset($_GET['search'])) ? $_GET['search'] : ""; // Obtém o termo de pesquisa (se houver).
    $page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1; // Obtém o número da página atual.

    if ($search != '') {
        // Se houver um termo de pesquisa, obtém a página de categorias com base na pesquisa.
        $pagination = Category::getPageSearch($search, $page);
    } else {
        // Caso contrário, obtém a página de categorias sem pesquisa.
        $pagination = Category::getPage($page);
    }

    $pages = [];

    // Gera links para as páginas da lista de categorias.
    for ($x = 0; $x < $pagination['pages']; $x++)
    {
        array_push($pages, [
            'href'=>'/admin/categories?'.http_build_query([
                'page'=>$x+1,
                'search'=>$search
            ]),
            'text'=>$x+1
        ]);
    }

    $page = new PageAdmin();

    // Passa os dados para o template
    $page->setTpl("categories", [
        "categories"=>$pagination['data'], // Categorias da página atual.
        "search"=>$search, // Termo de pesquisa.
        "pages"=>$pages // Links para as páginas.
    ]);
});

//Cria nova categoria ====================================
$app->get("/admin/categories/create", function()
{

	User::verifyLogin();

	$page = new PageAdmin();

	$page->setTpl("categories-create");
});

$app->post("/admin/categories/create", function(){

	User::verifyLogin();

	$category = new Category();

	//Seta os dados com o POST do formulário
	$category->setData($_POST);

	$category->saveCategory();

	header("Location: /admin/categories");
	exit;
});

// Deleta uma categoria ==========================================================
$app->get("/admin/categories/:idcategory/delete", function($idcategory)
{

	User::verifyLogin();

	$category = new Category();

	//carrega para ter certeza que existe no banco
	$category->getCategory((int)$idcategory);

	$category->deleteCategory();

	header("Location: /admin/categories");
	exit;
});

//Detalhes da categoria ===================================================
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

$app->post("/admin/categories/:idcategory", function($idcategory)
{

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

// Produtos por categoria =========================================================
$app->get("/admin/categories/:idcategory/products", function($idcategory)
{

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

//Adicionar produto a categoria =========================================================
$app->get("/admin/categories/:idcategory/products/:idproduct/add", function($idcategory, $idproduct)
{

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

//Remove um produto da categoria ========================================
$app->get("/admin/categories/:idcategory/products/:idproduct/remove", function($idcategory, $idproduct)
{

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