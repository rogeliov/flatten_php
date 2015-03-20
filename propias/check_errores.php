<?php
require 'vendor/autoload.php';

function print_online($cadena){
	echo "<pre>".$cadena."</pre>";
	flush();
    ob_flush();
}


/*
Para extraer el valo número de una variable
@input $val una variable
@output IF es numérico valor numérico de la variable ELSE cero
*/
function get_numeric($val) { 
	$number = floatval(str_replace('$','',(str_replace(',', '.', str_replace('.', '', $val)))));
    return $number + 0; 
}  


/*
Para revisar si un posible valor numero contiene letras
@input string con posible valor númerico
@output 1 si contiene una letra
		0 si solo tiene números 
*/
function check_letters_in_numbers($string_numero){
	if (preg_match('/[A-Z]+[a-z]+/', $string_numero)){
		return 1;
	}
	else{
		return 0;
	}
	
}

/*
Para revisar si un string es una posible fecha
@input string con posible valor númerico
@output 1 si contiene una letra
		0 si solo tiene números 
*/
function validateDate($date)
{
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') == $date;
}

?>