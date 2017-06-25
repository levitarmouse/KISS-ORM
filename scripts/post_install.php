<?php

echo "KISS ORM POST INSTALL SCRIPT";

$fwPath = __DIR__;

//$ormPath   = __DIR__.'/vendor/levitarmouse/kiss_orm';

$fwCfgPath = __DIR__.'/config';

$ormCfgPath = __DIR__.'/config/kissorm';

echo PHP_EOL;

echo "creando: ".$ormCfgPath.PHP_EOL;

if (!is_dir($fwCfgPath)) {
    mkdir($fwCfgPath);
}

mkdir($ormCfgPath);

copy('./vendor/levitarmouse/kiss_orm/tables.ini.dist', './tables.ini');
copy('./vendor/levitarmouse/kiss_orm/config/database.ini.dist', './config/kissorm/database.ini');

symlink('./vendor/levitarmouse/kiss_orm/kissGen.php', './kissGen.php');

symlink($ormCfgPath.'/../../vendor/levitarmouse/kiss_orm/config/Bootstrap.php', 'config/kissorm/Bootstrap.php');
