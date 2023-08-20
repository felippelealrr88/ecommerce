<?php
namespace Hcode\Model;

use Exception;
use \Hcode\DB\Sql;
use Hcode\Mailer;
use Hcode\Model;

class User extends Model{

        const SESSION = "User";
        const SECRET = "HcodePhp7_Secret";
        const SECRET_IV = "HcodePhp7_Secret_IV";
        const ERROR = "UserError";
	    const ERROR_REGISTER = "UserErrorRegister";
	    const SUCCESS = "UserSucesss";

        protected $fields = [
            "iduser", "idperson", "deslogin", "despassword", "inadmin", "dtergister"
        ];    
//======================================================================================================
    
    //Saber se o usuário tá logado    
    public static function getFromSession(){

        $user = new User();
        
        //verifica se a sessão está definida
        if (isset($_SESSION[User::SESSION]) && (int)$_SESSION[User::SESSION]['iduser'] > 0) {
            
            //seta as informações da sessão
            $user->setData($_SESSION[User::SESSION]);
        }

        return $user;
    }
    
    //verifica o login
    public static function checkLogin($inadmin = true){

		if (
			!isset($_SESSION[User::SESSION]) //Se a sessão não está definida
			||
			!$_SESSION[User::SESSION] //Definida mas vazia
			||
			!(int)$_SESSION[User::SESSION]["iduser"] > 0 //iduser !> 0
		) {
			//Não está logado
			return false;

		} else {

            //É usuário da administração? E o a rota é da administrção?
			if ($inadmin === true && (bool)$_SESSION[User::SESSION]['inadmin'] === true) {

				return true;
            
            //A rota não é da administração
			} else if ($inadmin === false) {

				return true;

			} else {
                //Não tá logado
				return false;
			}
		}
	}

    public static function login($login, $password):User{
        
        $sql = new Sql();
        $results = $sql->select("SELECT * FROM tb_users WHERE deslogin = :LOGIN", array(
            ":LOGIN"=>$login
        ));
    //Se nenhum resultado for encontrado
        if (count($results) === 0) {
            throw new \Exception(" Usuário ou Senha incorretos! ");
        }
            
        $data = $results[0]; //primeiro registro encontrado
    

    //verifica a senha digitada ($password) e a senha do banco ($data[despassword])
    //retorna um boolean
    if(password_verify($password, $data["despassword"])){
        
        $user = new User();

        //$data['desperson'] = utf8_encode($data['desperson']);

        //chama o setData de Model
        $user->setData($data);

        $_SESSION[User::SESSION] = $user->getValues();

        return $user;


    }else{
        throw new \Exception(" Usuário ou Senha incorretos! ");

    }    
    } 
    
//===============================================================================================

    public static function verifyLogin($inadmin = true){

        if (!User::checkLogin($inadmin)) {

            if ($inadmin) {
                header("Location: /admin/login");
            } else {
                header("Location: /login");
            }
            exit;

        }

}

//================================================================================================

    public static function logout()
	{

		$_SESSION[User::SESSION] = NULL;

	}

//==============================================================================================  

    public static function listAll(){

        $sql = new Sql();
        return $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) ORDER BY b.desperson");


    }

//=================================================================================================  

    public function getUser($iduser)
	{

		$sql = new Sql();
        //carrega o resultado do banco
		$results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) WHERE a.iduser = :iduser", array(
			":iduser"=>$iduser));

		$data = $results[0];

		//$data['desperson'] = utf8_encode($data['desperson']);

        //seta o primeiro registro no objeto    
		$this->setData($data);

	}

//=====================================================================================================

    public function save(){
        
        $sql = new Sql();
        $results = $sql->select("CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)",array(
            ":desperson"=>$this->getdesperson(),
            ":deslogin"=>$this->getdeslogin(),
            ":despassword"=>User::getPasswordHash($this->getdespassword()),
            ":desemail"=>$this->getdesemail(),
            ":nrphone"=>$this->getnrphone(),
            ":inadmin"=>$this->getinadmin()
        ));

       //seta no proprio objeto     
       $this->setData($results[0]);

    }

//======================================================================================================

    public function update(){
        
        $sql = new Sql();
        $results = $sql->select("CALL sp_usersupdate_save( :iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)",array(
            ":iduser"=>$this->getiduser(),
            ":desperson"=>utf8_decode($this->getdesperson()),
            ":deslogin"=>$this->getdeslogin(),
            ":despassword"=>User::getPasswordHash($this->getdespassword()),
            ":desemail"=>$this->getdesemail(),
            ":nrphone"=>$this->getnrphone(),
            ":inadmin"=>$this->getinadmin()
        ));

       //seta no proprio objeto     
       $this->setData($results[0]);

    }

//============================================================================================================

    public function delete(){
       
        $sql = new Sql();

        $sql->query("CALL sp_users_delete(:iduser)", array(
            ":iduser"=>$this->getiduser()
        ));

    }

//========================================================================================================

