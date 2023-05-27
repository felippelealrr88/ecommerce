<?php
namespace Hcode\Model;


use \Hcode\DB\Sql;
use Hcode\Model;

class User extends Model{

        const SESSION = "User";
    
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

}


?>