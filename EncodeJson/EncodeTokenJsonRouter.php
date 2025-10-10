<?php
/***************  POST ENCODE JSON DATA  ******************/
$f3->route('POST /EncodeJsonData',
	function($f3) {
		header('Content-Type: application/json');
		$decoded_items =  json_decode($f3->get('BODY'),true);
		if(!$decoded_items == NULL)
            encodeJson($decoded_items);
		else
			echo json_encode(array("status"=>"error This value","message_text"=>"Invalid input parameters"),JSON_FORCE_OBJECT);
	}
);
/***************  POST ENCODE JSON DATA  ******************/


/***************  POST DECODE JSON DATA  ******************/
$f3->route('POST /DecodeTokenData',
	function($f3) {
		header('Content-Type: application/json');
		$decoded_items =  json_decode($f3->get('BODY'),true);
		if(!$decoded_items == NULL)
            decodeJson($decoded_items);
		else
			echo json_encode(array("status"=>"error This value","message_text"=>"Invalid input parameters"),JSON_FORCE_OBJECT);
	}
);
/***************  POST DECODE JSON DATA  ******************/

?>