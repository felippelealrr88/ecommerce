<?php
namespace Hcode\Model;

use Exception;
use \Hcode\DB\Sql;
use Hcode\Model;
use Hcode\Model\User;

class Cart extends Model{

    //constante com a sessão do carrinho 
    const SESSION = "Cart";

    //Verifica o carrinho
    public static function getFromSession(){

        $cart = new Cart();

        //Verifica SE O CARRINHO EXISTE na sessão e SE EXISTE ID do carrinho (se for vazio o cast para inteiro retorna zero)
        if (isset($_SESSION[Cart::SESSION]) && (int)$_SESSION[Cart::SESSION]['idcart'] > 0) {
           
            //carrega os dados do banco
            $cart->getCart((int)$_SESSION[Cart::SESSION]['idcart']);
        }else{

            //Se NÃO EXISTE na sessão tenta recuperar do banco com o sessionId do usuário
            $cart->getFromSessionId();

            //Se NÃO conseguiu carregar então cria um novo carrinho
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
    }

    //cria uma sessão para um usuário ainda não logado (não estático por causa do $this)
    public function setToSession(){

        //Coloca o carrinho na sessão
        $_SESSION[Cart::SESSION] = $this->getValues();

    }

    //Traz do banco usando o id de sessão do usuário
    public function getFromSessionId(){
        
        $sql = new Sql();

        $results = $sql->select("SELEC * FROM tb_carts WHERE dessessionid = :dessessionid", [
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

        $results = $sql->select("SELEC * FROM tb_carts WHERE idcart = :idcart", [
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

}

?>