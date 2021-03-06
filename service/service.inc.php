<?php
$debug_queries = false;

// Need this for absolute URL configuration to be sef safe.
$protocol = empty($_SERVER['HTTPS']) ? 'http://' : 'https://';
$aurl = $protocol . $_SERVER['HTTP_HOST'] . str_replace('service/' . $type . '.php', '', $_SERVER['PHP_SELF']);

define('SERVICEPATH', realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
define('BASEPATH', realpath(str_replace('service/', '', SERVICEPATH)));

$db_version = 4000;
$db_versions = array(1100, 2100, 2500, 2600, 2620, 2710, 2800, 3000, 3001, 3002, 3004, 3110, 3120, 3130, 3140, 4000);
$time = time();

$version = '1.0.0-Beta-1';
$product = 'Feisty';

$lightbox = false;
$package = 'V-' . $version;
$doit = true; // RUN REAL QUERIES?
// Include modules.
include '../includes/PHPDS_utils.inc.php';

function warningHeadPrint($message)
{
	?>
    <div class="alert alert-warning">
        <strong>Warning!</strong><br><?php echo $message ?>.
    </div>
	<?php
}

function infoHeadPrint($message)
{
	?>
	<div class="alert alert-info">
		<strong>Information!</strong><br><?php echo $message ?>.
	</div>
	<?php
}

function displayDBfilling()
{
    ?>
        <div id="progress-bar" class="progress progress-striped active">
            <div id="progress" class="bar"></div>
        </div>
    <?php
}

function headHTML()
{
	global $TITLE, $aurl, $package;
	$skin = 'flick';
	?>
	<!DOCTYPE HTML>
	<html lang="en">
		<head>
			<title><?php echo $TITLE ?></title>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <meta name="keywords" content="install, upgrade">
            <meta name="description" content="install or upgrade phpdevshell framework">
            <link rel="stylesheet" href="<?php echo $aurl ?>themes/default/bootstrap/css/bootstrap.css" type="text/css">
            <link rel="stylesheet" href="<?php echo $aurl ?>themes/default/bootstrap/css/bootstrap-responsive.css" type="text/css">
            <link rel="stylesheet" href="<?php echo $aurl ?>service/service.css" type="text/css">
            <script type="text/javascript" src="<?php echo $aurl ?>themes/default/jquery/js/jquery-min.js"></script>
            <script type="text/javascript" src="<?php echo $aurl ?>themes/default/js/default.js"></script>
            <script type="text/javascript" src="<?php echo $aurl ?>themes/default/bootstrap/js/bootstrap.js"></script>
		</head>
		<body id="container">
            <header id="overview" class="jumbotron subhead">
                <div class="container">
                    <div>
                        <h1>Feisty</h1>
                        <p class="lead">
                            <?php echo $package ?> installation and upgrade service.
                        </p>
                    </div>
                </div>

            </header>
            <div class="container-fluid">
                <div id="bg" class="container">
                    <div>
	<?php
}

function footHTML()
{
	?>
                    </div>
                </div>
            </div>
		</body>
	</html>
	<?php
}

// Theming utilities.
function modPrint($moduleName) // more arguments can be provided
{
	global $module;
	$strings = func_get_args();
	$dep = array_shift($strings); // get rid of module name
	$result = empty($module[$moduleName]) ? implode($strings) : vsprintf($module[$moduleName], $strings);
	return $result;
}

function displayField($label, $field)
{
	global $data, $errors;

	$class = empty($errors[$field]) ? '' : 'required';

	print <<<HTML
		<p>
			<label for="$field">$label</label>
			<input id="$field" type="text" class="$class" size="30" name="$field" value="{$data[$field]}" required="required" title="$label">
		</p>
HTML;
}

function dumpEnv($content = '')
{
	$path = BASEPATH.DIRECTORY_SEPARATOR.'write'.DIRECTORY_SEPARATOR.'private';
	date_default_timezone_set('UTC');
	$file = 'envdump.'.date('YmdHis').'.txt';
	$path .= DIRECTORY_SEPARATOR.$file;

	ob_start();
	phpinfo();
	$content .= ob_get_clean();

	file_put_contents($path, "$content\n");
	return $file;
}

function displayErrors()
{
	global $errors;
	$count = count($errors);
	if ($count) {
		($count > 1) ? $m = "$count errors occured;" : $m = "The operation stopped because;";
        ?>
        <div class="alert alert-error">
        <strong><?php echo $m ?></strong><br>
        <ul>
        <?php
		foreach ($errors as $code => $description) {
			echo '<li>' . $description . '</li>';
		}
        ?>
        </ul>
        </div>
        <?php
		dumpEnv();
	}
}

function displayWarnings()
{
	global $warnings;
	$count = count($warnings);
	if ($count) {
		($count > 1) ? $m = "$count warnings occured;" : $m = "There is a warning;";
		infoHeadPrint($m);
		foreach ($warnings as $code => $description) {
			warningPrint(_($description));
		}
	}
}

function displayInstall()
{
	global $doit;

	$actual = $doit ? 'actual' : 'fake';
	messagePrint("Starting $actual installation");
	?>
	<script type="text/javascript">
		function updateProgress(p)
		{
			s = document.getElementById('progress');
			s.style.width = p + '%';

            if (p == 100) {
                $(document).ready(function() {
                    $("#progress-bar").removeClass('progress-striped active');
                });
            }
		}

	</script>
	<?php
}

// General utilities.
function preparations()
{
	// errors codes (fields are set by their names)
	define('kPHPVersion', 1);
	define('kApache', 2);
	define('kMYSQL', 3);
	define('kGETTEXT', 4);

	define('kMYSQLconnect', 20);
	define('kMYSQLselectDB', 21);
	define('kMYSQLnotempty', 22);
	define('kMYSQLversion', 23);
	define('kMYSQLquery', 24);
	define('kMYSQLempty', 25);
	define('kMYSQLuptodate', 26);

	define('kConfigNotFound', 30);
	define('kConfigDBName', 31);
	define('kConfigDBUserName', 32);
	define('kConfigDBPassword', 33);
	define('kConfigDBAddress', 34);
	define('kConfigDBPrefix', 35);
	define('kConfigSessionPath', 36);
	define('kConfigDBCompilePath', 37);


	set_error_handler('doHandleError');
	register_shutdown_function('doHandleShutdown');
}

function doHandleError($errno, $errstr, $errfile, $errline)
{
	print '<p>'._('A error has occurred!').'</p>';
	$error = '<p>'.sprintf(_('%s (code %s) in file "%s" at line %s'), $errstr, $errno, $errfile, $errline).'</p>';
	print $error;
	$file = dumpEnv($error);
	print '<p>'._('A dump file has been created with the name: ')."<tt>$file</tt></p>";
}

function doHandleShutdown()
	{
		$error = error_get_last();
		$errmask = error_reporting();
		if ($errmask & $error['type']) {
			doHandleError($error['type'], $error['message'], $error['file'], $error['line']);
		}
	}

function create_random_string($length = 4, $uppercase_only = false)
{
	if ($uppercase_only == true) {
		$template = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	} else {
		$template = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	}
	$length = $length - 1;
	$rndstring = false;
	$a = 0;
	$b = 0;
	settype($length, 'integer');
	settype($rndstring, 'string');
	settype($a, 'integer');
	settype($b, 'integer');
	for ($a = 0; $a <= $length; $a++) {
		$b = rand(0, strlen($template) - 1);
		$rndstring .= $template[$b];
	}
	return $rndstring;
}

function check_gettext()
{
	return function_exists('gettext');
}

// General Install supporting functions.
function check_apache()
{
	$server = $_SERVER["SERVER_SOFTWARE"];
	$pattern = '/([^\/]+)\/(\S+) ?(.*)?/';
	$matches = array();

	if (preg_match($pattern, $server, $matches)) {
		if ('Apache' == $matches[1])
			return $matches[2];
	}
	return false;
}

function check_mysql()
{
	$mysql_version = phpversion('mysql');

	if (!$mysql_version)
		return false;
	return true;
}

function checkField($field, $msg, $default)
{
	global $data;
	if (empty($_POST[$field])) {
		if (!empty($_POST))
			addError($field, $msg);
		$data[$field] = $default;
	} else {
		$data[$field] = $_POST[$field];
	}
}

function addError($code, $description)
{
	global $errors;
	$errors[$code] = $description;
}

function addWarning($code, $description)
{
	global $warnings;
	$warnings[$code] = $description;
}

function get_db_version()
{
	global $data;
	$db_prefix = $data['db_prefix'];
	$result = mysql_query("SELECT version FROM {$db_prefix}core_plugin_activation WHERE UPPER (plugin_folder) = 'PHPDEVSHELL'");
	if ($result) {
		$row = mysql_fetch_row($result);
		if ($row) {
			$result = $row[0];
		} else {
			$result = false;
		}
	}

	return $result;
}

function root_role()
{
	global $data;
	$db_prefix = $data['db_prefix'];
	return @mysql_result(mysql_query("SELECT setting_value FROM {$db_prefix}core_settings WHERE setting_description='Feisty_root_role'"), 0);
}

function guest_role()
{
	global $data;
	$db_prefix = $data['db_prefix'];
	return @mysql_result(mysql_query("SELECT setting_value FROM {$db_prefix}core_settings WHERE setting_description='Feisty_guest_role'"), 0);
}

function checkConfigFiles()
{
	global $data;
	global $errors;

	$configFolder = '../config/';
	$config_file = $configFolder . $data['config_file'];
	// Can we load the configuration file?
	if (!file_exists($config_file)) {
		addError('config_file', sprintf(_('The configuration file (%s) could not be read or found. Have you renamed the configuration file as specified in the readme file?'), $config_file));
	} else {
		try {
			// Include the config file.
			include_once $configFolder . 'PHPDS-defaults.config.php';
			@include_once $configFolder . 'single-site.config.php';
			require_once $config_file;
		} catch (Exception $e) {
			addError('config', _('Could not load the configuration files.'));
		}

		$db_settings = PU_GetDBSettings($configuration);

		// Check if we have a matching configuration file.
		if ($data['db_name'] != $db_settings['database']) {
			addError('db_name', _('Mismatching database name with the one provided in the config.php file.'));
		}
		if ($data['db_username'] != $db_settings['username']) {
			addError('db_username', _('Mismatching database username with the one provided in the config.php file.'));
		}
		if ($data['db_password'] != $db_settings['password']) {
			addError('db_password', _('Mismatching database password with the one provided in the config.php file.'));
		}
		if ($data['db_server'] != $db_settings['host']) {
			addError('db_server', _('Mismatching database server address (host) with the one provided in the config.php file.'));
		}
		if ($data['db_prefix'] != $db_settings['prefix']) {
			addError('db_prefix', _('Mismatching database prefix with the one provided in the config.php file.'));
		}
		// Check if folders are writable.
		if (!empty($configuration['session_path']) && !is_writeable('../' . $configuration['session_path'])) {
			addError(kConfigSessionPath, sprintf(_('Your session path or "write" directory (%s) is currently not writable, please check the readme/install file for instructions.'), '../' . $configuration['session_path']));
		}
		if (!is_writeable('../' . $configuration['compile_path'])) {
			addError(kConfigDBCompilePath, sprintf(_('Your compile path or "write" directory (%s) is currently not writable, please check the readme/install file for instructions.'), '../' . $configuration['compile_path']));
		}
	}
	return (count($errors) == 0);
}

/**
 * @date 20120306 (v1.1) (greg) support in-query relaxed error code
 *
 * To state that a sql query can fail on certains code, start the query with a special comment :
 *			/ *[1061]* /CREATE UNIQUE INDEX `index` USING BTREE ON `pds_core_node_structure`(`node_id`) ;
 * (remove the space between slashes and stars)
 * multiple codes can be separated with commas, NO SPACES allowed
 *
 * Common MySQL error codes:
 *
 * [1061] Duplicate key name 'index'
 * [1091] Can't DROP 'field'; check that column/key exists
 * [1068] Multiple primary key defined
 */
function stuffMYSQL()
{
	global $data, $doit, $type, $db_version, $version;

	// Going deeper, lets see if we can make a db connection.
	$connect_mysql = mysql_connect($data['db_server'], $data['db_username'], $data['db_password']);
	if (empty($connect_mysql)) {
		addError(kMYSQLconnect, sprintf(_('Unable to connect to the MySQL database %s, please make sure you entered all the relevant details correctly and that the MySQL server is currently running.'), mysql_error()));
		return false;
	}

	// Check if we can select our database.
	if (!mysql_select_db($data['db_name'])) {
		addError(kMYSQLselectDB, sprintf(_('Unable to select the specified database (%s). please make sure you entered all the relevant details correctly. The database should exists and be accessible by the user provided'), $data['db_name']));
		return false;
	}

	// Check if the databas is not perhaps already installed.
	$table_list = mysql_query('SHOW TABLES');
	$tables = @mysql_numrows($table_list);
	if ($type == 'install') {
		if (!empty($tables)) {
			addError(kMYSQLnotempty, _('There are tables in this database already, perhaps a previous Feisty installation?. This Installation script can not be used over an existing Feisty installation.'));
			return false;
		}
	} else if ($type == 'upgrade') {
		if (empty($tables)) {
			addError(kMYSQLempty, _('There are no existing tables in this database, are you sure Feisty is installed?.'));
			return false;
		}
		$phpds_db_ver = get_db_version();
		if ($phpds_db_ver == $db_version) {
			addError(kMYSQLuptodate, sprintf(_('<strong><span style="color: green">This specific upgrade version does not require database updates, system is running the latest current db version DB-%s which is used for %s. Try again when next update is released.</span></strong>'), $phpds_db_ver, $version));
			return false;
		}
	}

	// Check if the database version is up to date.
	if (mysql_get_server_info() < '5.0') {
		addError(kMYSQLversion, sprintf(_('This version of Feisty only supports MySQL version %s and later. You are currently running version %s.'), '5.0', mysql_get_server_info()));
		return false;
	}

	displayDBfilling();
	print "\n\n";
	ob_flush();
	flush();

	// Installation can now commence!
	$queries = get_queries();
	// Loop and execute queries.
	$i = 0;
	$max = count($queries);

	mysql_query('START TRANSACTION');
	$e = mysql_errno();
	if (!$e)
		foreach ($queries as $query) {
			if (!empty($query) && $doit) {
				if (!mysql_query($query))
					$em = mysql_error();
				usleep(8000);
			} else {
				usleep(8000);
			}
			if (connection_aborted()) {
				error_log('aborted');
				exit;
			}
			if ($e = mysql_errno()) {
				$matches = array();
				if (preg_match('#^\s*/\*\[([\d\,]+)\]\*/(.*)$#', $query, $matches)) {
					$accepted = explode(',', $matches[1]);
					if (in_array($e, $accepted)) {
						noticePrint(_('Accepting error').' '.$e.' ("'.$matches[2].'")');
					} else {
						break;
					}
				} else {
					break;
				}
			}
			$i++;
			$p = intval($i * 100 / $max);
			if (!empty($debug_queries)) {
				if (strpos($query, '#') === false) {
					messagePrint($query . " <strong>($p%)</strong>");
				} else {
					okPrint($query . " <strong>($p%)</strong>");
				}
			}
			if ($i % 10 == 0) {
				print "<script type=\"text/javascript\">updateProgress($p);</script>";
				ob_flush();
				flush();
			}
		}
	if ($e) {
		mysql_query('ROLLBACK');
		$error = sprintf(_('An error occurred trying to send the queries (query %d/%d).'), $i, $max);
		$error .= '<br>' . _('The error was') . ': [' . $e . '] ' . $em;
		$error .= '<br>' . _('The offending query was') . ': "' . $query . '"';
		addError(kMYSQLquery, $error);
		return false;
	}
	print "<script type=\"text/javascript\">updateProgress(100);</script>\n\n";
	ob_flush();
	flush();
	mysql_query('COMMIT');

	return true;
}

function doStage1()
{
	if (doSystemChecks()) {
		displayWarnings();
		checkFields();
		displayFields();
	} else
		displayErrors();
}

function doStage2()
{
	if (doSystemChecks()) {
		displayWarnings();
		if (checkFields()) {
			if (checkConfigFiles()) {
				if (doInstall())
					return true;
			}
		}
		displayErrors();
		displayFields();
	} else
		displayErrors();
	return false;
}

function doInstall()
{
	displayInstall();
	if (stuffMYSQL()) {
		displaySuccess();
		return true;
	}
	return false;
}

function doSystemChecks()
{
	// Do system checking.
	if (version_compare(phpversion(), "5.2.1", "<")) {
		addError(kPHPVersion, sprintf(_('This version of Feisty only supports PHP version %s and later. You are currently running version %s.'), '5.2.1', phpversion()));
	}
	if (check_apache() == false) {
		addWarning(kApache, _('You are not running Apache as your web server. This version of Feisty does not officially support non-Apache driven webservers.'));
	}
	if (check_mysql() == false) {
		addError(kMYSQL, _('The MySQL extension for PHP is missing. The installation script will be unable to continue'));
	}
	if (check_gettext() == false) {
		addError(kGETTEXT, _('The gettext extension for PHP is missing. The installation script will be unable to continue'));
	}
	global $errors;
	return (count($errors) == 0);
}

function headingPrint($heading_text)
{
	$HTML = <<<HTML
   		<h1>$heading_text</h1>

HTML;
	print $HTML;
}

function errorPrint($text)
{
	$HTML = <<<HTML
		<div class="alert alert-error">
			<button type="button" class="close" data-dismiss="alert">&times;</button>
			{$text}
		</div>

HTML;
	print $HTML;
}

function okPrint($text)
{
	$HTML = <<<HTML
		<div class="alert alert-success">
		    <button type="button" class="close" data-dismiss="alert">&times;</button>
			{$text}
		</div>

HTML;
	print $HTML;
}

function warningPrint($text)
{
	$HTML = <<<HTML
		<div class="alert">
			<button type="button" class="close" data-dismiss="alert">&times;</button>
			{$text}
		</div>

HTML;
	print $HTML;
}

function notePrint($text)
{
	$HTML = <<<HTML
		<div class="alert">
			<button type="button" class="close" data-dismiss="alert">&times;</button>
			{$text}
		</div>

HTML;
	print $HTML;
}

function messagePrint($text)
{
	$HTML = <<<HTML
		<div class="alert alert-info">
			<button type="button" class="close" data-dismiss="alert">&times;</button>
			{$text}
		</div>

HTML;
	print $HTML;
}

function criticalPrint($text)
{
	$HTML = <<<HTML
		<div class="alert alert-error">
			<button type="button" class="close" data-dismiss="alert">&times;</button>
			{$text}
		</div>

HTML;
	print $HTML;
}

function noticePrint($text)
{
	$HTML = <<<HTML
		<div class="alert">
			<button type="button" class="close" data-dismiss="alert">&times;</button>
			{$text}
		</div>

HTML;
	print $HTML;
}

