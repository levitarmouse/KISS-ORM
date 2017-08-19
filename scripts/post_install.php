<?php

echo "KISS ORM POST INSTALL SCRIPT";

$fwCfgPath = __DIR__.'/config';

$ormCfgPath = __DIR__.'/config/kissorm';

echo PHP_EOL;

echo "creando: ".$ormCfgPath.PHP_EOL;

if (!is_dir($fwCfgPath)) {
    mkdir($fwCfgPath);
}

if (!is_dir($ormCfgPath)) {
    mkdir($ormCfgPath);
}

$modelsInfo = './tables.ini';
$databaseCfg = './config/kissorm/database.ini';

if (!file_exists($modelsInfo)) {
    copy('./vendor/levitarmouse/kiss_orm/tables.ini.dist', $modelsInfo);
} else {
    echo 'INFO -> ALREADY EXIST ->'.$modelsInfo.PHP_EOL;
}

if (!file_exists($databaseCfg)) {
    copy('./vendor/levitarmouse/kiss_orm/config/database.ini.dist', $databaseCfg);
} else {
    echo 'INFO -> ALREADY EXIST ->'.$databaseCfg.PHP_EOL;
}

symlink('./vendor/levitarmouse/kiss_orm/kissGen.php', './kissGen.php');

symlink($ormCfgPath.'/../../vendor/levitarmouse/kiss_orm/config/Bootstrap.php', 'config/kissorm/Bootstrap.php');
