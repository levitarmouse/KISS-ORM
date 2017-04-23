# KISS-ORM dev
Por Alejandro Gabriel Prieto (Levitarmouse)

Active record Basado en PDO. Compatible con PHP 7

Compatible con Mysql.
En futuras versiones será compatible con OracleDB y MongoDB.

Configuración actualmente necesaria para la correcta instalación en el proyecto padre.
En composer.json agregar:
:
    "scripts": {
        "post-update-cmd":  ["php -r \"shell_exec('mkdir -p config/kissorm');\"",
                             "php -r \"shell_exec('ln -s ./vendor/levitarmouse/kiss_orm/tables.ini .');\"",
                             "php -r \"shell_exec('ln -s ./vendor/levitarmouse/kiss_orm/kissGen.php .');\"",
                             "php -r \"shell_exec('cd ./config/kissorm/; ln -s ../../vendor/levitarmouse/kiss_orm/config/Bootstrap.php .');\"",
                             "php -r \"shell_exec('cd ./config/kissorm/; ln -s ../../vendor/levitarmouse/kiss_orm/config/database.ini .');\"",
                             ""],
        "post-package-install": [],
        "post-install-cmd": [],
        "post-autoload-dump": [],
        "post-create-project-cmd": []
    }

Notar en index.php!
/////////////////////////////////////////////////
// INCLUSIÓN NECESARIA 
// Previo al primer uso de dependencias kiss_orm
/////////////////////////////////////////////////
if (file_exists('./config/kissorm/Bootstrap.php')) {
    include_once './config/kissorm/Bootstrap.php';    
}