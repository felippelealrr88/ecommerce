<?php
namespace Hcode\Model;

use \Hcode\DB\Sql;
use Hcode\Mailer;
use Hcode\Model;

class Product extends Model{


    public static function listAll(){

        $sql = new Sql();
        return $sql->select("SELECT * FROM tb_products ORDER BY desproduct;");
    }

    //checa a lista de produtos
    public static function checkList($list){
        
        //Altera row dentro o foreach por causa do &
        foreach ($list as &$row) {
            
            $p = new Product();

            $p->setData($row);
            //getValues trata a foto com checkPhoto
            $row = $p->getValues();
        }

        return $list;
    }

    public function saveProduct(){

        $sql = new Sql();

            $results = $sql->select("CALL sp_products_save(:idproduct, :desproduct, :vlprice, :vlwidth, :vlheight, :vllength, :vlweight, :desurl)", array(
                ":idproduct"=>$this->getidproduct(),
                ":desproduct"=>$this->getdesproduct(),
                ":vlprice"=>$this->getvlprice(),
                ":vlwidth"=>$this->getvlwidth(),
                ":vlheight"=>$this->getvlheight(),
                ":vllength"=>$this->getvllength(),
                ":vlweight"=>$this->getvlweight(),
                ":desurl"=>$this->getdesurl()
            ));
    
            //seta no proprio objeto    
            $this->setData($results[0]);

        
           
        }

public function getProduct($idproduct){

    $sql = new Sql();

    $results = $sql->select("SELECT * FROM tb_products WHERE idproduct = :idproduct", [
        ":idproduct"=>$idproduct
    ]);  
    
    $this->setData($results[0]);
}


public function deleteProduct(){

    $sql = new Sql();

    $sql->query("DELETE FROM tb_products WHERE idproduct = :idproduct", [
        ":idproduct"=>$this->getidproduct()
    ]);
}

//define uma foto pradrão caso ela não exista (necessidade do template)
public function checkPhoto(){

    if (file_exists(
        $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 
        "res" . DIRECTORY_SEPARATOR . 
        "site" . DIRECTORY_SEPARATOR . 
        "img" . DIRECTORY_SEPARATOR .
        "products" . DIRECTORY_SEPARATOR .
        $this->getidproduct() . ".jpg"  )){

            $url = "/res/site/img/products/" . $this->getidproduct() . ".jpg";

        }else{

            $url =  "/res/site/img/product.jpg";
        }

        //retorna e seta dentro do objeto
        return $this->setdesphoto($url);
}

//get values somente de produtos para verificar a foto necessaria ao template
public function getValues(){

    $this->checkPhoto();

    //Faz o mesmo queo pai
    $values = parent::getValues();

    return $values;
}

public function setPhoto($file){

    //onde tem ponto faz um array 
    $extension = explode('.', $file["name"]);

    //a extensão é a ultima posição do array
    $extension = end($extension);

    //criação dos temporários
    switch ($extension) {
        case 'jpg':
        case 'jpeg':
            $image = imagecreatefromjpeg($file["tmp_name"]);
            break;

        case 'gif':
            $image = imagecreatefromgif($file["tmp_name"]);
            break;
            
        case 'png':
            $image = imagecreatefrompng($file["tmp_name"]);
            break;
           
    }

    $destino = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 
    "res" . DIRECTORY_SEPARATOR . 
    "site" . DIRECTORY_SEPARATOR . 
    "img" . DIRECTORY_SEPARATOR .
    "products" . DIRECTORY_SEPARATOR .
    $this->getidproduct() . ".jpg";

    //imagem, destino
    imagejpeg($image, $destino);
    
    imagedestroy($image);
    
    //carrega o dado na memória
    $this->checkPhoto();
}

//metodo que pega a url da página
public function getFromUrl($desurl){
    
    $sql = new Sql();

    $rows =  $sql->select("SELECT * FROM tb_products WHERE desurl = :desurl LIMIT 1", [
        ':desurl'=>$desurl
    ]);

    //coloca as informações dentro do proprio objeto Product
    $this->setData($rows[0]);
}

//Lista as categorias do banco
public function getCategories(){

    $sql = new Sql();

    $sql->select("SELECT * FROM tb_categories a INNER JOIN tb_productscategories b ON a.idcategory = b.idcategory WHERE b.idproduct = :idproduct", [
        ':idproduct'=>$this->getidproduct()
    ]);
}

public static function getPage($page = 1, $itemsPerPage = 5)
{
    // Calcula o índice do primeiro item na página atual.
    $start = ($page - 1) * $itemsPerPage;

    // Cria uma nova instância da classe Sql.
    $sql = new Sql();

    // Executa uma consulta SQL para buscar todos os produtos com paginação.
    $results = $sql->select("
        SELECT SQL_CALC_FOUND_ROWS *
        FROM tb_products 
        ORDER BY desproduct
        LIMIT $start, $itemsPerPage;
    ");

    // Executa uma consulta SQL para obter o número total de resultados (para fins de paginação).
    $resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal;");

    // Retorna um array contendo os dados da página atual, o total de resultados e o número de páginas.
    return [
        'data' => $results, // Dados da página atual.
        'total' => (int)$resultTotal[0]["nrtotal"], // Total de resultados encontrados.
        'pages' => ceil($resultTotal[0]["nrtotal"] / $itemsPerPage) // Número total de páginas.
    ];
}

public static function getPageSearch($search, $page = 1, $itemsPerPage = 5)
{
    // Calcula o índice do primeiro item na página atual.
    $start = ($page - 1) * $itemsPerPage;

    // Cria uma nova instância da classe Sql.
    $sql = new Sql();

    // Executa uma consulta SQL para buscar os produtos com base em uma pesquisa, com paginação.
    $results = $sql->select("
        SELECT SQL_CALC_FOUND_ROWS *
        FROM tb_products 
        WHERE desproduct LIKE :search
        ORDER BY desproduct
        LIMIT $start, $itemsPerPage;
    ", [
        ':search' => '%' . $search . '%' // Parâmetro de pesquisa (com % para corresponder parcialmente).
    ]);

    // Executa uma consulta SQL para obter o número total de resultados (para fins de paginação).
    $resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal;");

    // Retorna um array contendo os dados da página atual, o total de resultados e o número de páginas.
    return [
        'data' => $results, // Dados da página atual.
        'total' => (int)$resultTotal[0]["nrtotal"], // Total de resultados encontrados.
        'pages' => ceil($resultTotal[0]["nrtotal"] / $itemsPerPage) // Número total de páginas.
    ];
}



}

?>