<?php

if (!defined('ROOT_PATH')) {
    define("ROOT_PATH", realpath(__DIR__."/../")."/");
}
$root_path = ROOT_PATH;

$aRootProjectPath = explode('/', $root_path);
$garbage = array_pop($aRootProjectPath);
$garbage = array_pop($aRootProjectPath);
$garbage = array_pop($aRootProjectPath);
$garbage = array_pop($aRootProjectPath);

$composerInstalationPath = implode('/', $aRootProjectPath)."/vendor/";
$configPath              = implode('/', $aRootProjectPath)."/config/kissorm/";
$descriptorsPath         = implode('/', $aRootProjectPath)."/descriptors/";

if (!defined('CONFIG_PATH')) {
    define("CONFIG_PATH", $configPath);
}

if (!defined('DESCRIPTORS_PATH')) {
    define("DESCRIPTORS_PATH", $descriptorsPath);
}

if (!defined('DB_CONFIG')) {
	define ('DB_CONFIG', CONFIG_PATH.'database.ini');
}

function kissOrmAutoloader($sFullClassName) {

    global $composerInstalationPath;

    $paths = array();
    $paths[] = $composerInstalationPath;

    $aSteps = explode('\\', $sFullClassName);
    if ($aSteps) {
        foreach ($paths as $ruta ) {
            $sFile = $ruta . implode('/', $aSteps) . '.php';

            if (file_exists($sFile)) {
                require_once $sFile;
                return;
            }
        }
    }
}

spl_autoload_register('kissOrmAutoloader');