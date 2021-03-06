<?php

include_once './config/kissorm/Bootstrap.php';
$client = php_sapi_name();

if ($client == 'cli') {
    $params = $argv;
    define('EOL', PHP_EOL);
} else {
    $params = array('');
    
    foreach ($_REQUEST as $key => $value) {
        $params[] = $key;
    }
    define('EOL', '<br>');
}

if ($client != 'cli') {
    echo "<pre>";
}
//echo json_encode($params);
//echo $params[0];
//echo $params[1];

$allowedParams = array('ALL', 'TABLES', 'VIEWS');

$bAllDB = $bTables = $bViews = $bUseINIFile = false;

if ($params) {
    $param1 = (isset($params[1])) ? strtoupper(trim($params[1])) : '';
    if ($param1) {
        $bUseINIFile = false;
        if (!in_array($param1, $allowedParams)) {
            echo EOL;
            echo '    El parámetro indicado no se corresponde con alguno esperado.'.EOL;
            echo '        All -> todas las Tablas y Vistas de la Base de Datos'.EOL;
            echo '     TABLES -> todas las Tablas de la Base de Datos'.EOL;
            echo '      VIEWS -> todas las Vistas de la Base de Datos'.EOL;
            echo EOL;
        }
        if ($param1 == 'ALL') {
            $bAllDB = true;
        }
        if ($param1 == 'TABLES') {
            $bTables = true;
        }
        if ($param1 == 'VIEWS') {
            $bViews = true;
        }        
    }
}

echo "   ALL: ".(($bAllDB) ? '1' : '0').EOL;
echo "TABLES: ".(($bTables) ? '1' : '0').EOL;
echo " VIEWS: ".(($bViews) ? '1' : '0').EOL;

function getDbInfo($attrib = 'name') {
    $dbConfig = new \levitarmouse\core\ConfigIni(KISSORM_DB_CONFIG);
    $value = '';
    $engine = $dbConfig->get('DEFAULT.EngineToUse');
    if ($attrib == 'name') {
        $value = $dbConfig->get($engine.'.dbname');
    }
    return $value;
}


if ($bAllDB) {
    
    $dbname = getDbInfo('name');
    $queryTables = "Show FULL Tables In {$dbname} where table_type like '%TABLE'";
    $queryViews = "Show FULL Tables In {$dbname} where table_type like '%VIEW'";
    
    $model = new \levitarmouse\kiss_orm\GenericEntity();
    $aTables = $model->getMapper()->select($queryTables);
    $aViews = $model->getMapper()->select($queryViews);
    
}

//
//die;


echo EOL;
echo "---------------------------------------------";
echo EOL;
echo "-- Autogeneración de Descriptores y Clases --";
echo EOL;
echo "---------------------------------------------";
echo EOL;

$tables = array();

// List of tables for which you want to create INI descriptors
// AS: Table Name-> array index. Class name -> value;

    if ($bUseINIFile) {
        if (file_exists('ormModels.ini')) {
            $listTables = parse_ini_file('ormModels.ini', true, INI_SCANNER_RAW);
            $tables = $listTables['tables'];
        } else {
            echo "No se halló el archivo ./ormModels.ini".EOL;
            echo "El mismo se utiliza para determinar la lista de objetos de la base de datos a mapear ...".EOL;
            echo "".EOL;
            echo "".EOL;
        }
    } else {
        
        if ($bAllDB) {
            $tables = array();
            $descriptor = new stdClass();
            foreach ($aTables as $key => $value) {
                $descriptor->NameSpace = '';
                $tableName = $aTables[$key]['Tables_in_'.strtolower($dbname)];
                $descriptor->ClassName = ucfirst($tableName);
                $tables[$tableName] = json_encode($descriptor);
            }
        }
    }

        $nTables = count($tables);
        if ($nTables < 1) {
            echo "   "."|=======================================================".EOL;
            echo "   "."|   No se halló configuración para generar descriptores ".EOL;
            echo "   "."|=======================================================".EOL;
        }
