<?php

//if (isset($argc) && isset($argv)) {
//    $actions    = $argc;
//    $actionList = $argv;
//
//    if ($actions) {
//        echo '$actions '.json_encode($actions).EOL;
//        echo '$actionList '.json_encode($actionList).EOL;
//    }
//
//    if ($actions == 2) {
//            echo "action".EOL;
//            $action = $actionList[1];
//            echo $action.EOL;
//    }
//
//    if ($actions == 3) {
//            echo "action".EOL;
//            $action = $actionList[1];
//            $data = $actionList[2];
//            echo $action.EOL;
//            echo $data.EOL;
//    }
//
//}
//
//die;

$client = php_sapi_name();

if ($client == 'cli') {
    define('EOL', PHP_EOL);
} else {
    define('EOL', '<br>');
}

if ($client != 'cli') {
    echo "<pre>";
}

echo EOL;
echo "---------------------------------------------";
echo EOL;
echo "--               KISS ORM                  --";
echo EOL;
echo "-- Autogeneración de Descriptores y Clases --";
echo EOL;
echo "---------------------------------------------";
echo EOL.EOL;

$tables = array();

// List of tables for which you want to create INI descriptors
// AS: Table Name-> array index. Class name -> value;

//if (!empty($action)) {
//
//    if ($action = 'run') {

        if (file_exists('tables.ini')) {
            echo "Se halló tables.ini. Se utilizará para determinar la lista de tablas y vistas a mapear ...".EOL.EOL;
            $listTables = parse_ini_file('tables.ini', true, INI_SCANNER_RAW);

            $tables = $listTables['tables'];
        } else {
            $tables['users'] = 'User';
            $tables[] = '';
        }

        $nTables = count($tables);
        if ($nTables < 1) {
            echo "   "."|=======================================================".EOL;
            echo "   "."|   No se halló configuración para generar descriptores ".EOL;
            echo "   "."|=======================================================".EOL;
        }

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

        $asProyectPath = __DIR__ . '/config/database.ini';
        $asLibraryPath = __DIR__ . '/../../../config/kissorm/database.ini';
        
        $asProyect = file_exists($asProyectPath);
        $asLibrary = file_exists($asLibraryPath);
        
        $dbConfig = null;
        if ($asProyect) {
            $dbConfig = new \levitarmouse\core\ConfigIni($asProyectPath);
        }
        
        if ($asLibrary) {
            $dbConfig = new \levitarmouse\core\ConfigIni($asLibraryPath);
        }

        if (!$dbConfig) {
            echo "".EOL;
            echo "Error".EOL;
            echo "No se encuentra el archivo de configuración para acceso a la Base de Datos".EOL;
            echo "Se espera hallarlo en:".EOL;
            echo "./config/kissorm/database.ini".EOL;
            echo "o ".EOL;
            echo "./vendor/levitarmouse/kissorm/config/database.ini".EOL;
            die;
        }
        
        $engine = $dbConfig->get('DEFAULT.EngineToUse');

        $dbname = $dbConfig->get($engine.'.dbname');

        $destination = KISSORM_DESCRIPTORS_PATH;

        if (!file_exists($destination)) {
            @$bMkDir = mkdir($destination, 0777, true);

            if ($bMkDir) {

                echo EOL;
                echo "---------------------------------------------".EOL;;
                echo "--- Se creó la carpeta " . $destination . EOL;
                echo "---------------------------------------------";
                echo EOL;
                echo "En ella se almacenarán los descriptores y Classes asociadas al ORM" . EOL;
            } else {
                echo EOL;
                echo "Sin embargo ...". EOL;
                echo EOL;
                echo "No se pudo crear la carpeta :" . $destination . EOL;
                echo EOL;
                echo "Se requieren permisos sobre el sistema de archivos para hacerlo!" . EOL;
                echo EOL;
                echo "Como alternativa acceda desde una consola a la carpeta:".EOL;
                echo realpath(__DIR__."/../../../"). EOL;
                echo " y ejecute el siguiente comando:".EOL;
                echo '$ php kissGen.php'.EOL;
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
                    echo EOL."WARNING La tabla ".$table." no existe en la base de datos ".$dbname.EOL;
                    echo EOL."Revise el archivo ./tables.ini ";
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

                    $psr0Destination = $destination.$psr0Path;
                    
                    echo "Destino de la generación: ".$destination.EOL.EOL;
                    echo "Creando la carpeta ".$psr0Destination.EOL;
                    
                    if (!file_exists($psr0Destination)) {
                        $bMkDir = mkdir($psr0Destination, 0777, true);
                    }

//                    $destination = $psr0Destination;
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

                    $secTable  = '[table]' . EOL;
                    $secTable .= 'schema = ' . $dbname . EOL;
                    $secTable .= 'table  = ' . $table . EOL;
                    $secTable .= EOL;

                    fwrite($descriptor, $secTable);

                    $details = '[details]' . EOL . EOL;
                    fwrite($descriptor, $details);

                    $fields = '[fields]' . EOL;
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

                        $line = $line1 . $line2 . $line3 . EOL;

                        fwrite($descriptor, $line);

                        $bPK = (strtoupper(trim($pk)) == 'PRI');
                        if ($bPK) {
                            $primaryKey[$field] = strtoupper($field);
                        }
                    }

                    $fields = EOL . '[fields_read]' . EOL;
                    fwrite($descriptor, $fields);

                    $fields = EOL . '[fields_write]' . EOL;
                    fwrite($descriptor, $fields);

                    $fields = EOL . '[primary_key]' . EOL;
                    fwrite($descriptor, $fields);

                    if (count($primaryKey) > 0) {
                        foreach ($primaryKey as $primaryKeyattrib => $primaryKeyfield) {
                            $pkString = str_pad($primaryKeyattrib, 13, ' ', STR_PAD_RIGHT) . ' = ' . $primaryKeyfield . EOL;
                            fwrite($descriptor, $pkString);
                        }
                    }

                    $fields = EOL . '[unique_key]' . EOL;
                    fwrite($descriptor, $fields);

                    fclose($descriptor);

                    $continue = true;

                    makePhpClass($result, $className, $aNameSpace, $objectType, $psr0Destination);

                    $info->className = $className;
                    $info->nameSpace = trim(implode('\\', $aNameSpace).EOL);

                    $resultTables[] = $info;

                } else {
                    $output[] = $result;
                    $output[] = "Revise el archivo config/database.ini";
                    foreach ($output as $msg) {
                        echo $msg.EOL.EOL;
                    }
                }
            }

