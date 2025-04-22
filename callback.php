<?php

// v1   19.11.2021
// Powered by Smart Sender
// https://smartsender.com

ini_set('max_execution_time', '1700');
set_time_limit(1700);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Content-Type: application/json; charset=utf-8');

http_response_code(200);

error_reporting(E_ERROR);
ini_set('display_errors', 1);

//--------------

$body = file_get_contents('php://input');
$input = json_decode($body, true);
$hInput = getallheaders();
$log["headers"] = $hInput;
$xSign = $hInput["Signature"];
//$s1 = base64_decode($xSign);
$s2 = base64_decode($xSign, true);
include('config.php');
$logUrl .= "-callback";
$log["input"] = $input;

$checkSign = base64_encode(hash_hmac("sha256", $body, $monoSecret, true));
if ($xSign != $checkSign) {
  $result["state"] = false;
  $result["error"]["message"][] = "failed check signature";
  echo json_encode($result, JSON_UNESCAPED_UNICODE);
  $log["response"] = $result;
  send_request("https://log.mufiksoft.com/mono-chast", [], "POST", $log);
  exit;
}

$result["state"] = true;
if ($input["order_id"] == NULL) {
  $result["state"] = false;
  $result["error"]["message"][] = "'order_id' is missing";
} else if (file_exists("orders/" . $input["order_id"]) != true) {
  $result["state"] = false;
  $result["error"]["message"][] = "'order_id' is not found";
} else {
  $orderData = json_decode(file_get_contents("orders/" . $input["order_id"]), true);
}
$log["orderData"] = $orderData;
if ($input["state"] == NULL) {
  $result["state"] = false;
  $result["error"]["message"][] = "'state' is missing";
}
if ($result["state"] != true) {
  echo json_encode($result, JSON_UNESCAPED_UNICODE);
  $log["response"] = $result;
  send_request("https://log.mufiksoft.com/mono-chast", [], "POST", $log);
  exit;
}

// Запуск триггера в Smart Sender
$headers[] = "Authorization: Bearer " . $ssToken;
if ($orderData["actions"][strtolower($input["state"])][strtolower($input["order_sub_state"])] != NULL) {
  $result["fire"] = [
    "name" => $orderData["actions"][strtolower($input["state"])][strtolower($input["order_sub_state"])]
  ];
  $result["SmartSender"] = json_decode(send_request("https://api.smartsender.com/v1/contacts/" . $orderData["userId"] . "/fire", $headers, "POST", [
    "name" => $orderData["actions"][strtolower($input["state"])][strtolower($input["order_sub_state"])]
  ]), true);
} else if ($orderData["actions"][strtolower($input["state"])] != NULL && is_string($orderData["actions"][strtolower($input["state"])])) {
  $result["fire"] = [
    "name" => $orderData["actions"][strtolower($input["state"])]
  ];
  $result["SmartSender"] = json_decode(send_request("https://api.smartsender.com/v1/contacts/" . $orderData["userId"] . "/fire", $headers, "POST", [
    "name" => $orderData["actions"][strtolower($input["state"])]
  ]), true);
} else if ($orderData["action"] != NULL && $input["state"] == "SUCCESS") {
  $result["fire"] = [
    "name" => $orderData["action"]
  ];
  $result["SmartSender"] = json_decode(send_request("https://api.smartsender.com/v1/contacts/" . $orderData["userId"] . "/fire", $headers, "POST", [
    "name" => $orderData["action"]
  ]), true);
} else {
  $log["fire"] = "none";
}
$log["response"] = $result;

send_request("https://log.mufiksoft.com/mono-chast", [], "POST", $log);
echo json_encode($result);
