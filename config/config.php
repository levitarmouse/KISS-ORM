<?php
//
//define('CORE', 'levitarmouse');

if (!defined('ROOT_PATH')) {
    define("ROOT_PATH", realpath(__DIR__."/../")."/");
}
$root_path = ROOT_PATH;

$aMockComposerAutoload = explode('/', $root_path);
$garbage = array_pop($aMockComposerAutoload);
$garbage = array_pop($aMockComposerAutoload);
$garbage = array_pop($aMockComposerAutoload);
$garbage = array_pop($aMockComposerAutoload);

$mockComposerAutoload = implode('/', $aMockComposerAutoload)."/vendor/";

//if (!defined('LOGS_PATH')) {
//    define("LOGS_PATH", ROOT_PATH."logs/messages.logs");
//}


if (!defined('CONFIG_PATH')) {
    define("CONFIG_PATH", ROOT_PATH.'config/');
}
$config_path = CONFIG_PATH;

//if (!defined('SERVICE_PATH')) {
//    define("SERVICE_PATH", ROOT_PATH.'services/');
//}
//$service_path = SERVICE_PATH;

//if (!defined('BUSSINES_LOGIC_PATH')) {
//    define("BUSSINES_LOGIC_PATH", ROOT_PATH.'src/');
//}
//$app_path = APP_PATH;

define("VENDOR_PATH", ROOT_PATH.'vendor/');
$vendorPath = VENDOR_PATH;
    
//if (!defined('DB_CONFIG')) {
//	define ('DB_CONFIG', CONFIG_PATH.'database.ini');
//}

define ('DTOs_SOURCE', ROOT_PATH.'dto');

define ('INTERFACEs_SOURCE', ROOT_PATH.'interfaces');


$a_PSR0_Source = array();
$a_PSR0_Source[] = $mockComposerAutoload; // for alone use
$a_PSR0_Source[] = ROOT_PATH;
$a_PSR0_Source[] = VENDOR_PATH;
$a_PSR0_Source[] = DTOs_SOURCE;
$a_PSR0_Source[] = INTERFACEs_SOURCE;
//$aWebServicesPSR0[] = BUSSINES_LOGIC_PATH;
//$aWebServicesPSR0[] = VENDOR_PATH;


//$scriptName = filter_input(INPUT_SERVER, 'SCRIPT_NAME');
//$aLinkName  = explode('/', $scriptName);
//$garbage = array_pop($aLinkName);
//$linkName   = implode('/', $aLinkName);

//define('WWW_LINK_NAME', $linkName);


require_once 'Autoload.php';

