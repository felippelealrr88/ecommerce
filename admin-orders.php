<?php

use \Hcode\PageAdmin;
use \Hcode\Model\User;
use \Hcode\Model\Order;
use \Hcode\Model\OrderStatus;
  
//Visualizar e atualizar o status de um pedido ==========================================
$app->get("/admin/orders/:idorder/status", function($idorder)
{
    // Verifica se o usuário está logado como administrador
    User::verifyLogin();

    $order = new Order();

    // Carrega as informações do pedido com o id
    $order->getOrder((int)$idorder);

    $page = new PageAdmin();

    //Passa os dados para o template
    $page->setTpl("order-status", [
            'order'=>$order->getValues(),
            'status'=>OrderStatus::listAll(),
            'msgSuccess'=>Order::getSuccess(),
            'msgError'=>Order::getError()
    ]);

});

$app->post("/admin/orders/:idorder/status", function($idorder)
{
    // Verifica se o usuário está logado como administrador
    User::verifyLogin();

    // Verifica se o campo 'idstatus' está definido e é um número inteiro positivo
    if (!isset($_POST['idstatus']) || !(int)$_POST['idstatus'] > 0) {
        Order::setError("Informe o status atual.");
        header("Location: /admin/orders/".$idorder."/status");
        exit;
    }

    $order = new Order();

    //Carrega as informações do pedido com o id
    $order->getOrder((int)$idorder);

    // Define o novo ID de status para o pedido
    $order->setidstatus((int)$_POST['idstatus']);

    // Salva as alterações no pedido
    $order->saveOrder();

    // Define uma mensagem de sucesso
    Order::setSuccess("Status atualizado.");

    // Redireciona de volta à página de status do pedido
    header("Location: /admin/orders/".$idorder."/status");
    exit;

});

// Rota para excluir um pedido =================================================
$app->get("/admin/orders/:idorder/delete", function($idorder)
{

    // Verifica se o usuário está logado como administrador
    User::verifyLogin();

    $order = new Order();

    // Obtém as informações do pedido com o id
    $order->getOrder((int)$idorder);

    // Exclui o pedido
    $order->delete();

    // Redireciona de volta à lista de pedidos no painel de administração
    header("Location: /admin/orders");
    exit;

});

// Visualizar os detalhes de um pedido =====================================================
$app->get("/admin/orders/:idorder", function($idorder)
{

        // Verifica se o usuário está logado como administrador
        User::verifyLogin();

        $order = new Order();

        // Obtém as informações do pedido com o ID especificado
        $order->getOrder((int)$idorder);

        // Obtém o carrinho de compras associado ao pedido
        $cart = $order->getCart();

        $page = new PageAdmin();

        // Passa informações como os detalhes do pedido, o carrinho de compras e os produtos no carrinho para p template
        $page->setTpl("order", [
            'order'=>$order->getValues(),
            'cart'=>$cart->getValues(),
            'products'=>$cart->getProducts()
        ]);

});

// Listar todos os pedidos =====================================================
$app->get("/admin/orders", function()
{

    // Verifica se o usuário está logado como administrador
    User::verifyLogin();

    // Obtém parâmetros da consulta (search e page)
    $search = (isset($_GET['search'])) ? $_GET['search'] : "";
    $page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

    if ($search != '') {

        // Obtém uma página de resultados de pesquisa de pedidos
        $pagination = Order::getPageSearch($search, $page);

    } else {

        // Obtém uma página de resultados de todos os pedidos
        $pagination = Order::getPage($page);

    }

    // Cria um array de páginas para a paginação
    $pages = [];

    for ($x = 0; $x < $pagination['pages']; $x++)
    {

        array_push($pages, [
            'href'=>'/admin/orders?'.http_build_query([
                'page'=>$x+1,
                'search'=>$search
            ]),
            'text'=>$x+1
        ]);

    }

    $page = new PageAdmin();

    // Passa informações como os pedidos, termo de pesquisa e páginas para o template
    $page->setTpl("orders", [
        "orders"=>$pagination['data'],
        "search"=>$search,
        "pages"=>$pages
    ]);

});

?>