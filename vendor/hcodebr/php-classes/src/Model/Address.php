<?php 

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;

class Address extends Model {

	const SESSION_ERROR = "AddressError";

    //Recebe o número de cep a ser pesquisado
	public static function getCEP($nrcep)
	{
        //Garante que somente numeros serão enviados
		$nrcep = str_replace("-", "", $nrcep);

        //Informa o php que vai iniciar o rastreio de url
		$ch = curl_init();

        //Opções da chamada
		curl_setopt($ch, CURLOPT_URL, "http://viacep.com.br/ws/$nrcep/json/");

        
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //Se a opção deve ser devolvida (espera retorno)
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //Se exige autenticação SSL

        //Faz um decode da resposta
		$data = json_decode(curl_exec($ch), true); //parametro true ja vem e array e não em objeto

        //fecha o ponteiro curl
		curl_close($ch);

        //retorna os dados
		return $data;

	}

    //Os nomes dos campos do web service são diferentes do banco
	public function loadFromCEP($nrcep)
	{

        //Recebe o cep
		$data = Address::getCEP($nrcep);

        //Verifica se logradouro está definido ou não é vazio
		if (isset($data['logradouro']) && $data['logradouro']) {

            //Carrega no proprio objeto (lá na rota, evita o return) todos os campos no banco de dados (vão receber do web service os campos correspondetes)
			$this->setdesaddress($data['logradouro']);
			$this->setdescomplement($data['complemento']);
			$this->setdesdistrict($data['bairro']);
			$this->setdescity($data['localidade']);
			$this->setdesstate($data['uf']);
			$this->setdescountry('Brasil');
			$this->setdeszipcode($nrcep);

		}

	}

	public function saveAddress()
	{
		$sql = new Sql();

		$results = $sql->select("CALL sp_addresses_save(:idaddress, :idperson, :desaddress, :desnumber, :descomplement, :descity, :desstate, :descountry, :deszipcode, :desdistrict)", [
			':idaddress'=>$this->getidaddress(),
			':idperson'=>$this->getidperson(),
			':desaddress'=>$this->getdesaddress(),
			':desnumber'=>$this->getdesnumber(),
			':descomplement'=>$this->getdescomplement(),
			':descity'=>$this->getdescity(),
			':desstate'=>$this->getdesstate(),
			':descountry'=>$this->getdescountry(),
			':deszipcode'=>$this->getdeszipcode(),
			':desdistrict'=>$this->getdesdistrict()
		]);

		//Se não estávazio
		if (count($results) > 0) {
			//Retorna os valores para o Objeto
			$this->setData($results[0]);
			//var_dump($results[0]);exit;
		}

	}

	public static function setMsgError($msg)
	{

		$_SESSION[Address::SESSION_ERROR] = $msg;

	}

	public static function getMsgError()
	{

		$msg = (isset($_SESSION[Address::SESSION_ERROR])) ? $_SESSION[Address::SESSION_ERROR] : "";

		Address::clearMsgError();

		return $msg;

	}

	public static function clearMsgError()
	{

		$_SESSION[Address::SESSION_ERROR] = NULL;

	}

}

 ?>