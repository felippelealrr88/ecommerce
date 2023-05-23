<?php 

namespace Hcode;
use Hcode\Page;

//herda de page
class PageAdmin extends Page {

	//configura a página do admin para não sobrescrever o site
	public function __construct($opts = array(), $tpl_dir = "/views/admin/") //rota para admin
	{
		//chama o construtor da classe pai (Page)
		parent::__construct($opts, $tpl_dir);

	}

}

?>