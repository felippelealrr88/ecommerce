<?php
namespace Hcode\Model;

use Exception;
use \Hcode\DB\Sql;
use Hcode\Model;
use Hcode\Model\User;

class Cart extends Model{

    //constante com a sessão do carrinho 
    const SESSION = "Cart";
    const SESSION_ERROR = "CartError";

    //Verifica o carrinho (se já existe ou não)
    public static function getFromSession(){

        $cart = new Cart();

        //Verifica SE O CARRINHO EXISTE na sessão e SE EXISTE ID do carrinho (se for vazio o cast para inteiro retorna zero)
        if (isset($_SESSION[Cart::SESSION]) && (int)$_SESSION[Cart::SESSION]['idcart'] > 0) {
           
            //carrega os dados do banco
            $cart->getCart((int)$_SESSION[Cart::SESSION]['idcart']);

        }else{

            //Se NÃO EXISTE na sessão tenta recuperar do banco com o sessionId do usuário
            $cart->getFromSessionId();
            

            //Se NÃO conseguiu carregar do banco então cria um novo carrinho
            if (!(int)$cart->getidcart() > 0) {
                
                //cria um carrinho novo
                $data = [
                    'dessessionid'=>session_id()
                ];

                //verifica se está logado passando false (Não admin)
                if (User::checkLogin(false)) {

                    //Já tem um objeto instanciado de User
                    //Traz o usuário da sessão
                    $user = User::getFromSession();

                    //Passa o id do usuário para $data (cria o carrinho no banco já com o id do usuário)
                    $data['iduser'] = $user->getiduser();

                }

                //colocata $data dentro do objeto Cart
                $cart->setData($data);

                $cart->saveCart();
                
                //coloca o novo carrinho na sessão (não existe ainda)
                $cart->setToSession();
            }
        }
        return $cart;
    }

    //cria uma sessão para um usuário ainda não logado (não estático por causa do $this)
    public function setToSession(){

        //Coloca o carrinho na sessão
        $_SESSION[Cart::SESSION] = $this->getValues();

    }

