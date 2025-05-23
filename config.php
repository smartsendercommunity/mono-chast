<?php

ini_set('max_execution_time', '1700');
set_time_limit(1700);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Content-Type: application/json; charset=utf-8');
http_response_code(200);

error_reporting(E_ERROR);
ini_set('display_errors', 1);

$ssToken = "";
$monoId = "";
$monoSecret = "";
$monoLink = "https://u2.monobank.com.ua";

function send_bearer($url, $token, $type = "GET", $param = [])
{
  $descriptor = curl_init($url);
  curl_setopt($descriptor, CURLOPT_POSTFIELDS, json_encode($param));
  curl_setopt($descriptor, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($descriptor, CURLOPT_HTTPHEADER, array("User-Agent: M-Soft Integration", "Content-Type: application/json", "Authorization: Bearer " . $token));
  curl_setopt($descriptor, CURLOPT_CUSTOMREQUEST, $type);
  $itog = curl_exec($descriptor);
  curl_close($descriptor);
  return $itog;
}
function send_request($url, $header = [], $type = "GET", $param = [], $raw = "json")
{
  $descriptor = curl_init($url);
  if ($type != "GET") {
    if ($raw == "json") {
      curl_setopt($descriptor, CURLOPT_POSTFIELDS, json_encode($param, JSON_UNESCAPED_UNICODE));
      $header[] = "Content-Type: application/json";
    } else if ($raw == "form") {
      curl_setopt($descriptor, CURLOPT_POSTFIELDS, http_build_query($param));
      $header[] = "Content-Type: application/x-www-form-urlencoded";
    } else {
      curl_setopt($descriptor, CURLOPT_POSTFIELDS, $param);
    }
  }
  $header[] = "User-Agent: M-Soft Integration(https://mufiksoft.com)";
  curl_setopt($descriptor, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($descriptor, CURLOPT_HTTPHEADER, $header);
  curl_setopt($descriptor, CURLOPT_CUSTOMREQUEST, $type);
  $itog = curl_exec($descriptor);
  curl_close($descriptor);
  return $itog;
}
$url = "https://" . $_SERVER["HTTP_HOST"] . dirname($_SERVER["PHP_SELF"]);
$url = explode("?", $url);
$url = $url[0];
if (substr($url, -1) != "/") {
  $url = $url . "/";
}


// testing server
// $monoId = "test_store_with_confirm";
// $monoSecret = "secret_98765432--123-123";
// $monoLink = "https://u2-demo-ext.mono.st4g3.com";
// predprod testing server
// $monoId = "COMFY";
// $monoSecret = "sign_key";
// $monoLink = "https://u2-ext.mono.st4g3.com";