<?php
/**
 * Mapper class
 *
 * PHP version 5
 *
 * @package   ORM
 * @author    Gabriel Prieto <gab307@gmail.com>
 * @copyright 2012 LM
 * @link      LM
 */

namespace levitarmouse\kiss_orm;

use \Exception;

/**
 * Mapper class
 *
 * @package   ORM
 * @author    Gabriel Prieto <gab307@gmail.com>
 * @copyright 2012 LM
 * @link      LM
 */
class Mapper extends \levitarmouse\core\Object
{
    const DB_CONFIG_FILE_DOESNT_EXIST = 'DB_CONFIG_FILE_DOESNT_EXIST';
    const DB_CONNECTION_FAILED     = 'DB_CONNECTION_FAILED';
    const SYSDATE_STRING     = 'SYSDATE_STRING';
    const SQL_SYSDATE_STRING = 'SQL_SYSDATE_STRING';
    const EMPTY_STRING       = 'EMPTY_STRING';
    const SQL_EMPTY_STRING   = 'SQL_EMPTY_STRING';
    const NULL_STRING        = 'NULL_STRING';
    const SQL_NULL_STRING    = 'SQL_NULL_STRING';
    const DISABLED           = 'DISABLED';
    const ENABLED            = 'ENABLED';
    const ANY_STRING         = 'ANY_STRING';
    const ORDER_HAS_INVALID_FIELDS = 'ORDER_HAS_INVALID_FIELDS';

    protected static $instance = null;
    static $iCountSelect  = 0;
    static $iCountExecute = 0;
    static $iCountUpdate  = 0;
    static $iCountDelete  = 0;
    static $iCountInsert  = 0;
    static $fTime         = 0;
    static $iCountAction  = 0;

    /* @var $oDb \levitarmouse\core\database\Database */
//    public $oDb;
    public static $oDb;
    public $oLogger;
    
    public static $dbCfg;

    /**
     * __construct
     *
     * @param type $oDB DbConnection
     * @param type $oLogger Logger
     *
     * @return none
     */
    public function __construct($oDB = null, $oLogger = null)
    {
        if (self::$oDb) {
            return;
        }        
        
        if ($oDB) {
            self::$oDb = $oDB;
        } else {
            $this->connect();
        }
        if ($oLogger) {
            $this->oLogger = $oLogger;
        }
    }
    
    public function getDbConfig() {
        return self::$dbCfg;
    }
    
    public static function connect()
    {
        $dbConfigPath = DB_CONFIG;
//        $logsConfigPath 
        $dbCfg = new \levitarmouse\core\ConfigIni($dbConfigPath);
        self::$dbCfg = $dbCfg;
        
//        $oProxy    = PDOProxy::getInstance($dbCfg);
        $oProxy = \levitarmouse\kiss_orm\database\PDOProxy::getInstance($dbCfg);


        $database = new database\Database($oProxy);
        
        self::$oDb = $database;
    }

    public static function getInstance($db)
    {
        if (self::$instance === null)
        {
            return new \levitarmouse\kiss_orm\Mapper($db);
        }

        return self::$instance;
    }

    /**
     *
     * @return type
     */
    public function getNextId()
    {
        return $this->getSeqNextVal($this->sSequenceName);
    }

    /**
     * getSeqNextVal
     *
     * @param type $db       DbConnection
     * @param type $sSecName Sequence Name
     *
     * @return type
     */
    public function getSeqNextVal($sSecName)
    {
        $iResult = null;
        $sSql = <<<EOQ
            SELECT {$sSecName}.nextval AS nextval
              FROM dual
EOQ;
        $aBinding = array();

        $aResult = $this->select($sSql, $aBinding);

        if (count($aResult) == 1)
        {
            $iResult = $aResult[0]['NEXTVAL'];
        }
        return $iResult;
    }

