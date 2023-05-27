<?php 

namespace Hcode;

use Rain\Tpl;

class Page {

	private $tpl; //atributo criado para poder ser usado em outros métodos com this->
	private $options = []; //array de opções
	private $defaults = [  //array de opções padrão dos templates
		"header"=>true, //por padrão tem cabeçalho e rodapé, com exceção do login que passa false para esses parametros
		"footer"=>true,
		"data"=>[]
	];

	//metodo mágico construtor (não precisa ser instanciado, sobe junto com um new Page)	
	public function __construct($opts = array(), $tpl_dir = "/views/") //recebe um array de opções e o nome da rota
	{

		//sobrescreve o array padrão com as opções informadas do usuário
		$this->options = array_merge($this->defaults, $opts); 

		//configurações do Rain TPL
		$config = array(
		    "base_url"      => null,
		    "tpl_dir"       => $_SERVER['DOCUMENT_ROOT'].$tpl_dir, //variável de ambiente do diretório root . pasta
		    "cache_dir"     => $_SERVER['DOCUMENT_ROOT']."/views-cache/",
		    "debug"         => false
		);

		//passa as configs para o TPL
		Tpl::configure( $config ); 

		$this->tpl = new Tpl();

		//passa o array de opções para o método setData
		if ($this->options['data']) $this->setData($this->options['data']); 

		//Se $options[header] for true então carrega o header
		if ($this->options['header'] === true) $this->tpl->draw("header", false); 

	}

	//método mágico destrutor (não precisa ser instanciado, roda no final)	
	public function __destruct()
	{
		//Se $option[footer] for true então carrega o footer
		if ($this->options['footer'] === true) $this->tpl->draw("footer", false); 

	}

	//seta os dados (espera um array com os dados)
	private function setData($data = array()) 
	{
		//percorre o array com os dados
		foreach($data as $key => $val) 
		{
			//passa para o TPL chave e valor para usar no HTML (back to front)
			$this->tpl->assign($key, $val); 

		}

	}

	//Cria o conteúdo da página (recebe o nome do template usado no index.php, as variáveis que serão passadas para o front)
	public function setTpl($tplname, $data = array(), $returnHTML = false) 
	{
		//chama o setData
		$this->setData($data); 
		//Desenha o arquivo na tela recebendo o nome do template (index.html)
		return $this->tpl->draw($tplname, $returnHTML); 

	}

}

 ?>