//    }

        ////////////////////////////////////////////
        // INCLUSIÓN NECESARIA
        // Previo al primer uso de kiss_orm
        ////////////////////////////////////////////
        if (file_exists('./config/kissorm/Bootstrap.php')) {
            include_once './config/kissorm/Bootstrap.php';
        }

        // Query base of DescGen engine!
        $query = 'desc ';

        // Empty Model to execute simple queries through ORM SMapper
        $model = new \levitarmouse\kiss_orm\GenericEntity();

        // Retrive Database configuration
        $dbConfig = new \levitarmouse\core\ConfigIni(KISSORM_DB_CONFIG);

        $engine = $dbConfig->get('DEFAULT.EngineToUse');

        $dbname = $dbConfig->get($engine.'.dbname');

        $destination = KISSORM_GEN_DESCRIPTORS_PATH;

        if (!file_exists($destination)) {
            @$bMkDir = mkdir($destination, 0777, true);

            if ($bMkDir) {

                echo EOL;
                echo "---------------------------------------------". EOL;
                echo "--- Se creó el directorio " . $destination . EOL;
                echo "---------------------------------------------";
                echo EOL;
                echo "En ella se almacenarán los descriptores y Classes asociadas al ORM" . EOL;
            } else {
                echo EOL;
                echo "Sin embargo ...". EOL;
                echo EOL;
                echo "No se pudo crear el directorio :" . $destination . EOL;
                echo EOL;
                echo "Se requieren permisos sobre el sistema de archivos para hacerlo!" . EOL;
                echo EOL;
                echo "Acceda desde una consola a el directorio:".EOL;
                echo realpath(__DIR__."/../../../"). EOL;
                echo " y ejecute el siguiente comando:".EOL;
                echo '$ php -f ormKissGen.php'.EOL;
                die;
            }
        }

        $output = array();

        $continue = false;

        $resultTables = array();

        try {

            $showInfo = false;

            foreach ($tables as $table => $data) {

                $psr0Path = '';

                $info = new stdClass();

                $info->tableName = $table;

                $info->className = '';
                $info->nameSpace = '';

                $objectType = validateTable($table);

                if (!$objectType) {
                    echo PHP_EOL."WARNING La tabla ".$table." no existe en la base de datos ".$dbname.PHP_EOL;
                    $resultTables[] = $info;
                    continue;
                }

                $oData = extractModelData($data);
                $className = trim($oData->ClassName);
                $NameSpace = trim($oData->NameSpace);

                $aNameSpace = explode('.', $NameSpace);

                $aNameSpace = array_map('trim', $aNameSpace);

                $psr0Destination = $destination;
                if (count($aNameSpace) > 1) {

                    $psr0Path = implode('/', $aNameSpace);

                    $psr0Destination .= $psr0Path;

                    echo "* Destination Folder ".$destination.PHP_EOL;
                    echo "* Creating PSR0 tree ".$psr0Destination.PHP_EOL;

                    if (!file_exists($psr0Destination)) {
                        $bMkDir = mkdir($psr0Destination, 0777, true);
                    }

                }

                $result = $model->getMapper()->select($query . ' ' . $table);

                $primaryKey = array();
                if (is_array($result)) {

                    $className = (!empty($className)) ? $className : ucfirst($data);
                    $className = ($className) ? $className : ucfirst($table);

                    $bJson = is_object(json_decode($className));
                    if ($bJson) {
                        $className = ucfirst($table);
                    }

                    $descriptor = fopen($psr0Destination . '/' . $className . '.ini', 'w+');

                    $secTable  = '[table]' . PHP_EOL;
                    $secTable .= 'schema = ' . $dbname . PHP_EOL;
                    $secTable .= 'table  = ' . $table . PHP_EOL;
                    $secTable .= PHP_EOL;

                    fwrite($descriptor, $secTable);

                    $details = '[details]' . PHP_EOL . PHP_EOL;
                    fwrite($descriptor, $details);

                    $fields = '[fields]' . PHP_EOL;
                    fwrite($descriptor, $fields);

                    foreach ($result as $key => $value) {

                        $field = $value['Field'];

                        $Type = str_pad($value['Type'], 13, ' ', STR_PAD_RIGHT);
                        $Null = str_pad($value['Null'], 13, ' ', STR_PAD_RIGHT);
                        $pk = str_pad($value['Key'], 13, ' ', STR_PAD_RIGHT);
                        $Default = str_pad($value['Default'], 13, ' ', STR_PAD_RIGHT);
                        $Extra = str_pad($value['Extra'], 13, ' ', STR_PAD_RIGHT);

                        $line1 = str_pad($field, 20, ' ', STR_PAD_RIGHT) . ' = ';
                        $line2 = str_pad(strtoupper($field), 20, ' ', STR_PAD_RIGHT) . ' ; ';
                        $line3 = $Type . ' |' . $Null . ' |' . $pk . ' |' . $Default . ' |' . $Extra;

                        $line = $line1 . $line2 . $line3 . PHP_EOL;

                        fwrite($descriptor, $line);

                        $bPK = (strtoupper(trim($pk)) == 'PRI');
                        if ($bPK) {
                            $primaryKey[$field] = strtoupper($field);
                        }
                    }

                    $fields = PHP_EOL . '[fields_read]' . PHP_EOL;
                    fwrite($descriptor, $fields);

                    $fields = PHP_EOL . '[fields_write]' . PHP_EOL;
                    fwrite($descriptor, $fields);

                    $fields = PHP_EOL . '[primary_key]' . PHP_EOL;
                    fwrite($descriptor, $fields);

                    if (count($primaryKey) > 0) {
                        foreach ($primaryKey as $primaryKeyattrib => $primaryKeyfield) {
                            $pkString = str_pad($primaryKeyattrib, 13, ' ', STR_PAD_RIGHT) . ' = ' . $primaryKeyfield . PHP_EOL;
                            fwrite($descriptor, $pkString);
                        }
                    }

                    $fields = PHP_EOL . '[unique_key]' . PHP_EOL;
                    fwrite($descriptor, $fields);

                    fclose($descriptor);

                    $continue = true;

                    makePhpClass($result, $className, $aNameSpace, $objectType, $psr0Destination);

                    $info->className = $className;
                    $info->nameSpace = trim(implode('\\', $aNameSpace).PHP_EOL);

                    $resultTables[] = $info;

                } else {
                    $output[] = $result;
                    $output[] = "Revise el archivo config/database.ini";
                    foreach ($output as $msg) {
                        echo $msg.EOL.EOL;
                    }
                }
            }

            $showInfo = true;

        } catch (\Exception $ex) {
            echo "Se produjo un error. "."Revise la configuración de acceso a la base de datos en el archivo config/kissorm/database.ini".EOL;
        }


        if ($showInfo) {
            echo EOL;
            echo "   " . "|===============================================================================================|" . EOL;
            echo "   " . "|  SEGÚN LAS SIGUIENTES  |        SE GENERÓ LA SIGUIENTE LISTA DE ELEMENTOS                     |" . EOL;
            echo "   " . "|===== TABLAS/VISTAS=====|====== CLASSes =======|============ NAMESPACEs =======================|" . EOL;
            foreach ($resultTables as $key => $data) {

                $tableName = str_pad($data->tableName.'  ', 19, '_', STR_PAD_LEFT);
                $className = str_pad('  '.$data->className.'  ', 22, ' ', STR_PAD_RIGHT);

                if (empty(trim($className))) {
                    $className = str_pad('<- No se creó Class   ', 22, ' ', STR_PAD_RIGHT);

                }

                $nameSpace = str_pad('  '.$data->nameSpace.'  ', 47, ' ', STR_PAD_RIGHT);

                echo "   " . "\_____" . $tableName . '|' . $className . '|' . $nameSpace .'|'. EOL;
            }
            echo EOL;
        }
        else {
            echo "   " . "|=======================================================" . EOL;
            echo "   " . "|   Se produjo un error. No se crearon los descriptores  " . EOL;
            echo "   " . "|=======================================================" . EOL;
        }


