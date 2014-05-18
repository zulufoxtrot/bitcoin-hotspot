<?php

//periodically update exchange rate
//periodically check payments status
//periodically check remaining access time

include('common.inc.php');


$euro_fare = EURO_FARE;

//we need to get and store the exchange rate in the database, rather flooding BitcoinAverage with requests, which could lead to a block
$exchange_rate = get_online_exchange_rate();
if (is_numeric($exchange_rate) && $exchange_rate > 0) { //if request succeeded
	connect_to_database();
	mysql_query("update misc set exchange_rate = {$exchange_rate}"); //store latest exchange rate in database
}




//look in the database for all currently registered clients
connect_to_database();
$result = mysql_query("select ip_address, bitcoin_address, last_balance, access_end_timestamp, last_transaction_amount
			from clients
			");


if (mysql_num_rows($result) > 0)
	//at least one result is returned, that means at least a client is connected and we can bother listing the entries.
{

	while($row = mysql_fetch_assoc($result)){
	//iterate through each customer/IP


		$bitcoin_address = $row['bitcoin_address'];
		$access_end_timestamp = $row['access_end_timestamp'];
		$stored_balance = $row['last_balance'];
		$ip_address = $row['ip_address'];
		$time_left = strtotime($access_end_timestamp) - time();



		//get address balance
		$bitcoin = new Bitcoin(RPC_USERNAME,RPC_PASSWORD,RPC_ADDRESS,RPC_PORT); //host and port are optional server is running on localhost and default port
		$current_balance = $bitcoin->getreceivedbyaddress($bitcoin_address, 0); //retreives the balance for given address. 0 is the number of confirmations.

		//compare current balance with stored balance
		//this processes the case where the client adds credit
		if ($current_balance > $stored_balance){
			//that means fresh money just came in !

			//calculate the amount sent in the transaction
			$last_transaction_amount = $current_balance - $stored_balance;

			//calculate access time based on current exchange rate
			$bitcoin_fare = round($euro_fare / $exchange_rate, 8); 	//round to 8 decimals (maximum supported by the protocol)
			$time_paid = $last_transaction_amount / $bitcoin_fare; //time paid for, in minutes

			
			//add access time to current access_end_timestamp
			if ($time_left > 0) {
				//if user still has credit
				//then we add the new credit to the current credit
				$new_access_end_timestamp = strtotime($access_end_timestamp) + ($time_paid * 60); //new timestamp, in seconds
			}else{
				//if user has no credit
				//then we only add the new credit, based on current timestamp
				$new_access_end_timestamp = time() + ($time_paid * 60); //new timestamp, in seconds
			}

			//convert from UNIX timestamp to SQL timestamp format
			$new_access_end_timestamp = date("Y-m-d H:i:s", $new_access_end_timestamp); 


			//replace last_transaction_amount with difference between current balance and stored balance
			//replace stored balance with current balance
			//replace access end timestamp with new timestamp
			mysql_query("update clients set
						access_end_timestamp = '{$new_access_end_timestamp}',
						last_transaction_amount = {$last_transaction_amount},
						last_balance = {$current_balance}
						where ip_address = '{$ip_address}'");


			//finally, issue iptables rules to unblock internet access

		}


		//block internet access if time is over
		if ($time_left < 0){

			//issue new iptable rules for $ip_address

		}



		//see if client still connected

		//get credit for client


		//if client disconnected AND credit exhausted





	}







}



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
		//replace last_transaction_amount with transaction amount. last_transaction_amount is not used by the backend, it is only used to give the user a confirmation of the received amount.
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
	//check_payment_status(client_ip_address);

}




?>
