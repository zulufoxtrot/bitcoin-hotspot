<?php
//functions used by several PHPs

//we're using the easybitcoin library, an abstraction layer for communication with bitcoind's RPC-JSON server
include('lib/easybitcoin.php');


//////
// RPC settings for communication with the bitcoind instance
define('rpc_username', 'hotsp0t');
define('rpc_password', 'brabrabra');
define('rpc_address', '192.168.56.101');
define('rpc_port', '8332');
/////

/////
//set the per minute fare here !
//
define("EURO_FARE", "0.1");
//
/////








function connect_to_database(){
//connects to the MySQL database system and selects the "bitcoin-hotspot" database
	mysql_connect('localhost','root','fjf');
	mysql_select_db('bitcoin-hotspot');
}

function get_exchange_rate(){
//pulls the average EUR/BTC exchange rate from the bitcoinaverage API
	$exchange_rate = file_get_contents("https://api.bitcoinaverage.com/ticker/global/EUR/last");
	if (is_null($exchange_rate)){ //if the request failed...
		//throw error and break
		$html_response = "<h2>Error</h2>Oh no ! We would not get the bitcoin average price and therefore cannot compute the fare. Try again later :)";
		echo $html_response;
		exit; //end script execution
	}else{
		return $exchange_rate;
	}
}

?>