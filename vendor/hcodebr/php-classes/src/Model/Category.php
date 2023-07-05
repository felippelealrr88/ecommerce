<?php
namespace Hcode\Model;

use Exception;
use \Hcode\DB\Sql;
use Hcode\Model;

class Category extends Model{

    public static function listAll(){

        $sql = new Sql();
        return $sql->select("SELECT * FROM db_ecommerce.tb_categories ORDER BY idcategory;");
    }

    public function saveCategory(){

        $sql = new Sql();

            $results = $sql->select("CALL sp_categories_save(:idcategory, :descategory)", array(
                ":idcategory"=>$this->getidcategory(),
                ":descategory"=>$this->getdescategory()
            ));
    
            //var_dump($results); exit;
            //seta no proprio objeto     
            $this->setData($results[0]);
            Category::updateFile();
           
        }

public function getCategory($idcategory){

    $sql = new Sql();

    $results = $sql->select("SELECT * FROM tb_categories WHERE idcategory = :idcategory", [
        ":idcategory"=>$idcategory
    ]);  
    
    $this->setData($results[0]);
}


public function deleteCategory(){

    $sql = new Sql();

    $sql->query("DELETE FROM tb_categories WHERE idcategory = :idcategory", [
        ":idcategory"=>$this->getidcategory()
    ]);

    Category::updateFile();
}

//Cria o menu de categorias dinamico em html
public static function updateFile(){

    $categories = Category::listAll();

    $html = [];

    foreach ($categories as $row) {
        //html adicionado ao arquivo
        array_push($html, '<li><a href="/categories/'.$row['idcategory'].'">'.$row['descategory'].'</a></li>');
    }
    //salva os arquivos
    file_put_contents($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "views" .DIRECTORY_SEPARATOR. "categories-menu.html", implode('', $html));
}


public function getProducts($related = true){

    $sql = new Sql();

    if ($related === true) {
        
        return $sql->select("
            SELECT * FROM tb_products WHERE idproduct IN(
                SELECT a.idproduct
                FROM tb_products a
                INNER JOIN tb_productscategories b ON a.idproduct = b.idproduct
                WHERE b.idcategory = :idcategory
            );
        ", [
            ':idcategory'=>$this->getidcategory()
        ]);
        
    }else{

        return $sql->select("
            SELECT * FROM tb_products WHERE idproduct NOT IN(
                SELECT a.idproduct
                FROM tb_products a
                INNER JOIN tb_productscategories b ON a.idproduct = b.idproduct
                WHERE b.idcategory = :idcategory
            );
        ", [
            ':idcategory'=>$this->getidcategory()
        ]);
    }
}

//Paginação do site
public function getProductsPage($page = 1, $itemsPerPage = 8){
    
    //Calculo das páginas 
    $start = ($page - 1) * $itemsPerPage;

    $sql = new Sql();

    //Primeira consulta
    $results = $sql->select("
        SELECT SQL_CALC_FOUND_ROWS *
        FROM tb_products a
        INNER JOIN tb_productscategories b ON a.idproduct = b.idproduct
        INNER JOIN tb_categories c ON c.idcategory = b.idcategory
        WHERE c.idcategory = :idcategory
        LIMIT $start, $itemsPerPage;
    ",[
        ':idcategory'=>$this->getidcategory()
    ]);

    //Para saber a quantidade de itens
    $resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal;");

    
    return [
        //retorna os dados do produto como array checando os produtos
        'data'=>Product::checkList($results),
        //numero total de registros na primeira linha e na coluna escolhida
        'total'=>(int)$resultTotal[0]["nrtotal"],
        //saber quantas páginas foram trazidas (converte arredondando pra cima com ceil)
        'pages'=>ceil($resultTotal[0]["nrtotal"] / $itemsPerPage)


    ];
}

//add produto na categoria
public function addProduct(Product $product) {

    $sql = new Sql();

    $sql->query("INSERT INTO tb_productscategories (idcategory, idproduct) VALUES (:idcategory, :idproduct)", [
    ':idcategory'=>$this->getidcategory(),
    ':idproduct'=>$product->getidproduct()
]);

}

public function removeProduct(Product $product){

    $sql = new Sql();

    $sql->query("DELETE FROM tb_productscategories WHERE idcategory = :idcategory AND idproduct = :idproduct", [
    ':idcategory'=>$this->getidcategory(),
    ':idproduct'=>$product->getidproduct()
]);

}


}

?>