    /**
     * getSysdate
     * Devuelve la fecha actual y la fecha luego de aplicar los modificadores pasados por parámetro.
     * Los modificadores son, el formato de las fechas a devolver y valores para agregar o quitar.
     *
     * @param type $sFormat          Formato a devolver
     * @param type $iYearsToAppend   Años para agregar
     * @param type $iMonthsToAppend  Meses para agregar
     * @param type $iWeeksToAppend   Semanas para agregar
     * @param type $iDaysToAppend    Días para agregar
     * @param type $iHoursToAppend   Horas para agregar
     * @param type $iMinutesToAppend Minutos para agregar
     * @param type $iSecondsToAppend Segundos para agregar
     *
     * @return type
     */
    public function getSysdate($sFormat = '', $bGetAsSeconds = false,
                               $iYearsToAppend = 0,  $iMonthsToAppend = 0, $iWeeksToAppend = 0, $iDaysToAppend = 0,
                               $iHoursToAppend = 0, $iMinutesToAppend = 0, $iSecondsToAppend = 0)
    {
        $iYearsToAppend   = ($iYearsToAppend !== null)   ? $iYearsToAppend : 0;
        $iMonthsToAppend  = ($iMonthsToAppend !== null)  ? $iMonthsToAppend : 0;
        $iWeeksToAppend   = ($iWeeksToAppend !== null)   ? $iWeeksToAppend : 0;
        $iDaysToAppend    = ($iDaysToAppend !== null)    ? $iDaysToAppend : 0;
        $iHoursToAppend   = ($iHoursToAppend !== null)   ? $iHoursToAppend : 0;
        $iMinutesToAppend = ($iMinutesToAppend !== null) ? $iMinutesToAppend : 0;
        $iSecondsToAppend = ($iSecondsToAppend !== null) ? $iSecondsToAppend : 0;

        $sFormat = ($sFormat == '') ? 'YYYYMMDDHH24MISS' : $sFormat;

        $sSql = <<<EOQ
            SELECT TO_CHAR(SYSDATE, '$sFormat') AS ACTUAL,
                   TO_CHAR(ADD_MONTHS(sysdate + :years*365 + :weeks*7 + :days + :hours/24 + :minutes/1440 + :seconds/86400, :months),
                           '$sFormat') AS FECHA,
                   TO_CHAR(ADD_MONTHS(sysdate + :years*365 + :weeks*7 + :days + :hours/24 + :minutes/1440 + :seconds/86400, :months),
                           'YYYY;MM;DD;HH24;MI;SS') AS TO_EXPLODE
              FROM DUAL
EOQ;
        $aBinding = array(
                          'years'   => $iYearsToAppend,
                          'months'  => $iMonthsToAppend,
                          'weeks'   => $iWeeksToAppend,
                          'days'    => $iDaysToAppend,
                          'hours'   => $iHoursToAppend,
                          'minutes' => $iMinutesToAppend,
                          'seconds' => $iSecondsToAppend,
                         );

        $aResult = $this->select($sSql, $aBinding);

        if (count(func_get_args()) == 0) {
            $sReturn = $aResult[0]['ACTUAL'];
        }
        else {
            if ($bGetAsSeconds) {
                list($yy, $mm, $dd, $hh, $mi, $ss) = explode(';', $aResult[0]['TO_EXPLODE']);
                $sReturn = mktime($hh, $mi, $ss, $mm, $dd, $yy);
            }
            else {
                $sReturn = $aResult[0]['FECHA'];
            }
        }

        return $sReturn;
    }

    public function getDateAsTimestamp($sDate = '', $sFormat = '')
    {
        if ($sDate == '') {
            $iReturn = $this->getSysdate('', true);
        }
        else {
            $sSql = <<<EOQ
                SELECT TO_CHAR(TO_DATE(:sDate, :sFormat), 'YYYY;MM;DD;HH24;MI;SS') AS TO_EXPLODE
                  FROM DUAL
EOQ;
            $aBinding = array('sDate' => $sDate, 'sFormat' => $sFormat);
            $aResult = $this->select($sSql, $aBinding);
            list($yy, $mm, $dd, $hh, $mi, $ss) = explode(';', $aResult[0]['TO_EXPLODE']);
            $iReturn = mktime($hh, $mi, $ss, $mm, $dd, $yy);
        }
        return $iReturn;
    }

    public function isDateInTheFuture($sDate, $sFormat = 'YYYYMMDDHH24MISS')
    {
        return ($this->getSysdateDiff($sDate, $sFormat) >= 0);
    }

    /**
     * getSysdate
     *
     * @param type $db DbConnection
     *
     * @return type
     */
    public function getSysdateDiff($sDate, $sFormat = '')
    {
        $sFormat = ($sFormat == '') ? 'DD/MM/YYYY HH24:MI:SS' : $sFormat;

        $sSql = <<<EOQ
               SELECT (TO_DATE(:datestring, :format) - SYSDATE ) AS TIMEDIFF
                 FROM DUAL
EOQ;
        $aBinding = array('datestring' => $sDate,
                          'format'     => $sFormat);
        $aResult = $this->select($sSql, $aBinding);

        $fDiff = $aResult[0]['TIMEDIFF'];
        return $fDiff;
    }

