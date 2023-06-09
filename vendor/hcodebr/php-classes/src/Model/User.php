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
                !isset($_SESSION[User::SESSION]) //Se a sessão não existe OU
			    || 
			    !$_SESSION[User::SESSION] //Se está vazio OU
			    ||
			    !(int)$_SESSION[User::SESSION]["iduser"] > 0 //se id > 0
            ) {
                //Não está logado
                return false;
            }else{
                //Se está logado cai no else
                //Verifica se é um admin (Se o usuário tenta acessar uma rota do admin)
                if($inadmin === true && (bool)$_SESSION[User::SESSION]['inadmin'] === true){

                    return true;
                }

                // Ele tá logado mas não precisa ser admin
                else if($inadmin === false){
                    //pode entrar
                    return true;
                }
                //Se algo for diferente disso não está logado
                else{
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
        //se não está ativa vai para admin/login
        if (User::checkLogin($inadmin)) {
			
			header("Location: /admin/login");
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
            ":despassword"=>$this->getdespassword(),
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
            ":desperson"=>$this->getdesperson(),
            ":deslogin"=>$this->getdeslogin(),
            ":despassword"=>$this->getdespassword(),
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

    public static function getForgot($email){

        $sql = new Sql();

        //verifica se o email está cadastrado
        $results = $sql->select("SELECT * FROM tb_persons a INNER JOIN tb_users b USING(idperson) WHERE a.desemail = :email;", array(
            ":email"=>$email
        ));

        //valida se encontrou email
        if(count($results) === 0){
            throw new \Exception("Não foi possível recuperar a senha");
        }else{

            $data = $results[0];
            
            //Procedure que salva info da tentativa de recuperação com id e ip do usuario
            $results2 = $sql->select("CALL sp_userspasswordsrecoveries_create(:iduser, :desip)", array(
                ":iduser"=>$data["iduser"],
                ":desip"=>$_SERVER["REMOTE_ADDR"]

            ));

            //verifica se criou
            if(count($results2) === 0){

                throw new \Exception("Não foi possível recuperar a senha");
            }else{

                $dataRecovery = $results2[0];

                //criptografa o usuário para mandar o email (em base64)
            
                $code = openssl_encrypt($dataRecovery['idrecovery'], 'AES-128-CBC', pack("a16", User::SECRET), 0, pack("a16", User::SECRET_IV));

			    $code = base64_encode($code);


                $link = "http://www.hcodecommerce.com.br/admin/forgot/reset?code=$code";

                //Usar o phpmailer para mandar o email
                $mailer = new Mailer($data["desemail"], $data["desperson"], "Redefinir senha da Hcode Store", "forgot", array(
                    "name"=>$data["desperson"],
                    "link"=>$link
                ));

                $mailer->send();

                return $data;


            }
        }

    }

//=====================================================================================================

    public static function validForgotDecrypt($code){

        //Descriptografar o id
        $code = base64_decode($code);

	    $idrecovery = openssl_decrypt($code, 'AES-128-CBC', pack("a16", User::SECRET), 0, pack("a16", User::SECRET_IV));

        //regras de verificação no banco (regras de validação do recovery)
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_userspasswordsrecoveries a INNER JOIN tb_users b USING(iduser) INNER JOIN tb_persons c USING(idperson) WHERE a.idrecovery = :idrecovery AND a.dtrecovery IS NULL AND DATE_ADD(a.dtregister, INTERVAL 1 HOUR) >= NOW(); ", array(
            ":idrecovery"=>$idrecovery
        ));


        if (count($results) === 0) {
            throw new \Exception("Não foi possível recuperar a senha");
        }else{

            return $results[0];
        }

    }

//==========================================================================================

    public static function setForgotUsed($idrecovery){

        $sql = new Sql();

        $sql->query("UPDATE tb_userspasswordsrecoveries SET dtrecovery = NOW() WHERE idrecovery = :idrecovery ", array(
            ":idrecovery"=>$idrecovery
        ));
    }

    public function setPassword($password){

        $sql = new Sql();

        $sql->query("UPDATE tb_users SET despassword = :password WHERE iduser = :iduser", array(
            ":password"=>$password,
            ":iduser"=>$this->getiduser()
        ));
    }


}

?>