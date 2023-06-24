<?php
namespace Hcode\Model;

use Exception;
use \Hcode\DB\Sql;
use Hcode\Mailer;
use Hcode\Model;

class Category extends Model{

//=============================================================================================

    public static function listAll(){

        $sql = new Sql();
        return $sql->select("SELECT * FROM db_ecommerce.tb_categories ORDER BY idcategory;");
    }

//==================================================================================================

    public function saveCategory(){

        $sql = new Sql();

            $results = $sql->select("CALL sp_categories_save(:idcategory, :descategory)", array(
                ":idcategory"=>$this->getidcategory(),
                ":descategory"=>$this->getdescategory()
            ));
    
            //var_dump($results); exit;
            //seta no proprio objeto     
            $this->setData($results[0]);
           
        }
//==========================================================================================

public function getCategory($idcategory){

    $sql = new Sql();

    $results = $sql->select("SELECT * FROM tb_categories WHERE idcategory = :idcategory", [
        ":idcategory"=>$idcategory
    ]);  
    
    $this->setData($results[0]);
}

//==============================================================================================================

public function deleteCategory(){

    $sql = new Sql();

    $sql->query("DELETE FROM tb_categories WHERE idcategory = :idcategory", [
        ":idcategory"=>$this->getidcategory()
    ]);

}

}

?>