    /**
     *
     * @param type $sSql
     * @param type $aBnd
     *
     * @return type
     */
    public function execute($sSql, $aBnd)
    {
        $db = self::$oDb;
        
        $iResult = 0;
        try {
            if (!$db) {
                throw new Exception(__CLASS__.' DbConection not present');
            }
            else {
                $iTimeStart = (microtime(true));
                $iResult = $db->sqlExecForBinding($sSql, $aBnd);
                $iTimeEnd   = (microtime(true));
                $fTime = vsprintf('%.3f', $iTimeEnd - $iTimeStart);

                self::$iCountExecute ++;
                self::$iCountAction ++;
                self::$fTime += $fTime;

                if ($this->oLogger) {
                    $this->oLogger->logDbTimes("EXECUTE Time: ".$fTime." s | ".
                                               "Accum Ops: ".self::$iCountAction." | ".
                                               "Accum Time: ".self::$fTime);
                }
            }
        }
        catch (Exception $e) {
            if ($this->oLogger) {
                $this->oLogger->logDebug($e->getMessage());
            }
            $iResult = $e->getMessage();
            $this->logTrace();
        }
        return $iResult;
    }

    /**
     *
     * @param type $sSql
     * @param type $aBnd
     *
     * @return type
     */
    public function insert($sSql, $aBnd)
    {
        $db = self::$oDb;
        
        $iResult = 0;
        try {
            if (!$db) {
                throw new Exception(__CLASS__.' DbConection not present');
            }
            else {
                $iTimeStart = (microtime(true));
                $iResult = $db->insertWithBindings($sSql, $aBnd);
                $iTimeEnd   = (microtime(true));
                $fTime = vsprintf('%.3f', $iTimeEnd - $iTimeStart);

                self::$iCountInsert ++;
                self::$iCountAction ++;
                self::$fTime += $fTime;

                if ($this->oLogger) {
                    $this->oLogger->logDbTimes("INSERT Time: ".$fTime." s | ".
                                              "Accum Ops: ".self::$iCountAction." | ".
                                              "Accum Time: ".self::$fTime);
                }
            }
        }
        catch (Exception $e) {
            if ($this->oLogger) {
                $this->oLogger->logDebug($e->getMessage());
            }
            $iResult = $e->getMessage();
            $this->logTrace();
        }
        return $iResult;
    }

    /**
     *
     * @param type $sSql
     * @param type $aBnd
     *
     * @return type
     */
    public function update($sSql, $aBnd)
    {
        $db = self::$oDb;
        
        $iResult = 0;
        try {
            if (!$db) {
                throw new Exception(__CLASS__.' DbConection not present');
            }
            else {
                $iTimeStart = (microtime(true));
                $iResult = $db->executeWithBindings($sSql, $aBnd);
                $iTimeEnd   = (microtime(true));
                $fTime = vsprintf('%.3f', $iTimeEnd - $iTimeStart);

                self::$iCountUpdate ++;
                self::$iCountAction ++;
                self::$fTime += $fTime;

                if ($this->oLogger) {
                    $this->oLogger->logDbTimes("UPDATE Time: ".$fTime." s | ".
                                              "Accum Ops: ".self::$iCountAction." | ".
                                              "Accum Time: ".self::$fTime);
                }
            }
        }
        catch (Exception $e) {
            if ($this->oLogger) {
                $this->oLogger->logDebug($e->getMessage());
            }
            $iResult = $e->getMessage();
            $this->logTrace();
        }
        return $iResult;
    }

    /**
     *
     * @param type $sSql
     * @param type $aBnd
     *
     * @return type
     */
    public function delete($sSql, $aBnd)
    {
        $db = self::$oDb;
        
        $iResult = 0;
        try {
            if (!$db) {
                throw new Exception(__CLASS__.' DbConection not present');
            }
            else {
                $iTimeStart = (microtime(true));
                $iResult = $db->executeWithBindings($sSql, $aBnd);
                $iTimeEnd   = (microtime(true));
                $fTime = vsprintf('%.3f', $iTimeEnd - $iTimeStart);

                self::$iCountDelete ++;
                self::$iCountAction ++;
                self::$fTime += $fTime;

                if ($this->oLogger) {
                    $this->oLogger->logDbTimes("DELETE Time: ".$fTime." s | ".
                                              "Accum Ops: ".self::$iCountAction." | ".
                                              "Accum Time: ".self::$fTime);
                }
            }
        }
        catch (Exception $e) {
            if ($this->oLogger) {
                $this->oLogger->logDebug($e->getMessage());
            }
            $iResult = $e->getMessage();
            $this->logTrace();
        }
        return $iResult;
    }

    public function getFieldMapping()
    {
        return $this->aFieldMapping;
    }

    public function getFieldMappingSize()
    {
        return count($this->aFieldMapping);
    }

