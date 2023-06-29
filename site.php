<?php
use \Hcode\Page;

//Rota do Home
$app->get("/", function() { 
    //instancia uma nova Page (chama o construtor e add o header na tela)
	$page = new Page(); 

	//chama setTPL passando "index" como paranetro, esse arquivo será desenhado na tela
	$page->setTpl("index"); 

});


?>