    //Traz do banco usando o id de sessão do usuário
    public function getFromSessionId(){
        
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_carts WHERE dessessionid = :dessessionid", [
            ':dessessionid'=>session_id()
        ]);

        //verifica se é vazio
        if (count($results) > 0) {
            //retorna para o próprio objeto
            $this->setData($results[0]);
        }
    }

    //carrega o carrinho do banco
    public function getCart(int $idcart){
        
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_carts WHERE idcart = :idcart", [
            ':idcart'=>$idcart
        ]);

        //verifica se é vazio
        if (count($results) > 0) {
            //retorna para o próprio objeto
            $this->setData($results[0]);
        }
    }

    public function saveCart(){

        $sql = new Sql();

        $results = $sql->select("CALL sp_carts_save(:idcart, :dessessionid, :iduser, :deszipcode, :vlfreight, :nrdays)", [
            ':idcart'=>$this->getidcart(),
            ':dessessionid'=>$this->getdessessionid(),
            ':iduser'=>$this->getiduser(),
            ':deszipcode'=>$this->getdeszipcode(),
            ':vlfreight'=>$this->getvlfreight(),
            ':nrdays'=>$this->getnrdays()
        ]);

        //Retorna para o própio objeto com setData
        $this->setData($results[0]);
    }

    //Adciona um produto
    public function addProduct(Product $product){

        $sql = new Sql();

        $sql->query("INSERT INTO tb_cartsproducts (idcart, idproduct) VALUES (:idcart, :idproduct)",[
            ':idcart'=>$this->getidcart(),
            ':idproduct'=>$product->getidproduct()
        ]);

        $this->getCalculateTotal();
        
    }

    //Remove o produto mas não apaga (para posterior análise)
    public function removeProduct(Product $product, $all = false){
        
        $sql = new Sql();

        //remove todos
        if ($all) {
            $sql->query("UPDATE tb_cartsproducts SET dtremoved = NOW() WHERE idcart = :idcart AND idproduct = :idproduct AND dtremoved IS NULL", [
                ':idcart'=>$this->getidcart(),
                ':idproduct'=>$product->getidproduct()
            ]);
        } else {

            //Remove apenas um produto
            $sql->query("UPDATE tb_cartsproducts SET dtremoved = NOW() WHERE idcart = :idcart AND idproduct = :idproduct AND dtremoved IS NULL LIMIT 1", [
                ':idcart'=>$this->getidcart(),
                ':idproduct'=>$product->getidproduct()
            ]);
        }
        $this->getCalculateTotal();

    }

    //Lista os produtos que já foram add ao carrinho
    public function getProducts(){

        $sql = new Sql();

        $rows = $sql->select("SELECT b.idproduct, b.desproduct, b.vlprice, b.vlwidth, b.vlheight, b.vllength, b.vlweight, b.desurl, COUNT(*) AS nrqtd, SUM(b.
        vlprice) AS vltotal
        FROM tb_cartsproducts a
        INNER JOIN tb_products b ON a.idproduct = b.idproduct
        WHERE a.idcart = :idcart AND a.dtremoved IS NULL
        GROUP BY b.idproduct, b.desproduct, b.vlprice, b.vlwidth, b.vlheight, b.vllength, b.vlweight, b.desurl
        ORDER BY b.desproduct
        ", [
            ':idcart'=>$this->getidcart()
        ]);

        //verifica as figuras de cada produto com checklist
        return Product::checkList($rows);
        
    }

    public function getProductsTotals(){

		$sql = new Sql();

        //Soma todas os campos necessários
		$results = $sql->select("
			SELECT SUM(vlprice) AS vlprice, SUM(vlwidth) AS vlwidth, SUM(vlheight) AS vlheight, SUM(vllength) AS vllength, SUM(vlweight) AS vlweight, COUNT(*) AS nrqtd
			FROM tb_products a
			INNER JOIN tb_cartsproducts b ON a.idproduct = b.idproduct
			WHERE b.idcart = :idcart AND dtremoved IS NULL;
		", [
			':idcart'=>$this->getidcart()
		]);

        //Tratamento se for vazio
		if (count($results) > 0) {
			return $results[0];
		} else {
			return [];
		}

	}

    public function setFreight($nrzipcode){

        //validar que somente números serão passados sem traço
		$nrzipcode = str_replace('-', '', $nrzipcode);

        // $totals recebe os totais dos produtos do carrinho
		$totals = $this->getProductsTotals();

        //Verifica se tem produtos dentro do carrinho
		if ($totals['nrqtd'] > 0) {

            //Regra de negócio do WebService
            //Comprimento
			if ($totals['vllength'] < 15) $totals['vllength'] = 15;
            if ($totals['vllength'] > 100) $totals['vllength'] = 100;

            //Largura
            if ($totals['vlwidth'] < 10) $totals['vlwidth'] = 10;
            if ($totals['vlwidth'] > 100) $totals['vlwidth'] = 100;
            
            //Altura
            if ($totals['vlheight'] < 2) $totals['vlheight'] = 2;
            if ($totals['vlheight'] < 2) $totals['vlheight'] = 2;

            //Passa as variáveis na query string ? (Espera um array)
			$qs = http_build_query([
				'nCdEmpresa'=>'',
				'sDsSenha'=>'',
				'nCdServico'=>'40010',
				'sCepOrigem'=>'09853120',
				'sCepDestino'=>$nrzipcode,
				'nVlPeso'=>$totals['vlweight'],
				'nCdFormato'=>'1',
				'nVlComprimento'=>$totals['vllength'],
				'nVlAltura'=>$totals['vlheight'],
				'nVlLargura'=>$totals['vlwidth'],
				'nVlDiametro'=>'0',
				'sCdMaoPropria'=>'S',
				'nVlValorDeclarado'=>$totals['vlprice'],
				'sCdAvisoRecebimento'=>'S'
			]);

            //Passa as informações para o webservice dos correios (XML)
			$xml = simplexml_load_file("http://ws.correios.com.br/calculador/CalcPrecoPrazo.asmx/CalcPrecoPrazo?".$qs);

            //echo json_encode($xml); exit;
            //Recebe o resultado da consulta (Objeto $result)
			$result = $xml->Servicos->cServico;

            //Verifica se houve erro
			if ($result->MsgErro != '') {

                //Define a mensagem de erro retornada do WebService
				Cart::setMsgError($result->MsgErro);

			} else {

                //Se não houve erro limpa
				Cart::clearMsgError();

			}

            //Seta o Prazo, Valor e Cep nas variáveis de Cart (banco)
			$this->setnrdays($result->PrazoEntrega);
			$this->setvlfreight(Cart::formatValueToDecimal($result->Valor));
			$this->setdeszipcode($nrzipcode);

			$this->saveCart();

            //caso precise de mais alguma informação fora desse método
			return $result;
 
		} else {

		}
	}

    public static function formatValueToDecimal($value):float{
        //Formata em decimal
		$value = str_replace('.', '', $value);
		return str_replace(',', '.', $value);

	}
    
	public static function setMsgError($msg){
        
        //Pega os erros na sessão
		$_SESSION[Cart::SESSION_ERROR] = $msg;

	}

	public static function getMsgError(){

        //Se estiver definido retorna ele mesmo se não retorna ""
		$msg = (isset($_SESSION[Cart::SESSION_ERROR])) ? $_SESSION[Cart::SESSION_ERROR] : "";

        //limpa a mensagem de erro
		Cart::clearMsgError();

        //retorna a variável
		return $msg;
	}

	public static function clearMsgError(){

		$_SESSION[Cart::SESSION_ERROR] = NULL;

	}

	public function updateFreight(){

        //Se não for vazio
		if ($this->getdeszipcode() != '') {

			$this->setFreight($this->getdeszipcode());

		}

	}

    public function getValues(){

		$this->getCalculateTotal();

		return parent::getValues();

	}

	public function getCalculateTotal(){

		$this->updateFreight();

        //recebe todos os valores totais do carrinho
		$totals = $this->getProductsTotals();

        //Campos que não existem no carrinho

        //coloca no carrinho subtotal
		$this->setvlsubtotal($totals['vlprice']);

        //coloca no carrinho o total geral
		$this->setvltotal($totals['vlprice'] + (float)$this->getvlfreight());

	}


}

?>