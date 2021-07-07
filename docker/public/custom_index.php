<?php
$content = file_get_contents('php://input');

$response_json = json_decode($content, true);
$response_options = $response_json['response'];
foreach ($response_options['headers'] as $key => $value) {
    header("{$key}: {$value}");
}
header("Content-Type: {$response_options['content_type']}");
echo($response_options['body']);