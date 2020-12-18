<?php
// index.php
$host  = $_SERVER['HTTP_HOST'];
$uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$extra = 'testdir/testpage-redirect-destination.php';
header("Location: http://$host$uri/$extra");
exit();
?>