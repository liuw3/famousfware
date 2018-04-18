<?php
ini_set("display_errors", "1");

$xml = '<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Header>
<ns2:AuthenticationToken xmlns:ns2="http://auth.ws.fbsaust.com.au" xmlns:S="http://
schemas.xmlsoap.org/soap/envelope/">
<username>learn.online@famous.com.au</username>
<method>AGENT</method>
<password>1E9O9z5S</password>
</ns2:AuthenticationToken>
</S:Header>
<S:Body>
<ns2:Login xmlns:ns2="http://auth.ws.fbsaust.com.au"/>
</S:Body>
</S:Envelope>';

$url = "http://110.145.171.154:8088/AuthenticationService/AuthenticationService?wsdl";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

$headers = array();
array_push($headers, "Content-Type: text/xml; charset=utf-8");
array_push($headers, "Accept: text/xml");
array_push($headers, "Cache-Control: no-cache");
array_push($headers, "Pragma: no-cache");
array_push($headers, "SOAPAction: http://110.145.171.154:8088/AuthenticationService/AuthenticationService/Login");

if($xml != null) {
    curl_setopt($ch, CURLOPT_POSTFIELDS, "$xml");
    array_push($headers, "Content-Length: " . strlen($xml));
}
curl_setopt($ch, CURLOPT_USERPWD, "user_name:password"); /* If required */
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$response = curl_exec($ch);
$token = $response;
var_dump($response);








?>