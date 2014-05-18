<?php

//periodically check payments status
//periodically check remaining access time

include('common.inc.php');



function check_payment_status($client_ip_address){
	
	//current sql structure:
	//1 table: clients. fields: btc_@, mac_@, ip_@, last_btc@_balance, browsing_end_timestamp, last_transaction_amount 
	//this should remove the need for a transactions table.

	//compare current @ balance to @ balance stored in database
	//if current @ balance > stored @ balance:
		//calculate amount received: (current@ - stored@)*exchange_rate
		//calculate browsing time: (amount received)/EURO_FARE
		//add browsing time to stored browsing_end_timestamp (also process case where browsing_end_timestamp is NULL or empty or equal to 000-00-00 00:00)
		//replace stored @ balance with current @ balance
		//replace last_transaction_amount with transaction amount. this is not used by the backend, it is only to give the user a confirmation of the received amount.
	//endif




}



// function update_authorization_status(){
// //determine whether connected clients are allowed to use the internet

// 	//get list of connected clients
// 	//for each client:
// 		//compute sum of durations allowed by associated payments
// 		//if sum 

// }








function list_connected_clients(){

	//call to some system mac-ip listing tool


	//if client is no longer connected AND has no more credit (use case: client accidentaly disconnects, reconnects immediately, still has credit):
		//cleanup IP/mac in DB 
		//DELETE * FROM clients WHERE mac_address NOT IN 

		//cleanup payments associated with client in DB
		//DELETE * FROM payments WHERE bitcoin_address=''
	//

	//if client is connected
	check_payment_status(client_ip_address);

}




?>
