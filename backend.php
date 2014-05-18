<?php

include('common.inc.php');



//!!!! une fois que la page est chargée, il faut faire un appel AJAX pour dire au backend que c'est un VRAI navigateur qui a fait la requête !
//comme ça, le backend désactive la redirection automatique (genre pendant 10 minutes) le temps que la personne paye !
//c'est important que ça soit un appel ajax (implique JS, implique vrai browser) sinon n'importe quelle app pourrait faire la requête sans pour autant afficher un truc à l'user !



//ask the database whether the client paid for access, and if so how much access time they've been granted

//get the client's IP
$ip_address = $_SERVER['REMOTE_ADDR'];

//additional info
$euro_fare = EURO_FARE;
//compute the bitcoin fare
$exchange_rate = get_stored_exchange_rate();
$bitcoin_fare = round($euro_fare / $exchange_rate, 8); 	//round to 8 decimals (maximum supported by the protocol)


//look in the database for the client data associated with this IP
connect_to_database();
$result = mysql_query("select bitcoin_address, last_balance, access_end_timestamp, last_transaction_amount
			from clients
			where ip_address = '{$ip_address}'");


if (mysql_num_rows($result) == 1)
	//one result is returned, that means an entry for this customer/IP is already in the database.
	//we can skip the entry creation part and wait for a payment, or confirm payments.
{

	$row = mysql_fetch_assoc($result);

	//get the bitcoin address associated with this IP address
	$bitcoin_address = $row['bitcoin_address'];

	//get the stored balance
	$stored_balance = $row['last_balance'];

	//get access end timestamp
	$access_end_timestamp = $row['access_end_timestamp'];

	//calculate time remaining by differenciating current timestamp and access end timestamp
	$time_remaining = strtotime($access_end_timestamp) - time();


	// get the amount of the LATEST transaction (provides visual confirmation of the success of the transaction, and works in case of multiple transactions)
	$last_transaction_amount = $row['last_transaction_amount'];

	//decide what response to send depending on gathered data

	if ($stored_balance > 0 && $time_remaining > 0){	
	//if stored timestamp LATER than current timestamp
	//that means there's access time left
	//display confirmation

		$response = array(
						'payment_status' => 'new_balance',
						'last_transaction_amount' => $last_transaction_amount,
						'time_remaining' => $time_remaining,
						'html_body' => "
										<h2>Thanks!</h2>
										We received your payment of <b>{$last_transaction_amount}฿</b>.
										<br />
										Access time remaining:
										<div id='time_remaining'><br /></div>

										Feel free to top up your balance by sending funds to the same address.

										<br />
										<br />

										<a href='#' id='link_show_address'>Show address</a>
										<div id='address'>
										The current fare is <b>{$bitcoin_fare}฿</b> per minute (or {$euro_fare}€/min at {$exchange_rate}€/฿).
										<br />
										<a href='bitcoin:{$bitcoin_address}?label=ITB Bitcoin Hotspot' id='bitcoin_address_uri'>
											<b>
												{$bitcoin_address}
											</b>
										</a>
										<div id='qr_code'></div>
										</div>
						");

	}else

	if ($stored_balance > 0 && $time_remaining <= 0){
	//if stored balance not null AND stored timestamp SOONER than current timestamp
	//that means the access time is over
	//offer to top up balance

		$response = array(
						'payment_status' => 'balance_empty',
						'time_remaining' => $time_remaining,
						'html_body' => "
										<h2>Time's up!</h2>
										Your access time is over.
										<br />
										Feel free to send funds to the same address to top up your balance.
										<br />
										<br />
										The current fare is <b>{$bitcoin_fare}฿</b> per minute (or {$euro_fare}€/min at {$exchange_rate}€/฿).
										<br />
										<a href='bitcoin:{$bitcoin_address}?label=ITB Bitcoin Hotspot' id='bitcoin_address_uri'>
											<b>
												{$bitcoin_address}
											</b>
										</a>
										<div id='qr_code'></div>
						");

	}else

	if ($stored_balance == 0){
	//if stored balance equals 0
	//that means no payment has been received (yet)
	//send a specific HTML response that tells the client-side script to patiently wait
		
		$response = array('payment_status' => 'unchanged',
							'html_body' => "
									<h2>Welcome!</h2>
									This is a paid internet access point.
									The current fare is <b>{$bitcoin_fare}฿</b> per minute (or {$euro_fare}€/min at {$exchange_rate}€/฿).

									<h3>Please send funds to:</h3>
									<a href='bitcoin:{$bitcoin_address}?amount={$bitcoin_fare}&label=ITB Bitcoin Hotspot' id='bitcoin_address_uri'>
										<b>
											{$bitcoin_address}
										</b>
									</a>

									<br/>
									<div id='qr_code'></div>
									You have been granted access to common Bitcoin wallets to proceed to payment.
					");

	}
	//no other use case.





}else{
	//if no result is returned, that means the IP is not in the database yet because the customer just connected.
	//We need to add an entry and generate a Bitcoin destination address.

	//create a bitcoin payment address, and save it in the database along with the IP of the customer
	$bitcoin_address = generate_bitcoin_destination_address();

	$response = array(
					'payment_status' => 'welcome_screen',
					'html_body' => "
									<h2>Welcome!</h2>
									This is a paid internet access point.
									The current fare is <b>{$bitcoin_fare}฿</b> per minute (or {$euro_fare}€/min at {$exchange_rate}€/฿).

									<h3>Please send funds to:</h3>
									<a href='bitcoin:{$bitcoin_address}?amount={$bitcoin_fare}&label=ITB Bitcoin Hotspot' id='bitcoin_address_uri'>
										<b>
											{$bitcoin_address}
										</b>
									</a>

									<br/>
									<div id='qr_code'></div>
									You have been granted access to common Bitcoin wallets to proceed to payment.
					");




}

//send HTTP response in JSON format
echo json_encode($response);


function generate_bitcoin_destination_address(){
//asks Bitcoind for a fresh destination address, and saves it in the database, associated with the IP of the client.

		//create new instance
	$bitcoin = new Bitcoin(RPC_USERNAME,RPC_PASSWORD,RPC_ADDRESS,RPC_PORT); //host and port are optional server is running on localhost and default port
	
	//ask for new address
	$new_bitcoin_address = $bitcoin->getnewaddress();

	//store address in DB, with IP
	$ip_address = $_SERVER['REMOTE_ADDR'];
	connect_to_database();
	mysql_query("insert into clients (bitcoin_address, ip_address)
				values ('{$new_bitcoin_address}','{$ip_address}')");

	return $new_bitcoin_address;
}

function get_stored_exchange_rate(){
	connect_to_database();
	$result = mysql_query("select exchange_rate
						from misc
						");
	$row = mysql_fetch_assoc($result);

	//get the bitcoin address associated with this IP address
	$exchange_rate = $row['exchange_rate'];

	return $exchange_rate;

}


?>
