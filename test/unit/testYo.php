<?php
#$file = 'testFile.php';
#if (file_exists($file)) {
#  unlink($file);
#}
#$data = '<?php' . "\n" .
#        '$settingsArray = array(' . "\n" .
#        '  \'project_token\' => \'9ivAX\',' . "\n" .
#        '  \'backend_host\' => \'rs1.wovn.io\',' . "\n" .
#        '  \'backend_port\' => \'6379\',' . "\n" .
#        '  \'default_lang\' => \'English\',' . "\n" .
#        '  );' .  "\n" .
#        'return $settingsArray;'; 
#file_put_contents($file, $data);
$file = 'test.ini';
$testEmptyArray = parse_ini_file($file);
print_r($testEmptyArray);
