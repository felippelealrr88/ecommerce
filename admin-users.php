<?php

use \Hcode\PageAdmin;
use \Hcode\Model\User; 

// Alteração de senha de usuário ================================================================
$app->get("/admin/users/:iduser/password", function($iduser)
{

    // Verifica o login do usuário.
    User::verifyLogin(); 

    $user = new User();

    //Carrega os dados do usuario pelo id
    $user->getUser((int)$iduser);

    $page = new PageAdmin();

    // Passa os dados para o template
    $page->setTpl("users-password", [
        "user"=>$user->getValues(),
        "msgError"=>User::getError(),
        "msgSuccess"=>User::getSuccess()
    ]);

});


$app->post("/admin/users/:iduser/password", function($iduser)
{
    // Verifica o login do usuário
    User::verifyLogin(); 

    // Validações de campos de senha e confirmação de senha.
    if (!isset($_POST['despassword']) || $_POST['despassword']==='') {
        User::setError("Preencha a nova senha.");
        header("Location: /admin/users/$iduser/password");
        exit;
    }

    if (!isset($_POST['despassword-confirm']) || $_POST['despassword-confirm']==='') {
        User::setError("Preencha a confirmação da nova senha.");
        header("Location: /admin/users/$iduser/password");
        exit;
    }

    if ($_POST['despassword'] !== $_POST['despassword-confirm']) {
        User::setError("Confirme corretamente as senhas.");
        header("Location: /admin/users/$iduser/password");
        exit;
    }

    $user = new User();

    //Carrega o usuário pelo id
    $user->getUser((int)$iduser);

    // Define a nova senha para o usuário.
    $user->setPassword(User::getPasswordHash($_POST['despassword']));

    User::setSuccess("Senha alterada com sucesso.");

    header("Location: /admin/users/$iduser/password");
    exit;

});

// Listar usuários ================================================================ 
$app->get("/admin/users", function() 
{
    //Verifica se está logado como admin
    User::verifyLogin();

    //Se ela existir vem ela mesma se não vem vazio
    $search = (isset($_GET['search'])) ? $_GET['search'] : "";
    $page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

    if ($search != '') {
        $pagination = User::getPageSearch($search, $page); // Paginação com pesquisa.
    } else {
        $pagination = User::getPage($page); // Paginação sem pesquisa.
    }

    $pages = [];

    // Gera links para as páginas.
    for ($x = 0; $x < $pagination['pages']; $x++) {
        array_push($pages, [
            'href'=>'/admin/users?'.http_build_query([
                'page'=>$x+1,
                'search'=>$search
            ]),
            'text'=>$x+1
        ]);
    }

    $page = new PageAdmin();

    //Passa os dados para o template
    $page->setTpl("users", array(
        "users"=>$pagination['data'], // Dados dos usuários.
        "search"=>$search, // Termo de pesquisa.
        "pages"=>$pages // Links para as páginas.
    ));

});

// Criar um novo usuário ======================================================
$app->get("/admin/users/create", function() 
{
    //Verifica se é admin
    User::verifyLogin(); 

    $page = new PageAdmin();

    // Define o template "users-create"
    $page->setTpl("users-create");

});

// Deletar um usuário ==============================================================
$app->get("/admin/users/:iduser/delete", function($iduser) 
{
    // Verifica o login 
    User::verifyLogin(); 

    $user = new User();

    //Carrega os dados so usuario
    $user->getUser((int)$iduser); 

    $user->delete();

    // Redireciona de volta para a lista de usuários
    header("Location: /admin/users"); 
    exit;

});

// Atualizar informações de um usuário ================================================
$app->get("/admin/users/:iduser", function($iduser) 
{
    // Verifica o login do usuário
    User::verifyLogin(); 

    $user = new User();

    //Carrega os dados do usuario
    $user->getUser((int)$iduser); 

    $page = new PageAdmin();

    //Manda os dados para o template
    $page->setTpl("users-update", array(
        "user"=>$user->getValues() // Dados do usuário.
    ));

});

$app->post("/admin/users/create", function() 
{
    //Verifica se é admin
    User::verifyLogin();

    $user = new User();

    //Se inadmin não for enviado passa 0
    $_POST["inadmin"] = (isset($_POST["inadmin"])) ? 1 : 0;

    $_POST['despassword'] = User::getPasswordHash($_POST['despassword']); // Gera hash para a senha.

    // Define os dados do usuário.
    $user->setData($_POST); 

    $user->save();

    // Redireciona para a lista de usuários
    header("Location: /admin/users"); 
    exit;

});

$app->post("/admin/users/:iduser", function($iduser) 
{
    // Verifica o login do usuário
    User::verifyLogin(); 

    $user = new User();

    //Se inadmin não for enviado passa 0
    $_POST["inadmin"] = (isset($_POST["inadmin"])) ? 1 : 0;

    //Carrega os dados de user
    $user->getUser((int)$iduser); 

    //Define os dados de user
    $user->setData($_POST); 

    $user->update(); 

    // Redireciona para a lista de usuários
    header("Location: /admin/users"); 
    exit;

});

?>
