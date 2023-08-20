<?php

use Hcode\Model\Cart;
use Hcode\Model\Product;
use \Hcode\Page;
use \Hcode\Model\Category;
use \Hcode\Model\Address;
use Hcode\Model\User;
use Hcode\Model\Order;
use Hcode\Model\OrderStatus;


//Rota do Home ==============================================================================
$app->get("/", function()
{ 

	$products = Product::listAll();

    //instancia uma nova Page (chama o construtor e add o header na tela)
	$page = new Page(); 

	//chama setTPL passando "index" como paranetro, esse arquivo será desenhado na tela
	$page->setTpl("index", [
		"products"=>Product::checkList($products)
	]); 

});

//categoria de produtos ==========================================================================
$app->get("/categories/:idcategory", function($idcategory)
{

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

//Página de detalhes do Produto =======================================================================
$app->get("/products/:desurl", function($desurl)
{

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

//Carrinho de compras ==========================================================================
$app->get("/cart", function()
{
	
	//verifica se o carrinho existe
	$cart = Cart::getFromSession();
	
	$page = new Page();

	//var_dump($cart); exit;
	//passa as informações para o template
	$page->setTpl("cart", [
		'cart'=>$cart->getValues(),
		'products'=>$cart->getProducts(),
		'error'=>Cart::getMsgError()
	]);
});

//Adiciona produtos ao carrinho =========================================================================
$app->get("/cart/:idproduct/add", function($idproduct)
{
	
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

//Remove um produto do carrinho ===========================================================================
$app->get("/cart/:idproduct/minus", function($idproduct)
{
	
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

//Remove todos os produtos do carrinho =====================================================================
$app->get("/cart/:idproduct/remove", function($idproduct)
{
	
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

//calculo do frete =======================================================
$app->post("/cart/freight", function()
{

	//Pega o carrinho da sessão
	$cart = Cart::getFromSession();

	//Manda o cep por post
	$cart->setFreight($_POST['zipcode']);

	header("Location: /cart");
	exit;

});

//checkout do carrinho =========================================================
$app->get("/checkout", function()
{

	//Verifica se o usuário está logado
	User::verifyLogin(false);

	$address = new Address();

	//pega o carrinho da sessão
	$cart = Cart::getFromSession();

	//Se o cep não tá setado
	if (!isset($_GET['zipcode'])) {
		//pega o cep do carrinho
		$_GET['zipcode'] = $cart->getdeszipcode();

	}

	//Se está setado
	if (isset($_GET['zipcode'])) {

		//carrega o objeto com o formato correto
		$address->loadFromCEP($_GET['zipcode']);

		//seta o novo cep no carrinho
		$cart->setdeszipcode($_GET['zipcode']);

		$cart->saveCart();
		
		//Força atualização do valor total
		$cart->getCalculateTotal();

	}

	//Vefiricações
	//Ficam definidos mesmo se vazios

	if (!$address->getdesaddress()) $address->setdesaddress('');
	if (!$address->getdesnumber()) $address->setdesnumber('');
	if (!$address->getdescomplement()) $address->setdescomplement('');
	if (!$address->getdesdistrict()) $address->setdesdistrict('');
	if (!$address->getdescity()) $address->setdescity('');
	if (!$address->getdesstate()) $address->setdesstate('');
	if (!$address->getdescountry()) $address->setdescountry('');
	if (!$address->getdeszipcode()) $address->setdeszipcode('');

	$page = new Page();

	//Passa os dados necessários ao template
	$page->setTpl("checkout", [
		'cart'=>$cart->getValues(),
		'address'=>$address->getValues(),
		'products'=>$cart->getProducts(),
		'error'=>Address::getMsgError()
	]);

});

$app->post("/checkout", function(){

	//Verifica se está logado
	User::verifyLogin(false);

	//Validações dos campos do formulário
	if (!isset($_POST['zipcode']) || $_POST['zipcode'] === '') {
		Address::setMsgError("Informe o CEP.");
		header('Location: /checkout');
		exit;
	}

	if (!isset($_POST['desaddress']) || $_POST['desaddress'] === '') {
		Address::setMsgError("Informe o endereço.");
		header('Location: /checkout');
		exit;
	}

	if (!isset($_POST['desdistrict']) || $_POST['desdistrict'] === '') {
		Address::setMsgError("Informe o bairro.");
		header('Location: /checkout');
		exit;
	}

	if (!isset($_POST['descity']) || $_POST['descity'] === '') {
		Address::setMsgError("Informe a cidade.");
		header('Location: /checkout');
		exit;
	}

	if (!isset($_POST['desstate']) || $_POST['desstate'] === '') {
		Address::setMsgError("Informe o estado.");
		header('Location: /checkout');
		exit;
	}

	if (!isset($_POST['descountry']) || $_POST['descountry'] === '') {
		Address::setMsgError("Informe o país.");
		header('Location: /checkout');
		exit;
	}

	//Pega o usuário da sessão
	$user = User::getFromSession();

	$address = new Address();

	//Sobrescreve pq o campo no banco é diferente do template
	$_POST['deszipcode'] = $_POST['zipcode'];
	$_POST['idperson'] = $user->getidperson();

	//Passa os dados do formulario por post
	$address->setData($_POST);

	//Salva o endereço no banco
	$address->saveAddress();

	//Pega o carrinho
	$cart = Cart::getFromSession();

	//Força o calculo do total
	$cart->getCalculateTotal();

	$order = new Order();

	//Passa os dados do pedido
	$order->setData([
		'idcart'=>$cart->getidcart(),
		'idaddress'=>$address->getidaddress(),
		'iduser'=>$user->getiduser(),
		'idstatus'=>OrderStatus::EM_ABERTO,
		'vltotal'=>$cart->getvltotal()
	]);

	$order->saveOrder();
	//var_dump($order->getidorder()); exit;

	switch ((int)$_POST['payment-method']) {

		case 1:
		header("Location: /order/".$order->getidorder()."/pagseguro");
		break;

		case 2:
		header("Location: /order/".$order->getidorder()."/paypal");
		break;

	}
	

	exit;

});

//Login =================================================================================================
$app->get("/login", function()
{

	$page = new Page();

	$page->setTpl("login", [
		'error'=>User::getError(),
		'errorRegister'=>User::getErrorRegister(),
		'registerValues'=>(isset($_SESSION['registerValues'])) ? $_SESSION['registerValues'] : ['name'=>'', 'email'=>'', 'phone'=>'']
	]);

});

$app->post("/login", function()
{

	try {
		//Tenta logar
		User::login($_POST['login'], $_POST['password']);

	} catch(Exception $e) {
		//Mostra o erro
		User::setError($e->getMessage());

	}

	header("Location: /checkout");
	exit;

});

//Logout ==================================================================================
$app->get("/logout", function()
{

	User::logout();

	header("Location: /checkout");
	exit;

});

//Registrar ================================================================================
$app->post("/register", function()
{

	//Recebe os dados já preenchidos no formulário para que não apaguem em caso de erro
	$_SESSION['registerValues'] = $_POST;

	//Valida os campos, se estão setados ou não vazios
	
	if (!isset($_POST['name']) || $_POST['name'] == '') {

		User::setErrorRegister("Preencha o seu nome.");
		header("Location: /login");
		exit;

	}
 
	if (!isset($_POST['email']) || $_POST['email'] == '') {

		User::setErrorRegister("Preencha o seu e-mail.");
		header("Location: /login");
		exit;

	}

	if (!isset($_POST['password']) || $_POST['password'] == '') {

		User::setErrorRegister("Preencha a senha.");
		header("Location: /login");
		exit;

	}

	//Verifica se o usuário informado já existe
	if (User::checkLoginExist($_POST['email']) === true) {

		User::setErrorRegister("Este endereço de e-mail já está sendo usado por outro usuário.");
		header("Location: /login");
		exit;

	}
	
	$user = new User();

	//Recebe os dados para criar um usuário
	//Passa um array pq os nomes dos campos são diferentes
	$user->setData([
		'inadmin'=>0,
		'deslogin'=>$_POST['email'],
		'desperson'=>$_POST['name'],
		'desemail'=>$_POST['email'],
		'despassword'=>$_POST['password'],
		'nrphone'=>$_POST['phone']
	]);

	$user->save();

	//Loga o usuario para ele visualizar o checkout
	User::login($_POST['email'], $_POST['password']);

	header("Location: /checkout");
	exit;

});

//Esqueceu a senha =====================================================================
$app->get("/forgot", function()
{

	$page = new Page();

	$page->setTpl("forgot");
});

$app->post("/forgot", function()
{

	//Passa false para o inadmin
	$user = User::getForgot($_POST["email"], false);

	header("Location:/forgot/sent");
	exit;

});

//ENVIADO
$app->get("/forgot/sent", function()
{

	$page = new Page();

	$page->setTpl("forgot-sent");

});

$app->get("/forgot/reset", function()
{
	//Identificação do usuário
	$user = User::validForgotDecrypt($_GET["code"]);

	$page = new Page();

	//passa um array para o template
	$page->setTpl("forgot-reset", array(
		"name"=>$user["desperson"],
		"code"=>$_GET["code"]
	));

});

//Seta que o código foi usado
$app->post("/forgot/reset", function()
{

	//Descriptografa o codigo
	$forgot = User::validForgotDecrypt($_POST["code"]);	

	//Verifica recuperação já usada
	User::setForgotUsed($forgot["idrecovery"]);
	
	$user = new User();
	
	//carrega os dados do usuário convertendo
	$user->getUser((int)$forgot["iduser"]);
	
	$password = User::getPasswordHash($_POST["password"]);
	
	//seta a nova senha informada no banco (hash)
	$user->setPassword($password);
	
	$page = new Page();
	
	$page->setTpl("forgot-reset-success");
	
});

//Cria o perfil do usuário ===================================================================
$app->get("/profile", function(){

    // Verifica se o usuário está logado
    User::verifyLogin(false);

    // Obtém o usuário da sessão
    $user = User::getFromSession();

    $page = new Page();

    // Define o modelo da página e passa informações pra o template
    $page->setTpl("profile", [
        'user'=>$user->getValues(),
        'profileMsg'=>User::getSuccess(),
        'profileError'=>User::getError()
    ]);

});

// Rota POST para atualização de dados do perfil
$app->post("/profile", function(){

    // Verifica se o usuário tá logado
    User::verifyLogin(false);

    // Verifica se o campo 'desperson' não está vazio
    if (!isset($_POST['desperson']) || $_POST['desperson'] === '') {
        User::setError("Preencha o seu nome.");
        header('Location: /profile');
        exit;
    }

    // Verifica se o campo 'desemail' não está vazio
    if (!isset($_POST['desemail']) || $_POST['desemail'] === '') {
        User::setError("Preencha o seu e-mail.");
        header('Location: /profile');
        exit;
    }

    // carrega o usuário atual da sessão
    $user = User::getFromSession();

    // Verifica se o novo e-mail é diferente do e-mail atual do usuário
    if ($_POST['desemail'] !== $user->getdesemail()) {

        // Verifica se o novo e-mail já existe em outros registros
        if (User::checkLoginExist($_POST['desemail']) === true) {
            User::setError("Este endereço de e-mail já está cadastrado.");
            header('Location: /profile');
            exit;
        }

    }

    // Define algumas informações para atualização
    $_POST['inadmin'] = $user->getinadmin();
    $_POST['despassword'] = $user->getdespassword();
    $_POST['deslogin'] = $_POST['desemail'];

    // Define os dados do usuário com os novos dados do formulário
    $user->setData($_POST);

    // Salva as informações atualizadas do usuário no banco de dados
    $user->save();

    // Define uma mensagem de sucesso
    User::setSuccess("Dados alterados com sucesso!");

    // Redireciona de volta à página de perfil
    header('Location: /profile');
    exit;

});



//Pedidos ==================================================================================
$app->get("/order/:idorder", function($idorder)
{

	//Verifica se está logado
	User::verifyLogin(false);

	$order = new Order();

	//Carrega o pedido pelo id
	$order->getOrder((int)$idorder);

	$page = new Page();

	//passa os dados para o template
	$page->setTpl("payment", [
		'order'=>$order->getValues()
	]);
});

//Rota do boleto ============================================================================
$app->get("/boleto/:idorder", function($idorder)
{

	//Verifica se está logado
	User::verifyLogin(false);

	$order = new Order();

	//Carrega o pedido pelo id
	$order->getOrder((int)$idorder);

	// DADOS DO BOLETO PARA O SEU CLIENTE
	$dias_de_prazo_para_pagamento = 10;
	$taxa_boleto = 5.00;
	$data_venc = date("d/m/Y", time() + ($dias_de_prazo_para_pagamento * 86400));  // Prazo de X dias OU informe data: "13/04/2006"; 

	$valor_cobrado = formatPrice($order->getvltotal()); // Valor - REGRA: Sem pontos na milhar e tanto faz com "." ou "," ou com 1 ou 2 ou sem casa decimal
	$valor_cobrado = str_replace(".", "", $valor_cobrado);
	$valor_cobrado = str_replace(",", ".",$valor_cobrado);
	$valor_boleto=number_format($valor_cobrado+$taxa_boleto, 2, ',', '');

	$dadosboleto["nosso_numero"] = $order->getidorder();  // Nosso numero - REGRA: Máximo de 8 caracteres!
	$dadosboleto["numero_documento"] = $order->getidorder();	// Num do pedido ou nosso numero
	$dadosboleto["data_vencimento"] = $data_venc; // Data de Vencimento do Boleto - REGRA: Formato DD/MM/AAAA
	$dadosboleto["data_documento"] = date("d/m/Y"); // Data de emissão do Boleto
	$dadosboleto["data_processamento"] = date("d/m/Y"); // Data de processamento do boleto (opcional)
	$dadosboleto["valor_boleto"] = $valor_boleto; 	// Valor do Boleto - REGRA: Com vírgula e sempre com duas casas depois da virgula

	// DADOS DO SEU CLIENTE
	$dadosboleto["sacado"] = $order->getdesperson();
	$dadosboleto["endereco1"] = $order->getdesaddress() . " " . $order->getdesdistrict();
	$dadosboleto["endereco2"] = $order->getdescity() . " - " . $order->getdesstate() . " - " . $order->getdescountry() . " -  CEP: " . $order->getdeszipcode();

	// INFORMACOES PARA O CLIENTE
	$dadosboleto["demonstrativo1"] = "Pagamento de Compra na Loja Hcode E-commerce";
	$dadosboleto["demonstrativo2"] = "Taxa bancária - R$ 0,00";
	$dadosboleto["demonstrativo3"] = "";
	$dadosboleto["instrucoes1"] = "- Sr. Caixa, cobrar multa de 2% após o vencimento";
	$dadosboleto["instrucoes2"] = "- Receber até 10 dias após o vencimento";
	$dadosboleto["instrucoes3"] = "- Em caso de dúvidas entre em contato conosco: suporte@hcode.com.br";
	$dadosboleto["instrucoes4"] = "&nbsp; Emitido pelo sistema Projeto Loja Hcode E-commerce - www.hcode.com.br";

	// DADOS OPCIONAIS DE ACORDO COM O BANCO OU CLIENTE
	$dadosboleto["quantidade"] = "";
	$dadosboleto["valor_unitario"] = "";
	$dadosboleto["aceite"] = "";		
	$dadosboleto["especie"] = "R$";
	$dadosboleto["especie_doc"] = "";


	// ---------------------- DADOS FIXOS DE CONFIGURAÇÃO DO SEU BOLETO --------------- //


	// DADOS DA SUA CONTA - ITAÚ
	$dadosboleto["agencia"] = "1690"; // Num da agencia, sem digito
	$dadosboleto["conta"] = "48781";	// Num da conta, sem digito
	$dadosboleto["conta_dv"] = "2"; 	// Digito do Num da conta

	// DADOS PERSONALIZADOS - ITAÚ
	$dadosboleto["carteira"] = "175";  // Código da Carteira: pode ser 175, 174, 104, 109, 178, ou 157

	// SEUS DADOS
	$dadosboleto["identificacao"] = "Hcode Treinamentos";
	$dadosboleto["cpf_cnpj"] = "24.700.731/0001-08";
	$dadosboleto["endereco"] = "Rua Ademar Saraiva Leão, 234 - Alvarenga, 09853-120";
	$dadosboleto["cidade_uf"] = "São Bernardo do Campo - SP";
	$dadosboleto["cedente"] = "HCODE TREINAMENTOS LTDA - ME";

	// NÃO ALTERAR!
	$path = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "res" . DIRECTORY_SEPARATOR . "boletophp" . DIRECTORY_SEPARATOR . "include" . DIRECTORY_SEPARATOR;

	require_once($path . "funcoes_itau.php");
	require_once($path . "layout_itau.php");

});

//Meus pedidos ===========================================================
$app->get("/profile/orders", function()
{
	//Verifica se está logado
	User::verifyLogin(false);

	//pega o usuário da sessão
	$user = User::getFromSession();

	$page = new Page();

	//Passa os dados para o template
	$page->setTpl("profile-orders", [
		'orders'=>$user->getOrders()
	]);

});

//Detalhes do pedido pelo id
$app->get("/profile/orders/:idorder", function($idorder){
    
    // Verifica se tá logado
    User::verifyLogin(false);

    $order = new Order();

    // Recebe um pedido específico com base no id
    $order->getOrder((int)$idorder);

    $cart = new Cart();

    // Obtém informações sobre o carrinho associado ao pedido.
    $cart->get((int)$order->getidcart());

    // Calcula o total do carrinho.
    $cart->getCalculateTotal();
	
    $page = new Page();

    // Define o modelo da página para renderizar a página 'profile-orders-detail' e passa dados para a renderização.
    $page->setTpl("profile-orders-detail", [
        'order' => $order->getValues(),
        'cart' => $cart->getValues(),
        'products' => $cart->getProducts()
    ]);	
});

// Mudança de senha ===============================
$app->get("/profile/change-password", function(){

    // Verifica se o usuário está logado
    User::verifyLogin(false);

    $page = new Page();

    //Passa as informações para o template
    $page->setTpl("profile-change-password", [
        'changePassError'=>User::getError(),
        'changePassSuccess'=>User::getSuccess()
    ]);

});

// Rota POST para a mudança de senha
$app->post("/profile/change-password", function(){

    // Verifica se o usuário está logado
    User::verifyLogin(false);

    // Verifica se o campo 'current_pass' não está vazio
    if (!isset($_POST['current_pass']) || $_POST['current_pass'] === '') {

        // Define uma mensagem de erro e redireciona de volta à página de mudança de senha
        User::setError("Digite a senha atual.");
        header("Location: /profile/change-password");
        exit;

    }

    // Carregao usuário da sessão
    $user = User::getFromSession();

    // Verifica se a senha atual fornecida corresponde à senha armazenada no banco de dados
    if (!password_verify($_POST['current_pass'], $user->getdespassword())) {

        // Define uma mensagem de erro e redireciona de volta à página de mudança de senha
        User::setError("A senha está inválida.");
        header("Location: /profile/change-password");
        exit;

    }

    // Define a nova senha para o usuário
    $user->setdespassword($_POST['new_pass']);

    // Atualiza os dados do usuário no banco de dados
    $user->update();

    // Define uma mensagem de sucesso
    User::setSuccess("Senha alterada com sucesso.");

    // Redireciona de volta à página de mudança de senha
    header("Location: /profile/change-password");
    exit;

});


?>