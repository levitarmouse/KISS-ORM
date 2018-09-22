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

//  files after installation
$modelsInfo  = './ormModels.ini';

$databaseCfg = './config/kissorm/database.ini';

if (file_exists($modelsInfo)) {
    echo 'INFO -> ALREADY EXIST ->'.$modelsInfo.PHP_EOL;
    echo ' WILL BE PRESERVED!!!!'.PHP_EOL;
} else {
    copy('./vendor/levitarmouse/kiss_orm/ormModels.ini.dist', $modelsInfo);
}



if (file_exists($databaseCfg)) {
    echo 'INFO -> ALREADY EXIST ->'.$databaseCfg.PHP_EOL;
    echo ' WILL BE PRESERVED!!!!'.PHP_EOL;
} else {
    copy('./vendor/levitarmouse/kiss_orm/config/database.ini.dist', $databaseCfg);
}

symlink('./vendor/levitarmouse/kiss_orm/ormKissGen.php', './ormKissGen.php');

symlink($ormCfgPath.'/../../vendor/levitarmouse/kiss_orm/config/Bootstrap.php', 'config/kissorm/Bootstrap.php');