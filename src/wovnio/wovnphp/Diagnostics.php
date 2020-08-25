<?php
namespace Wovnio\Wovnphp;

require_once(__DIR__ . '/../../version.php');


/**
 * The Diagnostics class contains utilities for debugging live environments
 * 
 * To enable the diagnostics function, 'enable_wovn_diagnostics' in wovn.ini must be set to true.
 * 'wovn_diagnostics_username' and 'wovn_diagnostics_password' options in wovn.ini must be set accordingly as well.
 */
class Diagnostics
{
    const SALT = '4b73ec6b-a2e1-4ab9-85bc-d92f03d224e5';
    private $store;
    private $results;

    /**
     *  Constructor of the Diagnostics class
     *
     *  @return void
     */
    public function __construct($store)
    {
        $this->initResults();
        $this->store = $store;
    }
    
    /**
     * authenticate
     * Authenticates a username and password hash pair.
     * @param  string $userName
     * @param  string $passwordHash
     * @return boolean True if the password is a match, false otherwise
     */
    public function authenticate($userName, $passwordHash)
    {
        if (null === $this->store || !isset($this->store->settings['enable_wovn_diagnostics']) || !isset($this->store->settings['wovn_diagnostics_username'])) {
            return false;
        }

        if ($userName !== $this->store->settings['wovn_diagnostics_username']) {
            return false;
        }

        if (!isset($this->store->settings['wovn_diagnostics_password'])) {
            return false;
        }

        $hashInput = $userName . $this->store->settings['wovn_diagnostics_password'] . Diagnostics::SALT;
        $expectedPasswordHash = hash('sha256', $hashInput);
        return $passwordHash === $expectedPasswordHash;
    }

    public function renderResults()
    {
        $buffer = '<h1>WOVN.php Diagnostics Page</h1>';
        if (!$this->authenticate($_COOKIE["wovn_diagnostics_name"], $_COOKIE["wovn_diagnostics_hash"])) {
            $buffer .= '<p>You are not authorized to view this page.</p>';
            return $buffer;
        }
        foreach ($this->results as $item=>$result) {
            $title = strtoupper(implode(' ', explode('_', $item)));
            $buffer .= "<h2>{$title}</h2>";

            if (is_string($result)) {
                if (in_array($item, array('php_info', 'swapped_page', 'original_page'))) {
                    $buffer .= '<div style="border: solid;">' . $result . '</div>';
                } else {
                    $buffer .= "<textarea rows=\"5\" cols=\"60\">{$result}</textarea>";
                }
            }

            if (is_array($result)) {
                $buffer .= '<table border="solid"><tbody>';
                foreach($result as $subItem=>&$subResult) {
                    if (is_array($subResult)) {
                        $subResult = implode(', ', $subResult);
                    }
                    $buffer .= "<tr><td>{$subItem}</td><td>{$subResult}</td></tr>";
                }
                $buffer .= '</tbody></table>';
            }
        }
        return $buffer;
    }

    public function logPerformance($startTime, $endTime) {
        $this->results['performance_info'] = array('Swapping Time (ms)' => $endTime - $startTime);
    }

    public function logSwappedPage($swappedPage) {
        $this->results['swapped_page'] = $swappedPage;
    }

    public function logOriginalPage($originalPage) {
        $this->results['original_page'] = $originalPage;
    }

    private function initResults()
    {
        $this->results = array(
            'diagnostics_version' => '1.0',
            'php_version' => phpversion(),
            'wovn.php_version' => WOVN_PHP_VERSION,
            'wovn.ini' => $this->getWovnIni(),
            'wovn_index.php' => $this->getWovnIndex(),
            'index.php' => $this->getIndex(),
            'server_type' => $this->getServerType(),
            'server_config' => $this->getServerConfig(),
            'fastly_check' => $this->getFastlyAccessResult(),
            'dir_scan' => $this->getDir(),
            'php_info' => $this->getPhpInfo()
        );
    }

    private static function getWovnIni()
    {
        $settingFileName = isset($env['WOVN_CONFIG']) ? $env['WOVN_CONFIG'] : DIRNAME(__FILE__) . '/../../../../wovn.ini';

        if (file_exists($settingFileName)) {
            $userSettings = parse_ini_file($settingFileName, true);
        } else {
            $userSettings = array('Error' => 'Not Found');
        }

        return $userSettings;
    }

    private static function getWovnIndex()
    {
        $wovnIndexFile = DIRNAME(__FILE__) . '/../../../../wovn_index.php';

        if (file_exists($wovnIndexFile)) {
            $wovnIndex = file_get_contents($wovnIndexFile, true);
        } else {
            $wovnIndex = 'Not Found';
        }

        return $wovnIndex;
    }

    private static function getIndex()
    {
        $indexFile = DIRNAME(__FILE__) . '/../../../../index.php';

        if (file_exists($indexFile)) {
            $index = file_get_contents($indexFile, true);
        } else {
            $index = 'Not Found';
        }

        return $index;
    }

    private static function getServerType()
    {
        return $_SERVER['SERVER_SOFTWARE'] . 'via ' . $_SERVER['SERVER_PROTOCOL'];
    }

        
    /**
     * getServerConfig
     * Returns either the .htaccess file for Apache, or the config file for Nginx.
     * @return string
     */
    private static function getServerConfig()
    {
        $htaccessFile = DIRNAME(__FILE__) . '/../../../../.htaccess';

        if (file_exists($htaccessFile)) {
            $config = file_get_contents($htaccessFile, true);
        } else {
            // TODO: this is not easy but see if we can get the nginx config.
            $config = 'Not Found';
        }

        return $config;
    }
    
    /**
     * getFastlyAccessResult
     * Returns error caught when accessing fastly using SSL.
     * @return array
     */
    private static function getFastlyAccessResult()
    {
        $fastlyUrl = 'https://wovn.global.ssl.fastly.net/';
        $error = '';

        try {
            $contents = file_get_contents($fastlyUrl);
        } catch (Exception $e) {
            $error = 'Caught exception: ' . $e->getMessage() . "\n";
        }

        if (empty($error)) {
            $error = 'OK.';
        }
        return array('Result' => $error);
    }

    private static function getPhpInfo()
    {
        date_default_timezone_set('Asia/Tokyo');
        ob_start();
        phpinfo();
        return ob_get_clean();
    }

    private static function getDir()
    {
        $all = array();
        $excludes = array("..", ".", "WOVN.php");
        $limit = 10000;
        $cwd = getcwd();
        Diagnostics::scanDirectories($cwd, $all, $excludes, $limit);
        return count($all) . " items inside {$cwd}\n" . join("\n", $all);
    }

    private static function scanDirectories($root, &$all, $excludes, &$limit)
    {
        $dirs = scandir($root);
        foreach ($dirs as $dir) {
            if (in_array($dir, $excludes)) {
                continue;
            }
            if ($limit <= 0) {
                return;
            }
            --$limit;
            $path = $root . "/" . $dir;
            array_push($all, $path);
            if (is_dir($path)) {
                Diagnostics::scanDirectories($path, $all, $excludes, $limit);
            }
        }
    }
}