//            setPermissions($destination);

            $showInfo = true;

        } catch (\Exception $ex) {
            
            echo EOL;
            echo "!!! Un momento !!! ".EOL;
            echo "Se produjo un error. ".EOL."Revise la configuración de acceso a la base de datos en el archivo config/kissorm/database.ini".EOL;
            echo EOL;
            echo EOL;
            die;
        }


        if ($showInfo) {
            echo EOL;
            echo "   " . "|=====================================================================================|" . EOL;
            echo "   " . "|  SEGÚN LAS SIGUIENTES  |        SE GENERÓ LA SIGUIENTE LISTA DE ELEMENTOS           |" . EOL;
            echo "   " . "|===== TABLAS/VISTAS=====|====== CLASSes =======|============ NAMESPACEs =============|" . EOL;
            foreach ($resultTables as $key => $data) {

                $tableName = str_pad($data->tableName.'  ', 19, '_', STR_PAD_LEFT);
                $className = str_pad('  '.$data->className.'  ', 22, ' ', STR_PAD_RIGHT);
                
                if (empty(trim($className))) {
                    $className = str_pad('<- No se creó Class   ', 22, ' ', STR_PAD_RIGHT);

                }
                
                $nameSpace = str_pad('  '.$data->nameSpace.'  ', 37, ' ', STR_PAD_RIGHT);

                echo "   " . "\_____" . $tableName . '|' . $className . '|' . $nameSpace .'|'. EOL;
            }
            echo EOL;
        }
        else {
            echo "   " . "|=======================================================" . EOL;
            echo "   " . "|   No se halló configuración para generar descriptores  " . EOL;
            echo "   " . "|=======================================================" . EOL;
        }


if ($client != 'cli') {
    echo "</pre>";
}


//    }
//}
//else {
//    echo "kissGenn:".EOL;
//    echo EOL;
//    echo '$php kissgen -run: Generarar todo los descriptores y clases indicados en tables.ini'.EOL;
//    echo '$php kissgen -t TableName : Generarar el descriptor y class inidicados como parámetros'.EOL;
//    echo '$php kissgen -c ClassName: Generarar el descriptor y class inidicados como parámetros'.EOL;
//    echo '$php kissgen -ns NameSpace: Generarar el descriptor y class inidicados como parámetros'.EOL;
//    echo EOL;
//
//}

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

//    global $destination;
    $isView = preg_match('(VIEW)', strtoupper($objectType));
    
    $parentName = ($isView) ? 'ViewModel' : 'EntityModel';
    
    $file = $psr0Destination . '/' . $className . '.php';

    $phpFile = fopen($file, 'w+');

    $code = <<<CODE
<?php
/*
 * CODIGO AUTOGENERADO POR kissGen -> KISS-ORM
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