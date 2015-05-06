<?php
require 'vendor/autoload.php';

function print_online($cadena){
	echo $cadena;
	flush();
    ob_flush();
}


function contar_errores(){
	if(isset($_SESSION['contador_errores']))
		$_SESSION['contador_errores']++;
		
}

function check_es_fecha($string_fecha){
	//Checo con expresion reguarl que sea dd/mm/yyyy
	if (preg_match("/^(0[1-9]|[1-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\/([0-9]{4})$/",$string_fecha))
    {
        return $string_fecha;
    }else{
        return 0;
    }
}

function check_es_moneda($string_moneda){
	if (preg_match("/^([\-]{0,1})([\$]{0,1})([0-9]{1,3}){1}(\,[0-9]{3})*(\.[0-9]{1,2}){0,1}$/",$string_moneda))
    {
        return $string_moneda;
    }else{
        return "error";
    }
	

}

/*
Para extraer el valo número de una variable
@input $val una variable
@output IF es numérico valor numérico de la variable ELSE cero
*/
function get_numeric_limpio($val){
	$string_numero = str_replace('$','',$val);
	$string_numero = str_replace(',','',$string_numero);
	
	return (float)$string_numero;
}

function get_numeric_autraliano($val){
	$string_numero = str_replace('$','',$val);
	$string_numero = str_replace('.','',$string_numero);
	$string_numero = str_replace(',','.',$string_numero);
	
	return $string_numero;
}

function get_numeric_americano($val){
	$string_numero = str_replace('$','',$val);
	$string_numero = str_replace(',','',$string_numero);
	
	
	return $string_numero;
}

function get_numeric($val) { 
	
	$precio = strpos($val, '$');
	$coma = strpos($val, ',');
	$punto = strpos($val, '.');
	
	$string_numero=0;
	//Quito el signo de pesos
	$string_numero = floatval(str_replace('$','',$val));
	
	//Si tiene solo coma o solo punto se tomara como separador decimal
	if($coma !== false && $punto === false){
		$string_numero = floatval(str_replace('$','',str_replace(',','.',$val)));
	}
	else if($coma !== false && $punto !== false){
		if($coma <= $punto){
			$string_numero = floatval(str_replace('$','',str_replace(',','',$val)));
		}
		if($punto <= $coma){
			$string_numero = floatval(str_replace('$','',str_replace('.','',$val)));
		}
	}
	return $string_numero + 0; 
}  



function sanear_string($string)
{

    $string = trim($string);

    $string = str_replace(
        array('á', 'à', 'ä', 'â', 'ª', 'Á', 'À', 'Â', 'Ä'),
        array('a', 'a', 'a', 'a', 'a', 'A', 'A', 'A', 'A'),
        $string
    );

    $string = str_replace(
        array('é', 'è', 'ë', 'ê', 'É', 'È', 'Ê', 'Ë'),
        array('e', 'e', 'e', 'e', 'E', 'E', 'E', 'E'),
        $string
    );

    $string = str_replace(
        array('í', 'ì', 'ï', 'î', 'Í', 'Ì', 'Ï', 'Î'),
        array('i', 'i', 'i', 'i', 'I', 'I', 'I', 'I'),
        $string
    );

    $string = str_replace(
        array('ó', 'ò', 'ö', 'ô', 'Ó', 'Ò', 'Ö', 'Ô'),
        array('o', 'o', 'o', 'o', 'O', 'O', 'O', 'O'),
        $string
    );

    $string = str_replace(
        array('ú', 'ù', 'ü', 'û', 'Ú', 'Ù', 'Û', 'Ü'),
        array('u', 'u', 'u', 'u', 'U', 'U', 'U', 'U'),
        $string
    );

    $string = str_replace(
        array('ñ', 'Ñ', 'ç', 'Ç'),
        array('n', 'N', 'c', 'C',),
        $string
    );

    //Esta parte se encarga de eliminar cualquier caracter extraño
	/*
    $string = str_replace(
        array("\\", "¨", "º", "-", "~",
             "#", "@", "|", "!", "\"",
             "·", "$", "%", "&", "/",
             "(", ")", "?", "'", "¡",
             "¿", "[", "^", "`", "]",
             "+", "}", "{", "¨", "´",
             ">", "< ", ";", ",", ":",
             ".", " "),
        '',
        $string
    );
	*/


    return $string;
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