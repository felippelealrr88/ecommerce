<?php 

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Model\Cart;

class Order extends Model 
{

	// Constantes para mensagens de sucesso e erro.
	const SUCCESS = "Order-Success";
	const ERROR = "Order-Error";

	public function saveOrder()
	{
		$sql = new Sql();

		// Chama um procedimento armazenado para salvar um pedido.
		$results = $sql->select("CALL sp_orders_save(:idorder, :idcart, :iduser, :idstatus, :idaddress, :vltotal)", [
			':idorder'=>$this->getidorder(),
			':idcart'=>$this->getidcart(),
			':iduser'=>$this->getiduser(),
			':idstatus'=>$this->getidstatus(),
			':idaddress'=>$this->getidaddress(),
			':vltotal'=>$this->getvltotal()
		]);

		// Se houver resultados, define os dados do primeiro resultado como os dados desta instância.
		if (count($results) > 0) {
			$this->setData($results[0]);
		}
	}
//=======================================================================
	public function getOrder($idorder)
	{
		$sql = new Sql();

		// Executa uma consulta para obter informações sobre um pedido
		$results = $sql->select("
			SELECT * 
			FROM tb_orders a 
			INNER JOIN tb_ordersstatus b USING(idstatus) 
			INNER JOIN tb_carts c USING(idcart)
			INNER JOIN tb_users d ON d.iduser = a.iduser
			INNER JOIN tb_addresses e USING(idaddress)
			INNER JOIN tb_persons f ON f.idperson = d.idperson
			WHERE a.idorder = :idorder
		", [
			':idorder'=>$idorder
		]);

		// Se houver resultados, retorna o primeiro resultado
		if (count($results) > 0) {
			$this->setData($results[0]);
		}
	}
//=====================================================================
	public static function listAll()
	{
		$sql = new Sql();

		return $sql->select("
			SELECT * 
			FROM tb_orders a 
			INNER JOIN tb_ordersstatus b USING(idstatus) 
			INNER JOIN tb_carts c USING(idcart)
			INNER JOIN tb_users d ON d.iduser = a.iduser
			INNER JOIN tb_addresses e USING(idaddress)
			INNER JOIN tb_persons f ON f.idperson = d.idperson
			ORDER BY a.dtregister DESC
		");
	}
//=================================================================
	public function delete()
	{
		$sql = new Sql(); 

		// Executa uma consulta para excluir um pedido com base no seu "idorder".
		$sql->query("DELETE FROM tb_orders WHERE idorder = :idorder", [
			':idorder'=>$this->getidorder()
		]);
	}

//==============================================================	
	public function getCart(): Cart
	{
		$cart = new Cart();

		// Obtém o carrinho associado a este pedido e retorna como um objeto Cart.
		$cart->getCart((int)$this->getidcart());

		return $cart;
	}

	// Métodos estáticos para gerenciamento de mensagens de erro e sucesso.
	public static function setError($msg)
	{
		// Define uma mensagem de erro na sessão.
		$_SESSION[Order::ERROR] = $msg;
	}

	public static function getError()
	{
		// Obtém a mensagem de erro da sessão e a retorna.
		$msg = (isset($_SESSION[Order::ERROR]) && $_SESSION[Order::ERROR]) ? $_SESSION[Order::ERROR] : '';

		// Limpa a mensagem de erro.
		Order::clearError();

		return $msg;
	}

	public static function clearError()
	{
		// Limpa a mensagem de erro na sessão.
		$_SESSION[Order::ERROR] = NULL;
	}

	public static function setSuccess($msg)
	{
		// Define uma mensagem de sucesso na sessão.
		$_SESSION[Order::SUCCESS] = $msg;
	}

	public static function getSuccess()
	{
		// Obtém a mensagem de sucesso da sessão e a retorna.
		$msg = (isset($_SESSION[Order::SUCCESS]) && $_SESSION[Order::SUCCESS]) ? $_SESSION[Order::SUCCESS] : '';

		// Limpa a mensagem de sucesso.
		Order::clearSuccess();

		return $msg;
	}

	public static function clearSuccess()
	{
		// Limpa a mensagem de sucesso na sessão.
		$_SESSION[Order::SUCCESS] = NULL;
	}
//===============================================================================
	public static function getPage($page = 1, $itemsPerPage = 10)
	{
		// Calcula o índice de início com base na página e no número de itens por página.
		$start = ($page - 1) * $itemsPerPage;

		$sql = new Sql();

		$results = $sql->select("
			SELECT SQL_CALC_FOUND_ROWS *
			FROM tb_orders a 
			INNER JOIN tb_ordersstatus b USING(idstatus) 
			INNER JOIN tb_carts c USING(idcart)
			INNER JOIN tb_users d ON d.iduser = a.iduser
			INNER JOIN tb_addresses e USING(idaddress)
			INNER JOIN tb_persons f ON f.idperson = d.idperson
			ORDER BY a.dtregister DESC
			LIMIT $start, $itemsPerPage;
		");

		// Obtém o número total de resultados (para paginação).
		$resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal;");

		return [
			'data'=>$results,
			'total'=>(int)$resultTotal[0]["nrtotal"],
			'pages'=>ceil($resultTotal[0]["nrtotal"] / $itemsPerPage)
		];
	}

//=======================================================================================
	public static function getPageSearch($search, $page = 1, $itemsPerPage = 10)
	{
		// Calcula o índice de início com base na página e no número de itens por página.
		$start = ($page - 1) * $itemsPerPage;

		$sql = new Sql(); 

		$results = $sql->select("
			SELECT SQL_CALC_FOUND_ROWS *
			FROM tb_orders a 
			INNER JOIN tb_ordersstatus b USING(idstatus) 
			INNER JOIN tb_carts c USING(idcart)
			INNER JOIN tb_users d ON d.iduser = a.iduser
			INNER JOIN tb_addresses e USING(idaddress)
			INNER JOIN tb_persons f ON f.idperson = d.idperson
			WHERE a.idorder = :id OR f.desperson LIKE :search
			ORDER BY a.dtregister DESC
			LIMIT $start, $itemsPerPage;
		", [
			':search'=>'%'.$search.'%',
			':id'=>$search
		]);

		// Obtém o número total de resultados (para paginação).
		$resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal;");

		return [
			'data'=>$results,
			'total'=>(int)$resultTotal[0]["nrtotal"],
			'pages'=>ceil($resultTotal[0]["nrtotal"] / $itemsPerPage)
		];
	}
}

?>