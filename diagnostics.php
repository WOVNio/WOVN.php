<?php
require_once(__DIR__ . '/src/version.php');
require_once(__DIR__ . '/src/wovn_helper.php');

function getVersion() {
  return WOVN_PHP_VERSION;
}

function getPhpInfo() {
  ob_start();
  phpinfo();
  return ob_get_clean();
}

function getHtaccess() {
  $path = getcwd() . "/../.htaccess";
  if (is_file($path)) {
    $htaccess = file_get_contents("../.htaccess");
    $htaccess = htmlspecialchars($htaccess);
    return "<pre>" . $htaccess . "</pre>";
  } else {
    return "not found $path";
  }
}

function getDir() {
  #return join("<br>", scandir("../"));
  $all = array();
  $excludes = array("..", ".", "wovnphp");
  $limit = 10000;
  scanDirectories("..", $all, $excludes, $limit);
  return count($all) . " items<br>" . join("<br>", $all);
}

function scanDirectories($root, &$all, $excludes, &$limit) {
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
      scanDirectories($path, $all, $excludes, $limit);
    }
  }
}

function getBaseUrl() {
  $path = $_SERVER["REQUEST_URI"];
  $path = dirname(dirname($path));
  $url = $_SERVER["REQUEST_SCHEME"] . "://" . reduce_slashes($_SERVER["HTTP_HOST"] . $path . "/");
  return $url;
}

function getOrderedUrl() {
  if (array_key_exists("url", $_GET)) {
    return $_GET["url"];
  }
  return getBaseUrl();
}

function getOriginal() {
  $url = getOrderedUrl() . "?off_wovn_php=1";
  return file_get_contents($url);
}

function getWithWovn() {
  $url = getOrderedUrl();
  $option = array("http" => array(
    'header' => "Accept: text/html"
  ));
  return file_get_contents($url, false, stream_context_create($option));
}

function getDiagnosticsHtaccess($user, $pass) {
  $url = getBaseUrl();
  $url .= '?enable_wovn_trace_htaccess=1';
  $option = array("http" => array(
    'header' => "Authorization: Basic " . base64_encode($user . ":" . $pass)
  ));
  return file_get_contents($url, false, stream_context_create($option));
}

function getAll($user, $pass) {
  $results = array();
  foreach (array("getVersion", "getHtaccess", "getDir", "getBaseUrl", "getOrderedUrl", "getDiagnosticsHtaccess", "getOriginal", "getWithWovn", "getPhpInfo") as $func) {
    $name = str_replace("get", "", $func);
    $result = '<h2 style="font-weight: normal">' . $name . '</h2><div style="margin: 20px 10px">';
    try {
      $result .= $func($user, $pass);
    } catch (Exception $e) {
      $result .= $e;
    }
    $result .= "</div>";
    array_push($results, $result);
  }
  return join("\n<hr>\n", $results);
}

function setAuthHeaders() {
  header('HTTP/1.1 401 Unauthorized');
  header('WWW-Authenticate: Basic realm="wovn authentication"');
}

function getAuthUser() {
  if(array_key_exists('PHP_AUTH_USER', $_SERVER)) {
    return $_SERVER['PHP_AUTH_USER'];
  }

  return null;
}

function getAuthPassword() {
  if(array_key_exists('PHP_AUTH_PW', $_SERVER)) {
    return $_SERVER['PHP_AUTH_PW'];
  }

  return null;
}

function auth($user, $password) {
  $htpasswd_filename = '.htpasswd';

  if($user != null && $password != null && file_exists($htpasswd_filename)) {
    $htpasswd_file = file($htpasswd_filename);
    $htpasswd_match = preg_grep("/$user:.*$/", $htpasswd_file);
    $user_auth_line = array_shift($htpasswd_match);
    preg_match("/^$user:((\\$2y\\$05\\$.{22}).*)$/", $user_auth_line, $matches);
    $correct_password = $matches[1];
    $salt = $matches[2];
    $encrypted_password = crypt($password, $salt);

    return ($encrypted_password == $correct_password);
  }

  return false;
}

function diagnosticInstructions() {
  $request_url = $_SERVER["REQUEST_URI"];
  $url = getOrderedUrl();
?>
  <h1>WOVN.PHP Diagnostic</h1>
  <h3>Instructions</h3>
  <ol>
    <li>Make sure <code>wovnphp_diagnostics.html</code> exists in the <code>WOVN.php</code> directory. This will be the file where we write the diagnostic report into.</li>
    <li>Set write permission to the file (use <code>chmod a+w WOVN.php/wovnphp_diagnostics.html</code>, or change permission from FTP client).</li>
    <li>Write the URL of your webpage that is causing problem in the &quot;URL to diagnose&quot; input below.</li>
    <li>Click on &quot;Start!&quot;.</li>
    <li>Copy the file and send it to us.</li>
    <li>Remove the file from your server.</li>
  </ol>
  <p>By default, the generated report file is not accessible via a browser.</p>
  <h3>Start diagnostic</h3>
  <form action="<?php echo $request_url; ?>" method="GET" style="margin: 20px 10px">
    URL to diagnose: <input type="text" name="url" value="<?php echo $url; ?>" size="50">
    <br>
    <br>
    <input type="submit" value="Start!">
  </form>
<?php
}

if (auth(getAuthUser(), getAuthPassword())) {
  if (array_key_exists('url', $_GET) || array_key_exists('enable_wovn_trace_htaccess', $_GET)) {
    if (array_key_exists("enable_wovn_trace_htaccess", $_GET)) {
      function show($key, $dict) {
        echo $key . "=" . (array_key_exists($key, $dict) ? $dict[$key] : '(no value)') . '<br>';
      }
      show("lookahead", $_GET);
      show("subreq", $_GET);
    } else {
      $filename = 'wovnphp_diagnostics.html';
      if (file_exists($filename)) {
        $filepath = realpath(getcwd() . '/' . $filename);
        ob_start(function($buffer) use ($filepath) {
          if ($buffer) {
            if (file_put_contents($filepath, $buffer, LOCK_EX)) {
              return '<span style="color: #40b87c;">Diagnostic result has been saved successfully!</span>';
            }
            else {
              return '<span style="color: #ff5f5f;">Diagnostic result could not be saved, please check the file permissions (chmod a+w ' . $filepath . ')!</span>';
            }
          }
        });
        echo getAll(getAuthUser(), getAuthPassword());
      } else {
        echo '<span style="color: #ff5f5f;">The file ' . $filename . ' does not exist! <a href="/wovnphp/diagnostics.php">Try again</a></span>';
      }
    }
  } else {
    diagnosticInstructions();
  }
} else {
  setAuthHeaders();
}
