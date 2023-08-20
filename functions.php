<?php 

use \Hcode\Model\User;
use \Hcode\Model\Cart;

function formatPrice($vlprice)
{
	//Se o preço for vazio
	if (!$vlprice > 0) $vlprice = 0;

	return number_format($vlprice, 2, ",", ".");

}

function formatDate($date)
{

	return date('d/m/Y', strtotime($date));

}

function checkLogin($inadmin = true)
{
	//passa para User::checklogin para usar no template no escopo global
	return User::checkLogin($inadmin);

}

function getUserName()
{
	//Pega o usuário da sessão
	$user = User::getFromSession();

	return $user->getdesperson();

}

function getCartNrQtd()
{
	//pega o carrinho da sessão
	$cart = Cart::getFromSession();

	//Pega o total dos produtos
	$totals = $cart->getProductsTotals();

	return $totals['nrqtd'];

}

function getCartVlSubTotal()
{
	//Pega o carrinho da sessão
	$cart = Cart::getFromSession();

	//Pega o total
	$totals = $cart->getProductsTotals();

	//Passa os valores formatados
	return formatPrice($totals['vlprice']);

}

 ?>