if ($client != 'cli') {
    echo "</pre>";
}

function extractModelData($data) {

    $oData = json_decode($data, true);

    $className = '';
    $nameSpace = '';

    if (is_array($oData)) {

        $oData['ClassName'] = isset($oData['ClassName']) ? $oData['ClassName'] : ' ';
        $oData['NameSpace'] = isset($oData['NameSpace']) ? $oData['NameSpace'] : ' ';

        $className = str_pad($oData['ClassName'].'  ', 24, ' ', STR_PAD_RIGHT);
        $nameSpace = str_pad($oData['NameSpace'].'  ', 33, ' ', STR_PAD_RIGHT);
    }

    if (is_object($oData)) {
        $oData->ClassName = isset($oData->ClassName) ? $oData->ClassName : ' ';
        $oData->NameSpace = isset($oData->NameSpace) ? $oData->NameSpace : ' ';

        $className = str_pad($oData->ClassName.'  ', 24, ' ', STR_PAD_RIGHT);
        $nameSpace = str_pad($oData->NameSpace.'  ', 33, ' ', STR_PAD_RIGHT);
    }

    if (is_string($oData)) {
        $className = trim($data);
        $nameSpace = '';
    }

    $object = new stdClass();
    $object->ClassName = trim($className);
    $object->NameSpace = trim($nameSpace);

    return $object;
}


