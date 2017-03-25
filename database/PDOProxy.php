<?php

namespace levitarmouse\kiss_orm\database;

class PDOProxy
{
    private static $_link = null;

    private function __construct(\levitarmouse\core\ConfigIni $DbConfig)
    {
        $engine = $DbConfig->get('DEFAULT.EngineToUse');

        $engine = strtoupper($engine);

        $cfg = $DbConfig->get($engine);

        $driver = $cfg->driver;

        $cfgDbname = isset($cfg->dbname) ? $cfg->dbname : null;
        $cfgDriver = isset($cfg->driver) ? $cfg->driver : null;
        $cfgHost   = isset($cfg->host)   ? $cfg->host   : null;
        $cfgPass   = isset($cfg->pass)   ? $cfg->pass   : null;
        $cfgPort   = isset($cfg->port)   ? $cfg->port   : null;
        $cfgSid    = isset($cfg->sid)    ? $cfg->sid    : null;
        $cfgTns    = isset($cfg->tns)    ? $cfg->tns    : null;
        $cfgUser   = isset($cfg->user)   ? $cfg->user   : null;

        switch (strtolower($cfgDriver)) {
            case 'mysql':
                $Config = array(
                    'dsn' => array('host' => $cfgHost, 'dbname' => $cfgDbname),
                    'db_driver' => $cfgDriver,
                    'db_user' => $cfgUser,
                    'db_password' => $cfgPass,
                    'db_options' => '',
                    'db_attributes' => '',
                );

                $driver = $Config["db_driver"];
                $user = $Config["db_user"];
                $password = $Config["db_password"];
                $options = $Config["db_options"];
                $attributes = $Config["db_attributes"];

                $dsn = strtolower($driver) . ":";

                $dsns = array();
                foreach ($Config ["dsn"] as $k => $v) {
                    $dsns[] = "{$k}={$v}";
                }
                $dsn = $dsn . implode(';', $dsns);

                $opciones = array(
                    \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
                );

                try {
                    self::$_link = new \PDO($dsn, $user, $password, $opciones);
                    self::$_link->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                } catch (\Exception $ex) {
                    $message = new \levitarmouse\core\Response();

                    $message->setError(\levitarmouse\core\Response::DB_ACCESS_DENIED);

                    echo $message->errorDescription . PHP_EOL;
                }
                break;
            case 'oracledb':
                throw new \Exception(\levitarmouse\core\Response::DB_DRIVER_NOT_IMPLEMENTED);        
                break;
            case 'mongodb':
                throw new \Exception(\levitarmouse\core\Response::DB_DRIVER_NOT_IMPLEMENTED);            
                break;
            default:
                throw new \Exception(\levitarmouse\core\Response::DB_INVALID_DRIVER);            
                break;
        }

        return;
    }

    private static function _init($dbConfig)
    {
        $instance = null;
        if (self :: $_link) {
            $instance = self;
        }
        else {
            $instance = new PDOProxy($dbConfig);
        }
        return $instance;
    }

    public static function getInstance(\levitarmouse\core\ConfigIni $dbConfig)
    {
        if (self::$_link) {
            $instance = self::$_link;
        } else {
            $instance = self::_init($dbConfig);
        }

        return $instance;
    }

//    public function __call($name, $args)
//    {
//        if (self::$link) {
//            $callback = array(self :: $link, $name);
//            return call_user_func_array($callback, $args);
//        }
//    }
//
//    public static function __callStatic($name, $args)
//    {
//        if (self::$link) {
//
//            return call_user_func_array($name, $args);
//        }
//    }

//    protected static functoin execute($sSql)
//    {
//
//    }

    protected static function prepare($sSql)
    {
        $link = self::$_link;

        if (!$link) {
            throw new \Exception(\levitarmouse\core\Response::DB_ACCESS_FAILED);
        }
        $stmt = $link->prepare($sSql);
        return $stmt;
    }

    public function select($sQuery, $bind = array())
    {
        $stmt    = self::prepare($sQuery . ';');

        foreach ($bind as $key => $value) {
            $b = $stmt->bindValue($key, $value, \PDO::PARAM_STR);
        }

        $c = $stmt->execute();
        $aReturn = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        $stmt    = null;
        return $aReturn;
    }

    public function execute($sQuery, $bind = array())
    {
//        $stmt = self :: prepare($sQuery . ';');
        $stmt = self :: prepare($sQuery);
        foreach ($bind as $key => $value) {
            $b = $stmt->bindValue($key, $value);
        }
        $result = $stmt->execute();

        return $result;
    }

    public function insert($sQuery, $bind = array())
    {
        $result = $this->execute($sQuery, $bind);

        $link = self::$_link;
        $id = $link->lastInsertId();

        $er = $link->errorCode();
        return $id;
    }

}