    /**
     *
     * @param type $sSql
     * @param type $aBnd
     *
     * @return type
     */
    public function select($sSql, $aBnd = array() )
    {
        $db = self::$oDb;
        
        $aResult = null;
        try {
            if (!$db) {
                throw new Exception(__CLASS__.' DbConection not present');
            }
//            else {
                $iTimeStart = (microtime(true));
                $aResult = $db->selectWithBindings($sSql, $aBnd);
                $iTimeEnd   = (microtime(true));
                $fTime = vsprintf('%.3f', $iTimeEnd - $iTimeStart);

//                $dbError = $db->getError();

                self::$iCountSelect ++;
                self::$iCountAction ++;
                self::$fTime += $fTime;

//                if ($this->oLogger) {
//                    $this->oLogger->logDbTimes("SELECT Time: ".$fTime." s | ".
//                                              "Accum Ops: ".self::$iCountAction." | ".
//                                              "Accum Time: ".self::$fTime);
//
//                    if (Config::LOG_DB_EXEC_PLAN) {
//                        $aBnd = array();
//                        $aExecPlan = $db->sqlOpenForBinding('SELECT * FROM TABLE(DBMS_XPLAN.DISPLAY_CURSOR)', $aBnd);
//                        foreach ($aExecPlan as $row) {
//                            $this->oLogger->logDebug($row['PLAN_TABLE_OUTPUT']);
//                        }
//                    }
//                }
//            }
        }
        catch (Exception $e) {
            if ($this->oLogger) {
                $this->oLogger->logDebug($e->getMessage());                
            }
            $aResult = $e->getMessage();
//            $this->logTrace();
        }
        return $aResult;
    }

    /**
     *
     * @param type $sSql
     * @param type $aBnd
     *
     * @return type
     */
    public function prepareAndSelect($query, $params)
    {
        $db = self::$oDb;
        
        try {
            if (!$db) {
                throw new Exception(__CLASS__.' DbConection not present');
            }
            $sSql = <<<QUERY
                SELECT t1.*
                  FROM ( {$query} ) t1
                 WHERE 1 = 1
QUERY;

            if (is_array($params) ||
                is_a($params, 'stdClass') ) {

                $sWhere = '';
                foreach ($params as $key => $value) {
                    if (strlen($value)) {

                        $bLike = strlen(strstr($value, '{{LIKE}}')) > 1;

                        if ($bLike) {
                            $sWhere    .= " AND {$key} LIKE :{$key} ";
                        } else {
                            $sWhere    .= " AND {$key} = :{$key} ";
                        }
                        $aBnd[$key] = $value;
                    }
                }
            }

            $sSql = $sSql . $sWhere;

            $aResult = null;
            $iTimeStart = (microtime(true));
            $aResult = $db->selectWithBindings($sSql, $aBnd);
            $iTimeEnd   = (microtime(true));
            $fTime = vsprintf('%.3f', $iTimeEnd - $iTimeStart);

            self::$iCountSelect ++;
            self::$iCountAction ++;
            self::$fTime += $fTime;

        }
        catch (Exception $e) {
            if ($this->oLogger) {
                $this->oLogger->logDebug($e->getMessage());                
            }
            $aResult = $e->getMessage();
        }
        return $aResult;
    }

    protected function logTrace()
    {
        if ($this->oLogger) {
//            $this->oLogger->logTrace();
        }
        return;
    }

    /**
     * _validateDateTime
     *
     * @param type $datetime
     * @param type $bDayFirst
     *
     * @return boolean
     */
    public function validateDateTime($datetime, $delimiter = '/', $bDayFirst=false)
    {
        $bValidDate = false;

        $aFullDate = explode(" ", $datetime);

        if (count($aFullDate) == 2) {

            $aDate = explode($delimiter, $aFullDate[0]);

            if (count($aDate) == 3 ) {

                if ($bDayFirst) {
                    list($day, $month, $year) = $aDate;
                } else {
                    list($month, $day, $year) = $aDate;
                }

                $aTime = explode(":", $aFullDate[1]);
                if (count($aTime) == 2) {
                    list($hour, $minute) = $aTime;

                    $bDate = @checkdate((int)$month, (int)$day, (int)$year);

                    if (is_numeric($hour) && is_numeric($minute) ) {
                        $bHour = ( ( ($hour >= 0 && $hour < 24) && ($minute >= 0 && $minute < 60) ) &&
                                   ( ((int)$hour == $hour ) && ((int)$minute) == $minute )           ) ? true : false;
                    } else {
                        $bHour = false;
                    }

                    if ($bDate == true && $bHour == true) {
                        $bValidDate = true;
                    }
                }
            }
        }

        return $bValidDate;
    }
}