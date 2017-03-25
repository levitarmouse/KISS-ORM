# KISS-ORM dev
Por Alejandro Gabriel Prieto (Levitarmouse)

Active record Basado en PDO. Compatible con PHP 7


Notar en index.php!
/////////////////////////////////////////////////
// INCLUSIÓN NECESARIA 
// Previo al primer uso de dependencias kiss_orm
/////////////////////////////////////////////////
if (file_exists('./config/kissorm/Bootstrap.php')) {
    include_once './config/kissorm/Bootstrap.php';    
}