<?php
require_once('WOVN.php/src/wovn_interceptor.php');

$content = file_get_contents('php://input');

$response_json = json_decode($content, true);
$response_options = $response_json['response'];
foreach ($response_options['headers'] as $key => $value) {
    header("{$key}: {$value}");
}
$status = $response_options['status'];
header("X-PHP-Response-Code: $status", true, $status);
header("Content-Type: {$response_options['content-type']}");
echo($response_options['body']);
