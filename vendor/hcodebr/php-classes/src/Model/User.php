<?php
namespace Hcode\Model;

use Exception;
use \Hcode\DB\Sql;
use Hcode\Mailer;
use Hcode\Model;

class User extends Model{

        const SESSION = "User";
        const SECRET = "HcodePhp7_Secret";
    
        protected $fields = [
            "iduser", "idperson", "deslogin", "despassword", "inadmin", "dtergister"
        ];    

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
    
    //verifica se está logado
    public static function verifyLogin($inadmin = true){
        //se não está ativa vai para admin/login
        if (
			!isset($_SESSION[User::SESSION]) //se não existir
			|| 
			!$_SESSION[User::SESSION] //se vazio
			||
			!(int)$_SESSION[User::SESSION]["iduser"] > 0 //se id >0
			||
			(bool)$_SESSION[User::SESSION]["inadmin"] !== $inadmin //não pode acessar administração
		) {
			
			header("Location: /admin/login");
			exit;

		}
        

    }

    public static function logout()
	{

		$_SESSION[User::SESSION] = NULL;

	}

//função que lista todos os usuários    
    public static function listAll(){

        $sql = new Sql();
        return $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) ORDER BY b.desperson");


    }

    //função que lista por id   
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

    //função que cria um novo usuário
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

    public function delete(){
       
        $sql = new Sql();

        $sql->query("CALL sp_users_delete(:iduser)", array(
            ":iduser"=>$this->getiduser()
        ));

    }

    //Envia o email para o esqueci a senha
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
                $key = User::SECRET;
                $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length("AES-256-CBC"));

                $code = base64_encode(openssl_encrypt($dataRecovery["idrecovery"], "AES-256-CBC", $key, 0, $iv));

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


}

?>