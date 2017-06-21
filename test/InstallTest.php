<?php

class InstallTest extends PHPUnit_Framework_TestCase {

  public function testInstall() {
    $this->checkErrors('/install.php');
  }

  public function testUninstall() {
    $this->checkErrors('/uninstall.php');
  }

  private function checkErrors($file) {
    $fullpath = dirname(dirname(__FILE__)) . $file;
    $command = 'php -d error_reporting=32767 ' . $fullpath;  // error_reporting=E_ALL
    exec($command, $output, $return_var);
    $this->assertEquals(0, $return_var);
    $this->assertEmpty(preg_grep("/^(Notice|Warning): /", $output));
  }
}

