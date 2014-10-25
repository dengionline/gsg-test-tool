<?php

function prepareRequest() {
	$requiredFields = ['projectID', 'secretKey', 'action'];
	foreach ($requiredFields as $field) {
		if (!isset($_REQUEST[$field]) || empty($_REQUEST[$field])) {
			echo error($field, 'Empty field value'); exit;
		}
	}

	$projectId = (int)$_REQUEST['projectID'];
	$timestamp = (int)$_REQUEST['timestamp'];
	$action = strtolower($_REQUEST['action']);
	$params = json_decode($_REQUEST['params'], true);
	$sign = $_REQUEST['secretKey'];

	$xml = "<request>
    <project>{$projectId}</project>
    <timestamp>{$timestamp}</timestamp>
    <action>{$action}</action>
";

    if (is_array($params) && count($params)) {
    	$xml .= "    <params>
";
        foreach ($params as $param) {
            $xml .= "        <{$param['key']}>{$param['val']}</{$param['key']}>
";
        }
    	$xml .= "    </params>
";    	
    }
    $xml .= "    <sign>{$sign}</sign>
";
	$xml .= "</request>";
	return $xml;
}

function sendRequest($request) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, 'http://gsg.dengionline.com/api/');
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$data = curl_exec($ch);
	curl_close($ch);	
	return $data;
}

function formatResponse($response) {
    $response = str_replace('> <', '><', $response);

	$doc = new DomDocument('1.0');
    $doc->loadXML($response);
    $doc->preserveWhiteSpace = false;
    $doc->formatOutput = true;
    return $doc->saveXML();
}

function success($request, $response) {
	return json_encode(array(
		'success' => TRUE,
		'request' => htmlentities($request),
		//'request' => htmlentities(print_r($_REQUEST, 1)),
		'response' => htmlentities($response),
	));
}

function error($field, $message) {
	return json_encode(array(
		'success' => FALSE,
		'errors' => array(
			$field => $message
			//'projectID' => 'Wrong project id',
			//'action' => 'Unknown action',
			//'secretKey' => 'Wrong signature',
			//'params' => "Wrong value of parameter \"paysystem\"",
			//'common' => 'Service is not reachable'
		)
	));
}

$request = prepareRequest();
$response = sendRequest($request);
$response = formatResponse($response);
echo success($request, $response);