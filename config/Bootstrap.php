<?php

if (!defined('ROOT_PATH')) {
    define("ROOT_PATH", realpath(__DIR__."/../../../../")."/");
}
$root_path = ROOT_PATH;

if (!defined('KISSORM_ROOT_PATH')) {
    define("KISSORM_ROOT_PATH", ROOT_PATH.'vendor/levitarmouse/kiss_orm/');
}
$root_path = KISSORM_ROOT_PATH;

$aRootProjectPath = explode('/', $root_path);
$garbage = array_pop($aRootProjectPath);
$garbage = array_pop($aRootProjectPath);
$garbage = array_pop($aRootProjectPath);
$garbage = array_pop($aRootProjectPath);

$composerInstalationPath = implode('/', $aRootProjectPath)."/vendor/";
$configPath              = implode('/', $aRootProjectPath)."/config/kissorm/";
$descriptorsPath         = implode('/', $aRootProjectPath)."/App/gen_models/";

if (!defined('KISSORM_CONFIG_PATH')) {
    define("KISSORM_CONFIG_PATH", $configPath);
}

if (!defined('KISSORM_GEN_DESCRIPTORS_PATH')) {
    define("KISSORM_GEN_DESCRIPTORS_PATH", $descriptorsPath);
}

if (!defined('KISSORM_DB_CONFIG')) {
	define ('KISSORM_DB_CONFIG', KISSORM_CONFIG_PATH.'database.ini');
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