function makePhpClass($result, $className, $aNameSpace, $objectType, $psr0Destination = '') {

    $isView = preg_match('(VIEW)', strtoupper($objectType));

    $parentName = ($isView) ? 'ViewModel' : 'EntityModel';

    $file = $psr0Destination . '/' . $className . '.php';

    $phpFile = fopen($file, 'w+');

    $code = <<<CODE
<?php
/*
 * CODIGO AUTOGENERADO POR kissGen. KISS-ORM
 */
{{namespace}}
/**
 * Description of $className
 *
{{properties}}
 */
class $className extends \levitarmouse\kiss_orm\\{$parentName}
{

}
CODE;

    $properties = '';
    $first = true;
    foreach ($result as $key => $value) {

        $field = $value['Field'];
        $field = str_pad($field, 15, ' ', STR_PAD_RIGHT);

        $type = $value['Type'];

        $nl = ($first) ? '' : PHP_EOL;

        $properties .= $nl.' * @property $' . $field . '      ' . $type ;
        $first = false;
    }

    $code = str_replace('{{properties}}', $properties, $code);


    if ($aNameSpace) {
        $nameSpace = trim(implode('\\', $aNameSpace).PHP_EOL);

        if (!empty($nameSpace)) {
            $code = str_replace('{{namespace}}', 'namespace '.$nameSpace.';', $code);
        } else {
            $code = str_replace('{{namespace}}', '', $code);
        }
    } else {
        $code = str_replace('{{namespace}}', '', $code);
    }


    fwrite($phpFile, $code);

    fclose($phpFile);

    setPermissions($file);

}

function setPermissions($path) {
//    shell_exec('chmod -R 755 '.$path);
//    chmod($path, '0764');
}



function validateTable($tableName) {

    global $dbConfig;

    $engine = $dbConfig->get('DEFAULT.EngineToUse');
    $dbname = $dbConfig->get($engine.'.dbname');

    $query = '';
    switch ($engine) {
        default:
        case 'MYSQL':
            $query = <<< QUERY
                SELECT TABLE_TYPE
                  FROM information_schema.tables
                 WHERE TABLE_SCHEMA = '{$dbname}'
                   AND TABLE_NAME = '{$tableName}'
QUERY;
            break;
    }

    $model = new levitarmouse\kiss_orm\GenericEntity();

    $result = $model->getMapper()->select($query);

    $type = (isset($result[0]['TABLE_TYPE'])) ? $result[0]['TABLE_TYPE'] : '';

    return $type;
}