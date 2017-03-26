<?php

//if (isset($argc) && isset($argv)) {
//    $actions = $argc;
//    $actionList = $argv;
//
//    if ($actions) {
//        echo json_encode($actions).PHP_EOL;
//        echo json_encode($actionList).PHP_EOL;
//        die;
//    }
//}

echo "---------------------------------------------";
echo PHP_EOL;
echo "--- Inciando autogeneración de Descriptores";
echo PHP_EOL;
echo "---------------------------------------------";
echo PHP_EOL;

$tables = array();

// List of tables for which you want to create INI descriptors
// AS: Table Name-> array index. Class name -> value;

if (file_exists('tables.ini')) {
    echo "Se halló tables.ini. Se utilizará para determinar la lista de tablas a mapear ..." . PHP_EOL;
    $listTables = parse_ini_file('tables.ini', true);

    $tables = $listTables['tables'];
} else {
    $tables['users'] = 'User';
    $tables[] = '';
}

$nTables = count($tables);
if ($nTables >= 1) {
    echo PHP_EOL;
    echo "   " . "f=======================================================" . PHP_EOL;
    echo "   " . "|            " . "Tables           |        Classes       " . PHP_EOL;
    echo "   " . "L_____________________________|_______________________" . PHP_EOL;
    foreach ($tables as $tabla => $class) {
        $tableName = str_pad($tabla, 24, '_', STR_PAD_LEFT);
        $className = str_pad($class, 24, ' ', STR_PAD_RIGHT);

        echo "   " . "L_____" . $tableName . '|' . $className . PHP_EOL;
    }
    echo PHP_EOL;
} else {
    echo "   " . "f=======================================================" . PHP_EOL;
    echo "   " . "|   No se halló configuración para generar descriptors  " . PHP_EOL;
    echo "   " . "L=======================================================" . PHP_EOL;
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
$dbConfig = new \levitarmouse\core\ConfigIni(__DIR__ . '/config/database.ini');

$engine = $dbConfig->get('DEFAULT.EngineToUse');

$dbname = $dbConfig->get($engine.'.dbname');

$destination = DESCRIPTORS_PATH;

if (!file_exists($destination)) {
    $bMkDir = mkdir($destination, 0777, true);

    if ($bMkDir) {
        echo "---------------------------------------------";
        echo "--- Se creó la carpeta " . $destination . PHP_EOL;
        echo "---------------------------------------------";
        echo PHP_EOL;
        echo "En ella se almacenarán los descriptores y Classes asociadas al ORM" . PHP_EOL;
    } else {
        echo "No se pudo crear la carpeta :" . $destination . PHP_EOL;
        echo "Se requieren permisos sobre el sistema de archivos para hacerlo!" . PHP_EOL;
        die;
    }
}

$output = array();

$continue = false;

try {
    
    foreach ($tables as $table => $className) {

        $result = $model->getMapper()->select($query . ' ' . $table);

        $primaryKey = array();
        if (is_array($result)) {

            $descriptor = fopen($destination . '/' . $className . '.ini', 'w+');

            $secTable = '[table]' . PHP_EOL;
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

            makePhpClass($result, $className);
        } else {
            $output[] = $result;
            $output[] = "Revise el archivo config/database.ini";
            foreach ($output as $msg) {
                echo $msg.PHP_EOL.PHP_EOL;                
            }
        }
    }
} catch (\Exception $ex) {
    echo "Se produjo un error. "."Revise la configuración de la base de datos en el archivo config/database.ini".PHP_EOL;
}


function makePhpClass($result, $className) {

    global $destination;
    $file = $destination . '/' . $className . '.php';

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

        $type = $value['Type'];

        $properties .= PHP_EOL.' * @property $' . $field . '      ' . $type ;
    }

    $code = str_replace('{{properties}}', $properties, $code);

    fwrite($phpFile, $code);

    fclose($phpFile);
}