<?php

ini_set('max_execution_time', '1700');
set_time_limit(1700);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Content-Type: application/json; charset=utf-8');

http_response_code(200);

//--------------

$input = json_decode(file_get_contents('php://input'), true);
include('config.php');
$result["state"] = true;


if ($input["userId"] == NULL) {
  $result["state"] = false;
  $result["error"]["message"][] = "'userId' is missing";
}
if ($input["phone"] == NULL) {
  $result["state"] = false;
  $result["error"]["message"][] = "'phone' is missing";
}
if ($input["amount"] == NULL) {
  $result["state"] = false;
  $result["error"]["message"][] = "'amount' is missing";
}
if ($input["action"] != NULL) {
  $log["use"] = "action";
} else if ($input["actions"] != NULL && is_array($input["actions"])) {
  $log["use"] = "actions";
  if ($input["actions"]["success"] == NULL) {
    $result["state"] = false;
    $result["error"]["message"][] = "'success' is missing from 'actions'";
  }
} else {
  $result["state"] = false;
  $result["error"]["message"][] = "'action' or array 'actions' is missing";
}
if ($input["productName"] == NULL) {
  $result["state"] = false;
  $result["error"]["message"][] = "'productName' is missing";
}
if ($input["parts"] == NULL) {
  $result["state"] = false;
  $result["error"]["message"][] = "'parts' is missing";
}
if ($result["state"] === false) {
  http_response_code(422);
  echo json_encode($result);
  exit;
}

// Формирование данных
$sendData["store_order_id"] = $input["userId"] . "-" . mt_rand(1000000, 9999999);
$sendData["client_phone"] = $input["phone"];
$sendData["total_sum"] = str_replace(array(",", " "), array(".", ""), $input["amount"]);
// $sendData["amount"] = $amount * 100;
settype($sendData["total_sum"], "float");
$sendData["invoice"] = [
  "date" => date("Y-m-d"),
  "number" => $sendData["store_order_id"],
  "source" => "INTERNET"
];
$sendData["available_programs"] = [[
  "type" => "payment_installments",
  "available_parts_count" => [$input["parts"]]
]];
$sendData["products"][0]["name"] = $input["productName"];
if ($input["productCount"] != NULL) {
  settype($input["productCount"], "int");
  if ($input["productCount"] >= 1) {
    $sendData["products"][0]["count"] = $input["productCount"];
  } else {
    $sendData["products"][0]["count"] = 1;
  }
} else {
  $sendData["products"][0]["count"] = 1;
}
$sendData["products"][0]["sum"] = $sendData["total_sum"] / $sendData["products"][0]["count"];
settype($sendData["products"][0]["sum"], "float");
$sendData["result_callback"] = $url . "callback.php";

$headers[] = "signature: " . base64_encode(hash_hmac("sha256", json_encode($sendData, JSON_UNESCAPED_UNICODE), $monoSecret, true));
$headers[] = "store-id: " . $monoId;


$response = json_decode(send_request($monoLink . "/api/order/create", $headers, "POST", $sendData), true);
if ($response["order_id"] != NULL) {
  if (file_exists("orders") !== true) {
    mkdir("orders");
  }
  file_put_contents("orders/" . $response["order_id"], json_encode([
    "userId" => $input["userId"],
    "action" => $input["action"],
    "actions" => $input["actions"]
  ], JSON_UNESCAPED_UNICODE));
}
$result["mono"] = $response;
$result["sendData"] = $sendData;
$result["headers"] = $headers;


echo json_encode($result, JSON_UNESCAPED_UNICODE);
