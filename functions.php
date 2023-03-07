<?php

function sendMessage($chat_id,$text)
{
	global $token;
    $api    = "https://api.telegram.org/bot$token/";
    $method = "sendMessage";
    $params = "?chat_id=$chat_id&text=" . urlencode($text)."&parse_mode=MarkDown";
  
  	$url = $api . $method . $params;
    $result = file_get_contents($url);
  	return $result;
}



?>