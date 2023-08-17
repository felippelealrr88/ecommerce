<?php

use Hcode\Model\Cart;
use Hcode\Model\Product;
use \Hcode\Page;
use \Hcode\Model\Category;
use \Hcode\Model\Address;
use Hcode\Model\User;
use Hcode\Model\Order;
use Hcode\Model\OrderStatus;


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

	//var_dump($cart); exit;
	//passa as informações para o template
	$page->setTpl("cart", [
		'cart'=>$cart->getValues(),
		'products'=>$cart->getProducts(),
		'error'=>Cart::getMsgError()
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

//calculo do frete
$app->post("/cart/freight", function(){

	//Pega o carrinho da sessão
	$cart = Cart::getFromSession();

	//Manda o cep por post
	$cart->setFreight($_POST['zipcode']);

	header("Location: /cart");
	exit;

});

//checkput do carrinho
$app->get("/checkout", function(){

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

	//Usuario não é administrador
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

	$address->setData($_POST);

	$address->save();

	//Pega o carrinho
	$cart = Cart::getFromSession();

	//Força o calculo do total
	$cart->getCalculateTotal();

	$order = new Order();

	//Manda os dados para o template
	$order->setData([
		'idcart'=>$cart->getidcart(),
		'idaddress'=>$address->getidaddress(),
		'iduser'=>$user->getiduser(),
		'idstatus'=>OrderStatus::EM_ABERTO,
		'vltotal'=>$cart->getvltotal()
	]);

	$order->save();

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

$app->get("/login", function(){

	$page = new Page();

	$page->setTpl("login", [
		'error'=>User::getError(),
		'errorRegister'=>User::getErrorRegister(),
		'registerValues'=>(isset($_SESSION['registerValues'])) ? $_SESSION['registerValues'] : ['name'=>'', 'email'=>'', 'phone'=>'']
	]);

});

$app->post("/login", function(){

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


$app->get("/logout", function() {

	User::logout();

	header("Location: /checkout");
	exit;

});

$app->post("/register", function(){

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

$app->get("/forgot", function(){

	$page = new Page();

	$page->setTpl("forgot");
});

$app->post("/forgot", function(){

	//Passa false para o inadmin
	$user = User::getForgot($_POST["email"], false);

	header("Location:/forgot/sent");
	exit;

});

//ENVIADO
$app->get("/forgot/sent", function(){

	$page = new Page();

	$page->setTpl("forgot-sent");

});

$app->get("/forgot/reset", function(){
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

$app->get("/profile", function()
{

	//Força o login não admin
	User::verifyLogin(false);
	
	//Pega o usuário da sessão
	$user = User::getFromSession();
	
	$page = new Page();
	
	//Passa os dados para o template 'profile'
	$page->setTpl("profile", [
			'user'=>$user->getValues(),
			'profileMsg'=>User::getSuccess(),
			'profileError'=>User::getError()
	]);
	
});
	
$app->post("/profile", function(){

	//Força o login não admin
	User::verifyLogin(false);

	//Validações de erros

	if (!isset($_POST['desperson']) || $_POST['desperson'] === '') {
		User::setError("Preencha o seu nome.");
		header('Location: /profile');
		exit;
	}

	if (!isset($_POST['desemail']) || $_POST['desemail'] === '') {
		User::setError("Preencha o seu e-mail.");
		header('Location: /profile');
		exit;
	}

	//Pega o usuário da Sessão
	$user = User::getFromSession();

	//Se ele alterou o email
	if ($_POST['desemail'] !== $user->getdesemail()) {

		//Verifica se o email está sendo usado
		if (User::checkLoginExist($_POST['desemail']) === true) {

			User::setError("Este endereço de e-mail já está cadastrado.");
			header('Location: /profile');
			exit;

		}

	}

	//Ignora o que o formulário está enviando e pega no banco
	//Sobrescreve com os dados já presentes no Objeto
	$_POST['inadmin'] = $user->getinadmin();
	$_POST['despassword'] = $user->getdespassword();
	$_POST['deslogin'] = $_POST['desemail'];

	$user->setData($_POST);

	$user->save();

	User::setSuccess("Dados alterados com sucesso!");

	header('Location: /profile');
	exit;

});	

?>