public static function getForgot($email, $inadmin = true)
{

    $sql = new Sql();

    //Verifica se o email foi cadastrado
    $results = $sql->select("
        SELECT *
        FROM tb_persons a
        INNER JOIN tb_users b USING(idperson)
        WHERE a.desemail = :email;
    ", array(
        ":email"=>$email
    ));

    //Valida se foi encontrado
    if (count($results) === 0)
    {

        throw new \Exception("Não foi possível recuperar a senha.");

    }
    else
    {

        $data = $results[0];

        //Procedure que sanva info das tentativas de recuperação
        $results2 = $sql->select("CALL sp_userspasswordsrecoveries_create(:iduser, :desip)", array(
            ":iduser"=>$data['iduser'],
            ":desip"=>$_SERVER['REMOTE_ADDR']
        ));

        //Verifica se criou
        if (count($results2) === 0)
        {

            throw new \Exception("Não foi possível recuperar a senha.");

        }
        else
        {

            $dataRecovery = $results2[0];

            //criptografa o usuário para mandar o email (em base64)
            
            $code = openssl_encrypt($dataRecovery['idrecovery'], 'AES-128-CBC', pack("a16", User::SECRET), 0, pack("a16", User::SECRET_IV));

            $code = base64_encode($code);

            //Verifica se é administrador para esconder a rota do admin
            if ($inadmin === true) {

                $link = "http://www.hcodecommerce.com.br/admin/forgot/reset?code=$code";

            } else {

                $link = "http://www.hcodecommerce.com.br/forgot/reset?code=$code";
                
            }				

            //Usar o phpmailer para mandar o email
            $mailer = new Mailer($data['desemail'], $data['desperson'], "Redefinir senha da Hcode Store", "forgot", array(
                "name"=>$data['desperson'],
                "link"=>$link
            ));				

            $mailer->send();

            return $link;

        }

    }

}

//=====================================================================================================

public static function validForgotDecrypt($code)
	{

		//Descriptografar o id
		$code = base64_decode($code);

		$idrecovery = openssl_decrypt($code, 'AES-128-CBC', pack("a16", User::SECRET), 0, pack("a16", User::SECRET_IV));

        //regras de verificação no banco (regras de validação do recovery)
		$sql = new Sql();

		$results = $sql->select("
			SELECT *
			FROM tb_userspasswordsrecoveries a
			INNER JOIN tb_users b USING(iduser)
			INNER JOIN tb_persons c USING(idperson)
			WHERE
				a.idrecovery = :idrecovery
				AND
				a.dtrecovery IS NULL
				AND
				DATE_ADD(a.dtregister, INTERVAL 1 HOUR) >= NOW();
		", array(
			":idrecovery"=>$idrecovery
		));

		if (count($results) === 0)
		{
			throw new \Exception("Não foi possível recuperar a senha.");
		}
		else
		{

			return $results[0];

		}

	}

//==========================================================================================

public static function setForgotUsed($idrecovery)
{

    $sql = new Sql();

    //Verifica se o link de recuperação já foi usado
    $sql->query("UPDATE tb_userspasswordsrecoveries SET dtrecovery = NOW() WHERE idrecovery = :idrecovery", array(
        ":idrecovery"=>$idrecovery
    ));

}

public function setPassword($password)
{

    $sql = new Sql();

    //Faz um update na senha
    $sql->query("UPDATE tb_users SET despassword = :password WHERE iduser = :iduser", array(
        ":password"=>$password,
        ":iduser"=>$this->getiduser()
    ));

}
//=======================================================================================

    public static function setError($msg){

        //Recebe a msg e coloca dentro de uma sessão
		$_SESSION[User::ERROR] = $msg;

	}

	public static function getError(){

        //Pega o erro da sessão
        //verifica se está definido, Se não é vazio e retorna a msg de erro
		$msg = (isset($_SESSION[User::ERROR]) && $_SESSION[User::ERROR]) ? $_SESSION[User::ERROR] : '';

		User::clearError();

		return $msg;
	}

	public static function clearError(){

        //Limpa o erro
		$_SESSION[User::ERROR] = NULL;

	}

    public static function setSuccess($msg)
	{

		$_SESSION[User::SUCCESS] = $msg;

	}

	public static function getSuccess()
	{

		$msg = (isset($_SESSION[User::SUCCESS]) && $_SESSION[User::SUCCESS]) ? $_SESSION[User::SUCCESS] : '';

		User::clearSuccess();

		return $msg;

	}

	public static function clearSuccess()
	{

		$_SESSION[User::SUCCESS] = NULL;

	}

	public static function setErrorRegister($msg)
	{
        //Recebe a mensagem do erro
		$_SESSION[User::ERROR_REGISTER] = $msg;

	}

	public static function getErrorRegister()
	{
        //Verifico se está definido se já está na sessão ou se é vazio
		$msg = (isset($_SESSION[User::ERROR_REGISTER]) && $_SESSION[User::ERROR_REGISTER]) ? $_SESSION[User::ERROR_REGISTER] : '';

		User::clearErrorRegister();

		return $msg;

	}

	public static function clearErrorRegister()
	{
        //Define como null
		$_SESSION[User::ERROR_REGISTER] = NULL;

	}

    //Verifica se o login já existe
	public static function checkLoginExist($login)
	{

		$sql = new Sql();

        //Vefirica se o usuário já existe no banco
		$results = $sql->select("SELECT * FROM tb_users WHERE deslogin = :deslogin", [
			':deslogin'=>$login
		]);

        //Se é maior que zero existe no banco
		return (count($results) > 0);

	}
//==========================================================================================

public static function getPasswordHash($password){

    //Criptografa a senha
		return password_hash($password, PASSWORD_DEFAULT, [
			'cost'=>12
		]);

	}

//=======================================================================================
public function getOrders()
{
    $sql = new Sql();

    // Executa uma consulta SQL que busca informações de pedidos e tabelas relacionadas.
    $results = $sql->select("
        SELECT * 
        FROM tb_orders a 
        INNER JOIN tb_ordersstatus b USING(idstatus) 
        INNER JOIN tb_carts c USING(idcart)
        INNER JOIN tb_users d ON d.iduser = a.iduser
        INNER JOIN tb_addresses e USING(idaddress)
        INNER JOIN tb_persons f ON f.idperson = d.idperson
        WHERE a.iduser = :iduser
    ", [
        ':iduser' => $this->getiduser() // Obtém o ID do usuário a partir do método getiduser()
    ]);

    // Retorna os resultados da consulta.
    return $results;
}


}


?>