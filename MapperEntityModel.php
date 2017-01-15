<?php

/**
 * MapperEntityModel class
 *
 * PHP version 5
 *
 * @package   ORM
 * @author    Gabriel Prieto <gab307@gmail.com>
 * @copyright 2012 Levitarmouse
 * @link      Levitarmouse
 */

namespace levitarmouse\kiss_orm;

use levitarmouse\kiss_orm\dto\GetByFilterDTO;
use levitarmouse\kiss_orm\dto\GetByIdDTO;
use levitarmouse\kiss_orm\dto\LimitDTO;
use levitarmouse\kiss_orm\dto\ModelDTO;
use levitarmouse\kiss_orm\dto\OrderByDTO;
use levitarmouse\kiss_orm\interfaces\CollectionInterface;
use levitarmouse\kiss_orm\interfaces\EntityInterface;
use levitarmouse\kiss_orm\Mapper as Mapper;

/**
 * MapperEntityModel class
 *
 * @package   ORM
 * @author    Gabriel Prieto <gab307@gmail.com>
 * @copyright 2012 LM
 * @link      LM
 */
class MapperEntityModel extends Mapper
implements EntityInterface,
           CollectionInterface
{
    private $_descriptorPath;

    protected $schema                        = '';
    protected $table                         = '';
    protected $primary_key                   = '';
    protected $sequence                      = '';
    protected $aFieldMapping                 = array();
    protected $aFieldMappingRead             = array();
    protected $aFieldMappingWrite            = array();
    protected $aFieldMappingUniqueKeyAttribs = array();
    protected $aFieldMappingPrimaryKeyAttribs = array();
    //protected $sOrganizationIdFieldName      = '';
    protected $iCountFileds;
    protected $sEntityDescriptionFileName;
    protected $hasDescriptor = false;

    protected $dbEngineVendor = '';
    /**
     * __construct
     *
     * @param type $db DbConnection
     *
     * @return none
     */
    public function __construct(ModelDTO $dto, $dbEngineVendor = '')
    {
        if ($dbEngineVendor && !empty($dbEngineVendor)) {
            $this->dbEngineVendor = $dbEngineVendor;
        } else {
            if (defined('ORM_DB_ENGINE')
                && !empty(ORM_DB_ENGINE)
                && in_array(strtoupper(ORM_DB_ENGINE), array('MYSQL', 'ORACLE')) ) {
                $this->dbEngineVendor = strtoupper(ORM_DB_ENGINE);
            } else {
                $this->dbEngineVendor = 'MYSQL';
            }
        }

        $bPathConf = false;
        if (defined('ORM_ENTITY_DESCRIPTOR_PATH')) {
            if (!empty(ORM_ENTITY_DESCRIPTOR_PATH)) {
                $bPathConf = true;
            }
        }

        if ($bPathConf) {
            $this->_descriptorPath = ORM_ENTITY_DESCRIPTOR_PATH;
            $x = $this->_descriptorPath;
        } else {
            $this->_descriptorPath = __DIR__.'/'.'entities_descriptors/';
            $x = $this->_descriptorPath;
        }

        $oDB         = ($dto->oDB) ? $dto->oDB : null;
        $oLogger     = ($dto->oLogger) ? $dto->oLogger : null;
        $sConfigFile = ($dto->sFileDescriptorModel) ? $dto->sFileDescriptorModel : '';

        parent::__construct($oDB, $oLogger);

        if (!empty($sConfigFile)) {
            $sEntityDescriptor = $sConfigFile;
        } else {
            if (!empty($this->sEntityDescriptionFileName)) {
                $sEntityDescriptor = $this->sEntityDescriptionFileName;
            } else {
                $sEntityDescriptor = '';
            }
        }

        if ($sEntityDescriptor) {
            $finalDescriptorLocation = $this->_descriptorPath.$sEntityDescriptor;
            if (file_exists($finalDescriptorLocation)) {
                $this->_loadConfig($finalDescriptorLocation);
            }
        }
    }

    public function getDBEngineVendor()
    {
        return $this->dbEngineVendor;
    }

    private function _loadConfig($sDescriptor)
    {
        if (!($aConfig  = parse_ini_file($sDescriptor, true))) {
            $aConfig = array();
        }

        foreach ($aConfig as $section => $aParameters) {
            if ($section == 'table' || $section == 'details') {
                foreach ($aParameters as $attrib => $value) {
                    $this->$attrib = $value;
                }
            }
            if ($section == 'fields') {
                foreach ($aParameters as $index => $value) {
                    $this->aFieldMapping[$index] = $value;
                }
            }
            if ($section == 'primary_key') {
                foreach ($aParameters as $index => $value) {
                    $this->aFieldMappingPrimaryKeyAttribs[$index] = $value;
                    $this->primary_key = $value;
                }
            }
            if ($section == 'unique_key') {
                foreach ($aParameters as $index => $value) {
                    $this->aFieldMappingUniqueKeyAttribs[$index] = $value;
                }
            }
            if ($section == 'fields_read') {
                foreach ($aParameters as $index => $value) {
                    $this->aFieldMappingRead[$index] = $value;
                }
            }
            if ($section == 'fields_write') {
                foreach ($aParameters as $index => $value) {
                    $this->aFieldMappingWrite[$index] = $value;
                }
            }
        }
        $this->hasDescriptor = true;
    }
    /**
     * Checks if the object has a descriptor associated.
     *
     * @return boolean
     */
    public function hasDescriptor()
    {
        return $this->hasDescriptor;
    }

    protected function setFileDescriptorByConvention($className)
    {
        $parts      = explode('\\', $className);
        $importPart = array_pop($parts);

        $descriptionFileName              = str_replace('Model', '', $importPart);
        $descriptionFileName              = $descriptionFileName . '.ini';
        $this->sEntityDescriptionFileName = $descriptionFileName;
    }

    public function getPrimaryKey()
    {
//        return (isset($this->primary_key)) ? $this->primary_key : '';
        return (isset($this->aFieldMappingPrimaryKeyAttribs)) ? $this->aFieldMappingPrimaryKeyAttribs : array();
    }

    public function getSchema()
    {
        return (isset($this->schema)) ? $this->schema : '';
    }

    public function getTableName()
    {
        return (isset($this->table)) ? $this->table : '';
    }

    public function getSequenceName()
    {
        return (isset($this->sequence)) ? $this->sequence : '';
    }

    public function getFieldMappingPrimaryKey()
    {
        return (isset($this->aFieldMappingPrimaryKeyAttribs)) ? $this->aFieldMappingPrimaryKeyAttribs : array();
    }

    public function getFieldMappingUniqueKey()
    {
        return (isset($this->aFieldMappingUniqueKeyAttribs)) ? $this->aFieldMappingUniqueKeyAttribs : array();
    }
    /*
    public function getDbFieldControllerType()
    {
        return (isset($this->sControllerTypeFieldName)) ? $this->sControllerTypeFieldName : '';
    }
    */
    public function getAttribAsUniqueKey()
    {
        $sAttrib = '';
        if (is_array($this->aFieldMappingUniqueKeyAttribs)) {
            $aCopy = $this->aFieldMappingUniqueKeyAttribs;
            array_flip($aCopy);

            $sAttrib = $aCopy[$this->primary_key];
        }
        return $sAttrib;
    }

    public function getAttribByFieldName($fieldName)
    {
        $aFieldMapping = array_flip($this->aFieldMapping);
        $return = new EntityAttribDTO();
        $return->attribName = $aFieldMapping[$fieldName];

        return $return;
    }

    public function getNextId()
    {
        return $this->getSeqNextVal($this->sequence);
    }

    /* ***************************
     * EntityInterface methods START
     * *************************** */

    /**
     * getById
     *
     * @param type $id           Identificador
     *
     * @return type
     */
    public function getById($id)
    {
//        $id = $dto->id;

        $sSchema      = $this->schema;
        $sMainTable   = $this->table;
        $sIdFieldName = $this->primary_key;

        if ($sMainTable != '' && $sIdFieldName != '') {

            switch ($this->dbEngineVendor) {
                case 'MYSQL':
                    $sSql = "SELECT @rownum:=@rownum+1 AS ROWNUM";
                    break;
                case 'ORACLE':
                    $sSql = "SELECT ROWNUM";
                    break;
            }

            foreach ($this->aFieldMapping as $classAttrib => $dbField) {

                $sTemp = " {$dbField} ";

                if (isset($this->aFieldMappingRead)) {
                    if (array_key_exists($dbField, $this->aFieldMappingRead)) {
                        $sTemp = ' ' . $this->aFieldMappingRead[$dbField] . ' ';
                    }
                }
                $sSql .= ", {$sTemp} ";
            }
            $tableName = ($sSchema) ? $sSchema . '.' . $sMainTable : $sMainTable;

            switch ($this->dbEngineVendor) {
                case 'MYSQL':
                    $sFrom  = " FROM (SELECT @rownum:=0) r, {$tableName} ";
                    break;
                case 'ORACLE':
                    $sFrom  = " FROM {$tableName} ";
                    break;
            }

            $sWhere = " WHERE {$sIdFieldName} = :ID ";

            $aBnd = array('ID' => $id);

            $sSql .= $sFrom . $sWhere;

            // Logging
            foreach ($aBnd as $field => $value) {
                //$sLogValues .= @$field.'->['.$value.'] ';
            }
            //$this->oLogger->logDbChanges("select from {$tableName} where {$sLogValues}", 'SELECT');

            $aResult = $this->select($sSql, $aBnd);

            //$this->oLogger->logDbChanges("result: ".serialize($aResult));

            if (is_array($aResult) && isset($aResult[0])) {
                return $aResult[0];
            }
        }
        return array();
    }
    /* ***************************
     * EntityInterface methods END
     * *************************** */

    /* ***************************
     * CollectionInterface methods START
     * *************************** */

    /**
     *
     * @return type
     */
//    public function getAll(dto\GetAllDTO $dto)
    public function getAll()
    {
        $sSql = "select * from ".$this->getSchema().".".$this->getTableName();

        $aBnd = array();

        $result = $this->select($sSql, $aBnd);

        return $result;
    }

    public function getByFilter(GetByFilterDTO $filterDTO, OrderByDTO $orderDto = null, LimitDTO $limitDto = null)
    {
        $sSchema      = $this->schema;
        $sMainTable   = $this->table;

        $filter = null;
        if ($filterDTO) {
            $filter = $filterDTO->getFilter();
        }

        $order = null;
        if ($orderDto) {

            $order = $orderDto;
            $orderFields = $orderDto->getAttribs();
        }

        $page = null;
        if ($limitDto) {
            $page = $limitDto;
        }

        if ($sMainTable) {

            $aaFieldCompares = array();
            $aBnd = array();

            if ($page) {
                switch ($this->dbEngineVendor) {
                    case 'MYSQL':
                        $sSql = "SELECT @rownum:=@rownum+1 ";
                        break;
                    case 'ORACLE':
                        $sSql = "SELECT ROWNUM";
                        break;
                }
            } else {
                $sSql = "SELECT ";
            }


            if ($page) {
                $sSql .= ', page.* from ( SELECT (SELECT @rownum:=0) r, ';
            }

            $first = true;
            foreach ($this->aFieldMapping as $classAttrib => $dbField) {

                $sTemp = " {$dbField} ";

                if (isset($this->aFieldMappingRead)) {
                    if (array_key_exists($dbField, $this->aFieldMappingRead)) {
                        $sTemp = ' ' . $this->aFieldMappingRead[$dbField] . ' ';
                    }
                }

                $comma = ($first) ? ' ' : ', ';

                $sSql .= $comma. $sTemp;

                if (isset($filterDTO->$classAttrib)) {
                    $aaFieldCompares[$dbField] = $filterDTO->$classAttrib;
                }
                $first = false;
            }

            $tableName = ($sSchema) ? $sSchema . '.' . $sMainTable : $sMainTable;

//            switch ($this->dbEngineVendor) {
//                case 'MYSQL':
//                    $sFrom  = " FROM (SELECT @rownum:=0) r, {$tableName} ";
//                    break;
//                case 'ORACLE':
            $sSql  .= " FROM $tableName ";
//                    break;
//            }

            $sWhere = 'WHERE 1 = 1';

            foreach($aaFieldCompares as $dbField => $value) {
                if (is_array($value)) {

                    $size = count($value);

                    $bindNames = ''; $i = 1;
                    foreach ($value as $bindKey => $bindValue) {
                        $name = $dbField."_".$i;
                        $comma = ($i < $size) ? ', ' : ' ';

                        $bindNames .= ":".$name.$comma;

                        $aBnd[$name] = $bindValue;

                        $i++;
                    }

                    $sWhere .= " AND $dbField IN ($bindNames)";
                } else {
                    $bLike = strlen(strstr($value, '{{LIKE}}')) > 1;
                    if ($bLike) {
                        $sWhere .= " AND $dbField like :$dbField";
                    } else {
                        $sWhere .= " AND $dbField = :$dbField";
                    }
                    $aBnd[$dbField] = $value;
                }
            }

//            $sSql .= $sFrom;

            $sSql .= $sWhere;

//            $sSql .= $sFrom.') page';

            if ($order) {

                $orderStr = " ORDER BY ";

                $bFirst = true;
                foreach ($orderFields as $field => $direction) {

                    $comma = ($bFirst) ? ' ' : ', ';

                    $orderStr .= $field." ".$direction.$comma;

//                    if ($order->direction) {
//                        $direction = $order->direction;
//                        $orderStr .= " ".$direction;
//                    }

                    $bFirst = false;
                }

                $sSql .= $orderStr;
            }

            if ($page) {
                $sSql .= ") page";

                switch ($this->dbEngineVendor) {
                    case 'MYSQL':
                        $pageSql = " WHERE @rownum >= :pageStart AND @rownum <= :pageEnd";
                        break;
                    case 'ORACLE':
                        break;
                }

                $aBnd['pageStart'] = $page->firstRow;
                $aBnd['pageEnd'] = $page->lastRow;

                $sSql .= $pageSql;
            }

            // Logging
            foreach ($aBnd as $field => $value) {
                //                $sLogValues .= @$field.'->['.$value.'] ';
            }
            //            $this->oLogger->logDbChanges("select from {$tableName} where {$sLogValues}", 'SELECT');

            $aResult = $this->select($sSql, $aBnd);

            //            $this->oLogger->logDbChanges("result: ".serialize($aResult));

            if (is_array($aResult)) {
                return $aResult;
            }
        }
        return array();
    }


    /* ***************************
     * CollectionInterface methods END
     * *************************** */

    public function create($aValues)
    {
        $sLogValues = '';
        $sMainTable = $this->getTableName();
        $sSchema    = $this->getSchema();

        $iResult    = false;
        if ($sMainTable != '') {
            $aBnd    = array();
            $iValues = count($aValues);
            $bFirst  = true;

            $sFields = $sValues = '';

            foreach ($aValues as $field => $value) {
                $bValueIsAConstant = $this->_isAConstant($value);
                $valueExpresion    = null;
                if (is_array($this->aFieldMappingWrite)) {
                    if (isset($this->aFieldMappingWrite[$field]) && !$bValueIsAConstant) {
                        $valueExpresion = $this->aFieldMappingWrite[$field];
                    }
                }

                $value = $this->_replaceConstant($value);

                $sFields .= (!$bFirst) ? ', ' . $field : $field . '';

                if ($valueExpresion !== null) {
                    $sValues .= (!$bFirst) ? ', ' . $valueExpresion : $valueExpresion;
                    $aBnd[$field] = $value;
                }
                else {
                    if ($bValueIsAConstant) {
                        $sValues .= (!$bFirst) ? ', ' . $value : $value;
                    }
                    else {
                        $sValues .= (!$bFirst) ? ', :' . $field : ':' . $field;
                        $aBnd[$field] = $value;
                    }
                }

                // Logging
                $sLogValues .= $field . '->[' . $value . '] ';

                $bFirst = false;
            }

            $sSchemaTable = ($sSchema) ? $sSchema . '.' . $sMainTable : $sMainTable;

            // Logging
//            $this->oLogger->logDbChanges("insert {$sSchemaTable} values {$sLogValues}", 'INSERT');

            $sSql = "
           INSERT INTO {$sSchemaTable}
                      ({$sFields})
               VALUES ({$sValues})";

            $iResult = $this->insert($sSql, $aBnd, $sSchemaTable);
//            $this->oLogger->logDebug("insert ending with: ({$iResult})");

        }
        return $iResult;
    }

    private function _replaceConstant($value)
    {
        if ($value === Mapper::SYSDATE_STRING || $value === Mapper::SQL_SYSDATE_STRING) {
            switch ($this->dbEngineVendor) {
                case 'MYSQL':
                    $value = "NOW()";
                    break;
                case 'ORACLE':
                    $value = "SYSDATE";
                    break;
            }
        } elseif ($value === Mapper::ENABLED) {
            $value = "0";
        } elseif ($value === Mapper::DISABLED) {
            $value = "1";
        } elseif ($value === Mapper::EMPTY_STRING || $value === Mapper::SQL_EMPTY_STRING) {
            $value = "''";
        } elseif ($value === Mapper::NULL_STRING || $value === Mapper::SQL_NULL_STRING) {
            $value = "null";
        } elseif ($value === '') {
            $value = "''";
        } elseif ($value === null) {
            $value = '';
        }
        return $value;
    }

    private function _isAConstant($value)
    {
        if (in_array($value, array(
            Mapper::SYSDATE_STRING,
            Mapper::SQL_SYSDATE_STRING,
            Mapper::EMPTY_STRING,
            Mapper::SQL_EMPTY_STRING,
            Mapper::NULL_STRING,
            Mapper::ANY_STRING,
            Mapper::DISABLED,
            Mapper::ENABLED) ) ) {
            return true;
        }
        return false;
    }

    public function modify($aValues, $aWhere)
    {
        $sLogValues = $sLogWhere  = $sSetters = '';
        $sMainTable = $this->getTableName();
        if (count($aWhere) > 0 && count($aValues) > 0 && $sMainTable != '') {

            $bFirst = true;
            foreach ($aValues as $field => $value) {

                $aBnd[$field] = $value;

                $setExpresion  = $originalValue = null;
                if ($value !== null) {
                    if (is_array($this->aFieldMappingWrite)) {
                        if (isset($this->aFieldMappingWrite[$field])) {
                            $setExpresion = $this->aFieldMappingWrite[$field];
                        }
                    }
                }

                if ($value === Mapper::SYSDATE_STRING || $value === Mapper::SQL_SYSDATE_STRING) {
                    switch ($this->dbEngineVendor) {
                        case 'MYSQL':
                            $value = "NOW()";
                            break;
                        case 'ORACLE':
                            $value = "SYSDATE";
                            break;
                    }
                    unset($aBnd[$field]);
                }
                elseif ($value === Mapper::ENABLED) {
                    $value = "0";
                    unset($aBnd[$field]);
                }
                elseif ($value === Mapper::DISABLED) {
                    $value = "1";
                    unset($aBnd[$field]);
                }
                elseif ($value === Mapper::EMPTY_STRING || $value === Mapper::SQL_EMPTY_STRING) {
                    $value = "''";
                    unset($aBnd[$field]);
                }
                elseif ($value === Mapper::NULL_STRING || $value === Mapper::SQL_NULL_STRING || $value === null) {
                    $value = 'null';
                    unset($aBnd[$field]);
                }
                elseif ($value === '') {
                    $value = "''";
                    unset($aBnd[$field]);
                }
                else {
                    $originalValue = $value;
                    $value         = ":{$field}";
                }

                if ($setExpresion === null) {
                    $setExpresion = $value;
                }

                $sSetters .= ($bFirst) ? "{$field} = {$setExpresion}" : ", {$field} = {$setExpresion}";

                $bFirst = false;

                // Logging
                $sLogValues .= $field . '->[' . (($originalValue) ? $originalValue : $value) . '] ';
            }

            $sWhere = ' 1 = 1 ';
            if (is_array($aWhere) && count($aWhere) > 0) {
                foreach ($aWhere as $field => $value) {
                    $aBnd[$field] = $value;
                    $sWhere .= " AND {$field} = :{$field}";

                    // Logging
                    $sLogWhere .= $field . '->[' . $value . '] ';
                }
            }
            else {
                return false;
            }

            // Logging
//            $this->oLogger->logDbChanges("update {$sMainTable} set {$sLogValues} where {$sLogWhere}", 'UPDATE');

            $sSql = "
                UPDATE {$sMainTable}
                   SET {$sSetters}
                 WHERE {$sWhere}";

            $iResult = $this->update($sSql, $aBnd, $sMainTable);
            $this->oLogger->logDebug("update ending with: ({$iResult})");
            return $iResult;
        }

        return false;
    }

    public function remove($aWhere)
    {
        $sLogWhere  = '';
        $sMainTable = $this->getTableName();
        if (is_array($aWhere) && count($aWhere) > 0 && $sMainTable != '') {

            $sSql = "
                DELETE
                  FROM {$sMainTable} ";

            $sWhere = ' WHERE 1 = 1 ';

            foreach ($aWhere as $field => $value) {
                $aBnd[$field] = $value;
                $sWhere .= " AND {$field} = :{$field}";
                // Logging
                $sLogWhere .= $field . '->[' . $value . '] ';
            }

            // Logging
//            $this->oLogger->logDbChanges("delete from {$sMainTable} where {$sLogWhere}", 'DELETE');

            $sSql .= ' ' . $sWhere;

            $iResult = $this->delete($sSql, $aBnd, $sMainTable);
            $this->oLogger->logDebug("delete ending with: ({$iResult})");
            return $iResult;
        }

        return false;
    }

    public function isBeingUsed($sField, $sValue, $iExcludeId = '')
    {
        if ($iExcludeId) {
            return $this->valueAlreadyExists($this->sMainTable, $sField, $sValue, $this->sInternalIdFieldName, $iExcludeId);
        }
        else {
            return $this->valueAlreadyExists($this->sMainTable, $sField, $sValue, $this->sInternalIdFieldName);
        }
    }

    public function valueAlreadyExists($sTableName, $sFieldName, $sValue, $sExcludeField = '', $iExcludeId = '')
    {
        // TODO
        // escapar parametros
        $bResult = false;

        $sSql     = <<<EOQ
            SELECT count(*) as Q
              FROM {$sTableName}
             WHERE {$sFieldName} = :value
EOQ;
        $aBinding = array('value' => $sValue);

        if ($iExcludeId) {
            $sSql .= " AND {$sExcludeField} <> :excludeid ";
            $aBinding['excludeid'] = $iExcludeId;
        }

        $aRecords = $this->select($sSql, $aBinding);
        if ($aRecords[0]['Q'] > 0) {
            $bResult = true;
        }

        return $bResult;
    }

    /**
     * Función que permite blockear un registro de una tabla de la base de datos, sin necesidad de desbloquear, ya que el desbloqueo
     * se realiza cuando se commitea o se hace rollback
     *
     * @param type $sFieldBlock campo de la tabla que se quiere bloquear
     * @param type $iId id por el que se quiere bloquear.
     *
     * @return type
     */
    public function blockById($sFieldBlock, $iId)
    {
        $bBlockedField = false;
        $sMainTable    = $this->getDbTableName();
        $sIdFieldName  = $this->getDbFieldInternalId();

        if ($sMainTable != '' && $sIdFieldName != '') {

            $sSql       = "SELECT " . $sFieldBlock . " ";
            $sFrom      = " FROM {$sMainTable} ";
            $sWhere     = " WHERE {$sIdFieldName} = :ID ";
            $sForUpdate = " FOR UPDATE WAIT 30 ";

            $aBnd = array('ID' => $iId);
            $sSql .= $sFrom . $sWhere . $sForUpdate;

            // Logging
            foreach ($aBnd as $field => $value) {
                $sLogValues .= @$field . '->[' . $value . '] ';
            }
            $this->oLogger->logDbChanges("select from {$sMainTable} where {$sLogValues}", 'SELECT');

            $aResult = $this->select($sSql, $aBnd);
            $this->oLogger->logDbChanges("result: " . serialize($aResult));

            if (is_array($aResult) && isset($aResult[0])) {

                $bBlockedField = $aResult[0]['IDCLIENTE'];
            }
        }
        return $bBlockedField;
    }

}
