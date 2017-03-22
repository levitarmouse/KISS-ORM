<?php

if (isset($argc) && isset($argv)) {
    $actions = $argc;
    $actionList = $argv;

    if ($actions) {
        echo json_encode($actions).PHP_EOL;
        echo json_encode($actionList).PHP_EOL;
        die;
    }    
}

include_once './config/config.php';

// Load the composer autoloader
$path = ROOT_PATH;
$composerAutoloader = $path . '/vendor/autoload.php';
if (file_exists($composerAutoloader)) {
    require $composerAutoloader;
}

$tables = array();

// List of tables for which you want to create INI descriptors
// AS: Table Name-> array index. Class name -> value;

if (file_exists($path.'tables.ini')) {
    $listTables = parse_ini_file($path.'tables.ini', true);
    
    $tables = $listTables['tables'];
} else {
//    $tables['users'] = 'User';    
//    $tables[] = '';
}

//die;

// Query base of DescGen engine!
$query = 'desc ';

// Empety Model to execute simple queries through ORM SMapper
$model = new levitarmouse\kiss_orm\GenericEntity();

// Retrive Database configuration
$dbConfig = new \levitarmouse\core\ConfigIni(__DIR__ . '/config/database.ini');
$dbname   = $dbConfig->get('mysql.dbname');

//$destination = __DIR__ . '/descriptors';
$destination = ORM_MODELS_PATH;

if (!file_exists($destination)) {
    mkdir($destination, 755, true);
}

$output = array();

$continue = false;
foreach ($tables as $table => $className) {

    $result = $model->getMapper()->select($query . ' ' . $table);
    
    $primaryKey = array();
    if ($result) {

        $descriptor = fopen($destination . '/' . $className . '.ini', 'w+');

        $secTable  = '[table]'.PHP_EOL;
        $secTable .= 'schema = '.$dbname.PHP_EOL;
        $secTable .= 'table  = '.$table.PHP_EOL;
        $secTable .= PHP_EOL;
        
        fwrite($descriptor, $secTable);
        
        $details  = '[details]'.PHP_EOL.PHP_EOL;
        fwrite($descriptor, $details);
        
        $fields  = '[fields]'.PHP_EOL;
        fwrite($descriptor, $fields);
        
        foreach ($result as $key => $value) {
            
            $field = $value['Field'];
            
            $Type    = str_pad($value['Type'], 13, ' ', STR_PAD_RIGHT);
            $Null    = str_pad($value['Null'], 13, ' ', STR_PAD_RIGHT);
            $pk      = str_pad($value['Key'], 13, ' ', STR_PAD_RIGHT);
            $Default = str_pad($value['Default'], 13, ' ', STR_PAD_RIGHT);
            $Extra   = str_pad($value['Extra'], 13, ' ', STR_PAD_RIGHT);
            
            $line1 = str_pad($field, 20, ' ', STR_PAD_RIGHT).' = ';
            $line2 = str_pad(strtoupper($field), 20, ' ', STR_PAD_RIGHT).' ; ';
            $line3 = $Type.' |'.$Null.' |'.$pk.' |'.$Default.' |'.$Extra;
            
            $line = $line1.$line2.$line3.PHP_EOL;
            
            fwrite($descriptor, $line);
            
            $bPK = (strtoupper(trim($pk)) == 'PRI'); 
            if ($bPK) {
                $primaryKey[$field] = strtoupper($field);
            }
            
        }
        
        $fields  = PHP_EOL.'[fields_read]'.PHP_EOL;
        fwrite($descriptor, $fields);
        
        $fields  = PHP_EOL.'[fields_write]'.PHP_EOL;
        fwrite($descriptor, $fields);
        
        $fields  = PHP_EOL.'[primary_key]'.PHP_EOL;
        fwrite($descriptor, $fields);
        
        if (count($primaryKey) > 0) {
            foreach ($primaryKey as $primaryKeyattrib => $primaryKeyfield) {
                $pkString = str_pad($primaryKeyattrib, 13, ' ', STR_PAD_RIGHT).' = '.$primaryKeyfield.PHP_EOL;
                fwrite($descriptor, $pkString);
            }
        }
        
        $fields  = PHP_EOL.'[unique_key]'.PHP_EOL;
        fwrite($descriptor, $fields);

        fclose($descriptor);

        $continue = true;
        
        makePhpClass($result, $className);
        
        
    } else {
        echo json_encode($output);
    }
}




function makePhpClass($result, $className) {
    
    global $destination;
    $file = $destination.'/'.$className.'.php';
    
    $phpFile = fopen($file, 'w+');
 
    $code = <<<CODE
<?php
/*
 * CODIGO AUTOGENERADO POR kissDesc. KISS-ORM
 */

namespace classes;

/**
 * Description of $className
 * 
{{properties}}
 */
class $className extends \levitarmouse\kiss_orm\EntityModel
{

}
CODE;
    
    $properties = '';
    foreach ($result as $key => $value) {

        $field = $value['Field'];
        $field = str_pad($field, 15, ' ', STR_PAD_RIGHT);
        
        $type  = $value['Type'];
        
        $properties .= ' * @property $'.$field.'      '.$type.PHP_EOL;

    }

    $code = str_replace('{{properties}}', $properties, $code);

    fwrite($phpFile, $code);
    
    
    fclose($phpFile);    
}