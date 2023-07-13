<?php

namespace Hcode;

//classe que gera automaticamente Getters and Setters
class Model {

    //recebe os campos do objeto usuário
    private $values = [];

    //toda vez que um método for chamado (SET ou GET)
    public function __call($name, $args)
    {
        //Os primeiros 3 caracteres do nome chamado
        $method = substr($name, 0, 3);
        //descarta aos 3 primeiros e pega o nome do campo
        $fieldName = substr($name, 3, strlen($name));

// Testando o tipo do método, get ou set        
        switch ($method) {

            case "get": // se está setado retorna o fildname se não retorna null
                return (isset($this->values[$fieldName])) ? $this->values[$fieldName] : NULL;
                break;

            case "set":
                $this->values[$fieldName] = $args[0];
                break;
        }
    }

    //cria dinamicamente (a partir de um array) atributo e valor
    public function setData($data = array()){
        foreach ($data as $key => $value) {
            // string "set" concatenada com o valor de $key
            $this->{"set".$key}($value);
        }
    }

//converte objeto para array
    public function getValues(){

        //retorna o atributo privado indiretamente
        return $this->values;

    }

}



?>