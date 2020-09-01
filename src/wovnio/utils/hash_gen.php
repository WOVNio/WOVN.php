<?php
$username = $argv[1];
$password = $argv[2];
$salt = '4b73ec6b-a2e1-4ab9-85bc-d92f03d224e5';
$hashInput = $username . $password . $salt;
$expectedPasswordHash = hash('sha256', $hashInput);
echo($expectedPasswordHash . "\n");
