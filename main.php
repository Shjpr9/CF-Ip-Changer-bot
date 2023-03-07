<?php
// error_reporting(0);
require("functions.php");
$var = file_get_contents("php://input");
$var = json_decode($var,true);
$token = "Your-Bot-Token";
$chat_id = $var['message']['chat']['id'];
$text = $var['message']['text'];
$username = $var['message']['chat']['username'];
$firstname = $var['message']['chat']['first_name'];
$message = "";

file_get_contents("https://api.telegram.org/bot$token/sendChatAction?chat_id=$chat_id&action=typing");

//-----------------------------------code---------------------------------------//
//command {api_key} {email} {zone_id} {dns_record_id} {new_subdomain} {new_ip}

$zones_condition = substr($text, 0, 6) == '/zones'; 
$dnsrec_condition = substr($text, 0, 8) == '/records';
$change_condition = substr($text, 0, 7) == '/change';
switch ($text) {
    //start command
    case '/start':
        $message = "Hey, Welcome to this telegram bot. You can use this bot to directly manage your dns records on Cloudflare. So let's start! \n\n 
        First, get your zone id by sending /zones \n
        Usage: `/zones {api_key} {email}`";
        sendMessage($chat_id, $message);
        break;

    //help command
    case '/help':
        $message = "This bot has been made to help you change your subdomain ips through Cloudflare API. Here are the steps: \n
        1. Get your api key from your Cloudflare Dashboard. \n
        2. use /zones to get your current zones from Cloudflare API. Use this structure to get your current zones:\n
        `/zones {api_key} {email}` \n
        (note: You should use the email that you provided during Cloudflare Signup)\n
        3. Now you should get your dns record ids. Here is the structure: \n
        `/records {api_key} {email} {zone_id}` \n
        4. Now you have everything you need to form the last command! Lets do it: \n
        `/change {api_key} {email} {zone_id} {dns_record_id} {new_subdomain} {new_ip}` \n
        (note: if you want to keep the old subdomain-name simply write it again.)\n
        By the way, because we don't store any data on our servers, you should enter your credentials everytime you send a request!";
        sendMessage($chat_id, $message);
        break;
    

    case '/help_fa':
        $message = "برای دریافت راهنمای فارسی روی لینک زیر کلیک کنید";
        $inline_keyboard = [
			[
				[
					"text" => "راهنمای فارسی ربات",
					"url" => "https://telegra.ph/%D8%B1%D8%A7%D9%87%D9%86%D9%85%D8%A7%DB%8C-%D9%81%D8%A7%D8%B1%D8%B3%DB%8C-%D8%B1%D8%A8%D8%A7%D8%AA-%D8%AA%D8%BA%DB%8C%DB%8C%D8%B1-%D8%A2%DB%8C%D9%BE%DB%8C-03-07"
				]
			]
		];
		$keyboard = json_encode([
			"inline_keyboard" =>  
				$inline_keyboard,
		  ]);

		file_get_contents("https://api.telegram.org/bot$token/sendMessage?text=".urlencode($message)."&chat_id=$chat_id&reply_markup=$keyboard");
        break;

    //Get Zones
    case $zones_condition:
        $parts = explode(" ", $text);
        if(count($parts) < 3){
            sendMessage($chat_id, "Please enter full command! ");
            exit();
        }

        // Your Cloudflare API credentials
        $email = $parts[2];
        $key = $parts[1];

        // Cloudflare API endpoint for retrieving all zones
        $endpoint = 'https://api.cloudflare.com/client/v4/zones';

        // Set the headers for the API request
        $headers = array(
            'Content-Type: application/json',
            'X-Auth-Email: ' . $email,
            'X-Auth-Key: ' . $key,
        );

        // Send the API request to retrieve the zones
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        curl_close($ch);

        // Check the API response for errors
        $result = json_decode($response, true);
        if ($result['success'] !== true) {
            $message = 'Error retrieving zones: ' . $result['errors'][0]['message'];
        } else {
            $zones = $result['result'];
            foreach ($zones as $zone) {
                $message .= 'Zone ID for ' . $zone['name'] . ': `' . $zone['id'] . "`\n\n";
            }
        }
        sendMessage($chat_id, $message);
        break;
    
    //Get dns record ids
    case $dnsrec_condition:
        $parts = explode(" ", $text);
        if(count($parts) < 4){
            sendMessage($chat_id, "Please enter full command! ");
            exit();
        }
        // Your Cloudflare API credentials
        $email = $parts[2];
        $key = $parts[1];
        $zone_id = $parts[3];
        // Cloudflare API endpoint for retrieving all DNS records for a zone
        $endpoint_get_dnss = "https://api.cloudflare.com/client/v4/zones/$zone_id/dns_records?type=A";
        // Set the headers for the API request
        $headers = array(
            'Content-Type: application/json',
            'X-Auth-Email: ' . $email,
            'X-Auth-Key: ' . $key,
        );
        // Send the API request to retrieve the DNS records
        $ch = curl_init($endpoint_get_dnss);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        curl_close($ch);
        // Check the API response for errors
        $result = json_decode($response, true);
        if ($result['success'] !== true) {
            $message = 'Error retrieving DNS records: ' . $result['errors'][0]['message'];
        } else {
            $dns_records = $result['result'];
            foreach ($dns_records as $record) {
                $message .= 'DNS record ID for ' . urlencode($record['name']) . ": `" . $record['id'] . "`\n\n";
            }
        }
        sendMessage($chat_id, $message);
        break;


    //Change the ip
    case $change_condition:
        $parts = explode(" ", $text);
        if(count($parts) < 7){
            sendMessage($chat_id, "Please enter full command! ");
            exit();
        }

        // Your Cloudflare API credentials
        $email = $parts[2];
        $key = $parts[1];
        $zone_id = $parts[3];
        $record_id = $parts[4];
        $new_subdomain = $parts[5];
        $new_ip = $parts[6];


        // Cloudflare API endpoint for updating a DNS record
        $endpoint_change_dnss = "https://api.cloudflare.com/client/v4/zones/$zone_id/dns_records/$record_id";

        // Set the headers for the API request
        $headers = array(
            'Content-Type: application/json',
            'X-Auth-Email: ' . $email,
            'X-Auth-Key: ' . $key,
        );

        // Set the data for the API request
        $data = array(
            'type' => 'A',
            'name' => $new_subdomain, // Replace with your DNS record name
            'content' => $new_ip,
            'ttl' => 1,
            'proxied' => true,
        );

        // Send the API request to update the DNS record
        $ch = curl_init($endpoint_change_dnss);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $response = curl_exec($ch);
        curl_close($ch);

        // Check the API response for errors
        $result = json_decode($response, true);
        if ($result['success'] !== true) {
            $message = 'Error updating DNS record: ' . $result['errors'][0]['message'];
        } else {
            $message = 'DNS record updated successfully';
        }
        sendMessage($chat_id, $message);
        break;



    default:
        sendMessage($chat_id, "Command not recognized. /help");
        break;
}
?>