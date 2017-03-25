<?php
//
//define('CORE', 'levitarmouse');

if (!defined('ROOT_PATH')) {
    define("ROOT_PATH", realpath(__DIR__."/../")."/");
}
$root_path = ROOT_PATH;

$aRootProjectPath = explode('/', $root_path);
$garbage = array_pop($aRootProjectPath);
$garbage = array_pop($aRootProjectPath);
$garbage = array_pop($aRootProjectPath);
$garbage = array_pop($aRootProjectPath);

$mockComposerAutoload = implode('/', $aRootProjectPath)."/vendor/";

$mockConfigPath       = implode('/', $aRootProjectPath)."/config/";

$descriptorsPath      = implode('/', $aRootProjectPath)."/descriptors/";


if (!defined('CONFIG_PATH')) {
    define("CONFIG_PATH", $mockConfigPath);
}

if (!defined('DESCRIPTORS_PATH')) {
    define("DESCRIPTORS_PATH", $descriptorsPath);
}
    
if (!defined('DB_CONFIG')) {
	define ('DB_CONFIG', CONFIG_PATH.'database.ini');
}

$a_PSR0_Source = array();
$a_PSR0_Source[] = $mockComposerAutoload; // for alone use

//require_once 'Autoload.php';

