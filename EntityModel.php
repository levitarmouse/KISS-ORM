<?php

/**
 * EntityModel class
 *
 * PHP version 5
 *
 * @package   ORM
 * @author    Gabriel Prieto <gab307@gmail.com>
 * @copyright 2012 LM
 * @link      LM
 */

namespace levitarmouse\kiss_orm;

use Exception;
use levitarmouse\kiss_orm\dto\EntityDTO;
use levitarmouse\kiss_orm\dto\GetByFilterDTO;
use levitarmouse\kiss_orm\dto\LimitDTO;
use levitarmouse\kiss_orm\dto\ModelDTO;
use levitarmouse\kiss_orm\dto\OrderByDTO;
use levitarmouse\kiss_orm\dto\PrimaryKeyDTO;
use levitarmouse\kiss_orm\dto\UniqueKeyDTO;
use levitarmouse\kiss_orm\interfaces\CollectionInterface;
use levitarmouse\kiss_orm\interfaces\EntityInterface;
use stdClass;

/**
 * EntityModel class
 *
 * @param MapperEntityModel $oMapper Mapper
 *
 * @package   ORM
 * @author    Gabriel Prieto <gab307@gmail.com>
 * @copyright 2012 LM
 * @link      LM
 */
abstract class EntityModel extends \levitarmouse\core\Object
implements EntityInterface, CollectionInterface
{

    const NO_CREATED       = 'NO_CREATED';     // No existe en la DB
    const FILLED_BY_OBJECT = 'FILLED_BY_OBJECT'; // Se populó con otro objeto
    const FILLED_BY_ARRAY  = 'FILLED_BY_ARRAY'; // Se populó con un array
    const ALREADY_EXISTS   = 'ALREADY_EXISTS'; // Ya existe en la DB
    const CREATE_OK        = 'CREATE_OK';      // Se creó en la DB
    const CREATE_FAILED    = 'CREATE_FAILED';  // Falló la creación en la DB
    const UPDATE_OK        = 'UPDATE_OK';      // Se modificó en la DB
    const UPDATE_FAILED    = 'UPDATE_FAILED';  // Falló la modificación en la DB
    const REMOVAL_OK       = 'REMOVAL_OK';     // Se eliminó en la DB
    const REMOVAL_FAILED   = 'REMOVAL_FAILED'; // Falló la eliminación en la DB

    protected $oMapper;

    protected $hasDescriptor;
    protected $exists;
    protected $aListChange;
    protected $hasChanges;
    protected $aData;
    private $_isLoading;
    public $objectStatus;
    //public $oTvTopology;
    public $oLogger;
    public $oDb;
    
    protected $descriptorLocation;

    protected $aCollection;
    protected $collectionIndex;

    protected $_dto;

//    function __construct(EntityDTO $dto)
    function __construct(EntityDTO $dto)
    {
        $this->_locateSource(get_class($this));

        $this->_dto = $dto;

        $this->aCollection = array();
        $this->collectionIndex = 0;

        if ($dto->oDB) {
            $this->oDb = $dto->oDB;
        }
        if ($dto->oLogger) {
            $this->oLogger = new DbLogger($dto->oLogger);
        }

        $sFileDescriptor = $this->getFileDescriptorByConvention();

        $oModelDto     = new ModelDTO($this->oDb, $this->oLogger, $sFileDescriptor);
        
        if ($this->descriptorLocation) {
            $oModelDto->sFileDescriptorModel = $this->descriptorLocation.'/'.$sFileDescriptor;
        }

        $modelName = get_class($this) . 'Model';
        if (class_exists($modelName)) {
            $this->oMapper = new $modelName($oModelDto);
        } else {
            $this->oMapper = new MapperEntityModel($oModelDto);
        }

        $this->hasDescriptor = $this->oMapper->hasDescriptor();

        $this->aData        = array();
        $this->aListChange  = array();
        $this->exists       = false;
        $this->hasChanges   = false;
        $this->_isLoading   = false;
        $this->objectStatus = self::NO_CREATED;

        if ($dto->pkDTO) {
            $this->loadByPK($dto->pkDTO);
        } else if ($dto->ukDTO) {
            $this->loadByUK($dto->ukDTO);
        }
    }
    
    private function _locateSource($className) {
        
        $locationByName = str_replace('\\', '/', $className);
        
        $aLocationByName = explode('/', $locationByName);
        $ClassName = array_pop($aLocationByName);
        $ClassPSR0Location = implode('/', $aLocationByName);
        
        $entityLocation = BUSSINES_LOGIC_PATH.$ClassPSR0Location;
        
        $this->descriptorLocation = $entityLocation;
        
    }

    protected function loadByPK(PrimaryKeyDTO $pkDTO)
    {
        $pk = $pkDTO->getPK();

        $aPrimaryKey = $this->oMapper->getFieldMappingPrimaryKey();

        foreach ($aPrimaryKey as $classAttrib => $dbField) {}

//        $classAttrib = $aPrimaryKey;

        $this->$classAttrib = $pk;

        $filterDTO = new GetByFilterDTO();

//        if (!$dtoAttribs) {
//            $dtoAttribs = new \stdClass();
        $dtoAttribs = array();
        $dtoAttribs[$classAttrib] = $pk;
//        }

        $filterDTO->setAttribs($dtoAttribs);

//        $filterDTO = new GetByFilterDTO();
//        $filterDTO->setAttribs($ukDTO->getAttribs());
//        $this->getByUK($filterDTO);
//
////        $return = $aRs[0];
//        return;

        $this->getByPK($filterDTO);

//        $return = $aRs[0];
        return;
    }


    protected function loadByUK(UniqueKeyDTO $ukDTO)
    {
        $filterDTO = new GetByFilterDTO();
        $filterDTO->setAttribs($ukDTO->getAttribs());
        $this->getByUK($filterDTO);

//        $return = $aRs[0];
        return;
    }

    /**
     * Returns a file descriptor
     *
     * @return string
     */
    protected function getFileDescriptorByConvention()
    {
        $className = get_class($this);

        $parts      = explode('\\', $className);
        $importPart = array_pop($parts);

        $descriptionFileName              = str_replace('Model', '', $importPart);
        $descriptionFileName              = $descriptionFileName . '.ini';
        return $descriptionFileName;
    }

    /**
     * Checks if this entity exists
     *
     * @return boolean
     */
    public function exists()
    {
        return $this->exists;
    }

    /**
     * @brief Inicializa los atributos de la clase desde el ResultSet
     * pasado como parametro.
     *
     * @param type $aRsValues
     *
     * @return type
     */
    private function _initClassAttribs($aRsValues, $aFieldMapping)
    {
        $this->exists = false;

        if (is_array($aRsValues) && count($aRsValues) > 0) {
            foreach ($aRsValues as $sField => $value) {
                if (in_array($sField, $aFieldMapping)) {
                    $this->aData[array_search($sField, $aFieldMapping)] = $value;
                }
                else {
                    $this->aData[$sField] = $value;
                }
            }

            $this->exists       = true;
            $this->objectStatus = self::ALREADY_EXISTS;
        }

        return;
    }

    /**
     * Returns the next sequence id
     *
     * @return Ambigous <\levitarmouse\kiss_orm\type, NULL>
     */
    public function getNextId()
    {
        return $this->oMapper->getNextId();
    }

    public function __get($sAttrib)
    {
        if (isset($this->aData[$sAttrib])) {
            return $this->aData[$sAttrib];
        }

        return null;
    }

    public function __set($sAttrib, $sValue)
    {
        $oldValue              = (isset($this->aData[$sAttrib])) ? $this->aData[$sAttrib] : null;
        $newValue              = $sValue;
        $this->detectChanges($sAttrib, $oldValue, $newValue);
        $this->aData[$sAttrib] = $sValue;
    }

    protected function init($aRsValues)
    {
        $aFieldMapping    = $this->oMapper->getFieldMapping();
        $this->_isLoading = true;
        $this->_initClassAttribs($aRsValues, $aFieldMapping);
        $this->_isLoading = false;

        $this->loadRelated();

        return;
    }

    public function initByResultSet($aRsValues)
    {
        $this->init($aRsValues);
        return;
    }

    public function fill($item)
    {
        if (is_array($item)) {
            return $this->fillByArray($item);
        }

        if (is_object($item)) {
            return $this->fillByObject($item);
        }
    }

    public function fillByObject($object)
    {
        if (is_object($object) && $object) {
            $array = array();
            foreach ($object as $attrib => $value) {
                $array[$attrib] = $value;
            }

            $this->init($array);
        }
        $this->objectStatus = self::FILLED_BY_OBJECT;
        return;
    }

    public function fillByArray($array)
    {
        if (is_array($array) && $array) {
            $this->init($array);
        }
        $this->objectStatus = self::FILLED_BY_ARRAY;
        return;
    }

    /* ************************************
     * interfaces\EntityInterface methods
     * ************************************ */

    public function getById($id)
    {
        /*$iId = $dto->id;

        $aRs = $this->oMapper->getById($iId);*/
        $aRs = $this->oMapper->getById($id);
        if (is_array($aRs)) {
            $this->fillByArray($aRs);
        } else {
            $this->fillByObject($aRs);
        }
        return true;
    }

    public function getByUK(GetByFilterDTO $filterDTO)
    {
        $resultSet = $this->oMapper->getByFilter($filterDTO);

        if (isset($resultSet[0])) {
            $this->fill($resultSet[0]);
        }
    }

    public function getByPK(GetByFilterDTO $filterDTO)
    {
        $resultSet = $this->oMapper->getByFilter($filterDTO);

        if (isset($resultSet[0])) {
            $this->fill($resultSet[0]);
        }
    }

    /* ********************************************
     * interfaces\CollectionInterface methods START
     * ******************************************** */

    public function getAll()
    {
        $resultSet = $this->oMapper->getAll();

        $className = get_class($this);

//        $return = array();
        $dto = new EntityDTO($this->oDb, $this->oLogger);
        foreach ($resultSet as $key => $row) {
            $obj = new $className($dto);
            $obj->fillByObject($row);

            $this->aCollection[] = $obj;
        }
        unset($resultSet);

        return $this->aCollection;
    }

    protected function fillCollection($resultSet)
    {
        $className = get_class($this);

        if (count($resultSet) >= 1) {
            $this->aCollection = array();
        }

        $dto = new EntityDTO($this->oDb, $this->oLogger);
        foreach ($resultSet as $key => $row) {
            $obj = new $className($dto);
            $obj->fill($row);

            $this->aCollection[] = $obj;
        }
        unset($resultSet);
    }

    /**
     * GetByFilter
     *
     * @param GetByFilterDTO $filterDTO
     * @param OrderByDTO $orderDto
     * @param LimitDTO $limitDto
     *
     * return array
     */
    public function getByFilter(GetByFilterDTO $filterDTO, OrderByDTO $orderDto = null, LimitDTO $limitDto = null)
    {
        $resultSet = $this->oMapper->getByFilter($filterDTO, $orderDto, $limitDto);

        $this->fillCollection($resultSet);

        if ($limitDto && $limitDto->justFirst()) {
            $theFirst = $this->getNext()->getAttribs();
            $this->fill($theFirst);
            return;
        }
//        $className = get_class($this);
//
////        $return = array();
//        $dto = new EntityDTO($this->oDb, $this->oLogger);
//        foreach ($resultSet as $key => $row) {
//            $obj = new $className($dto);
//            $obj->fill($row);
//
//            $this->aCollection[] = $obj;
//        }
//        unset($resultSet);

        return $this->aCollection;
    }

    public function getCollectionSize()
    {
        return count($this->aCollection);
    }

    public function getNext()
    {
        while ($this->collectionIndex < count($this->aCollection)) {
//            if ($this->collectionIndex == 0) {
//                $index = 0;
//                $this->collectionIndex ++;
//            } else {
                $index = $this->collectionIndex;
                $this->collectionIndex ++;
//            }
//            $index = ($this->collectionIndex == 0 ) ? 0 : $this->collectionIndex + 1;
            $return = $this->aCollection[$index];

            return $return;
        }
        return $this;
    }

    /* ********************************************
     * interfaces\CollectionInterface methods END
     * ******************************************** */

    /**
     * @return nothing
     */
    public function loadRelated()
    {
        return;
    }

    /**
     * create
     *
     * @return none
     */
    public function create()
    {
        $iResult = 0;
        $aValues = $this->getValues();

        if (is_array($aValues) && count($aValues > 0)) {
            $iResult = $this->oMapper->create($aValues);

            if (is_numeric($iResult)) {
                if ($this->oMapper->getDBEngineVendor() == 'MYSQL') {
                    $pk = $this->oMapper->getPrimaryKey();
                    if (count($pk) == 1) {
                        foreach ($pk as $key => $value) {
                            $this->$key = $iResult;
                        }
                    }
                }
                $this->objectStatus = self::CREATE_OK;
                $return = '';
            } else {
                $this->objectStatus = self::CREATE_FAILED;
                $return = "MAPPED_ENTITY_FAILED_TO_CREATE_[" . get_class($this) . "]_INSTANCE | Details: [{$iResult}]";
            }
        }

        return $return;
    }

    /**
     * modify
     *
     * @return none
     */
    public function modify()
    {
        $iResult = 0;
        $aWhere  = array();

        $aUniqueKey = $this->oMapper->getPrimaryKey();
        if (is_array($aUniqueKey) && count($aUniqueKey) > 0) {
            try {
                foreach ($aUniqueKey as $sField => $sAttrib) {
                    $attrib = $this->oMapper->getAttribByFieldName($sAttrib);
                    if ($this->{$attrib->attribName} === null) {
                        throw new Exception('MAPPED_ENTITY_ERROR_COULD_NOT_DETERMINE_CONDITION_FOR_MODIFICATION');
                    }
                    $aWhere[$sAttrib] = $this->{$attrib->attribName};
                }

                $aValues = $this->getValues(true);

                if (is_array($aValues) && count($aValues) > 0) {
                    $iResult = $this->oMapper->modify($aValues, $aWhere);

                    if ($iResult) {
                        $this->objectStatus = self::UPDATE_OK;
                    }
                    else {
                        $this->objectStatus = self::UPDATE_FAILED;
                    }
                }
            }
            catch (Exception $e) {
                $iResult = $e->getMessage();
            }
        }
        return ($iResult == 1) ? '' : "MAPPED_ENTITY_FAILED_TO_MODIFY_[" . get_class($this) . "]_INSTANCE_THROUGH_MAPPEDENTITY | Rows affected: [{$iResult}]";
    }

    public function remove()
    {
        $iResult = 0;

        $aUniqueKey = $this->oMapper->getFieldMappingUniqueKey();
        if (is_array($aUniqueKey) && count($aUniqueKey) > 0) {
            try {
                foreach ($aUniqueKey as $sField => $sAttrib) {
                    $condition = $this->$sField;
                    if ($condition === null) {
                        throw new Exception('MAPPED_ENTITY_ERROR_COULD_NOT_DETERMINE_CONDITION_FOR_REMOVAL');
                    }
                    $aWhere[$sAttrib] = $this->$sField;
                }

                $iResult = $this->oMapper->remove($aWhere);
            }
            catch (Exception $e) {
                $iResult = $e->getMessage();
            }
        }

        if ($iResult) {
            $this->objectStatus = self::REMOVAL_OK;
        }
        else {
            $this->objectStatus = self::REMOVAL_FAILED;
        }

        return ($iResult == 1) ? '' : "MAPPED_ENTITY_FAILED_TO_REMOVE_[" . get_class($this) . "]_INSTANCE_THROUGH_MAPPEDENTITY | Rows affected: [{$iResult}]";
    }

    public function getPrimaryKeyValue() {
        $aPKMapping = $this->oMapper->getFieldMappingPrimaryKey();

        foreach ($aPKMapping as $classAttrib => $dbField) { }

        $pkValue = $this->$classAttrib;

        return $pkValue;
    }

    protected function getValues($bOnlyChanges = false)
    {
        $aValues = array();
        // Devuelve los attribs de la clase en un array asociativo
        // donde la key es el nombre del campo en la DB y el valor es el attr
        if ($bOnlyChanges) {
            if (is_array($this->aListChange)) {
                // Solo devuelve los campos sobre los que hubo cambios
                foreach ($this->aListChange as $sAttrName => $aChanges) {
                    $aFieldMapping = $this->oMapper->getFieldMapping();
                    if (isset($aFieldMapping[$sAttrName])) {
                        // Devuelve el nuevo valor de los campos
                        $aValues[$aFieldMapping[$sAttrName]] = $aChanges['newValue'];
                    }
                }
            }
        }
        else {
            // Devuelve todos los campos, es para el caso de un insert
            $aFieldMapping = $this->oMapper->getFieldMapping();
            foreach ($aFieldMapping as $sAttrib => $sField) {
                if (isset($this->aData[$sAttrib]) &&  $this->aData[$sAttrib] !== null) {
                    $aValues[$sField] = $this->aData[$sAttrib];
                }
            }
        }
        return $aValues;
    }

    protected function detectChanges($sAttrib, $oldValue, $newValue)
    {
        $bWasChanged = false;
        if (!$this->_isLoading) {
            if (isset($this->oMapper)) {
                if (array_key_exists($sAttrib, $this->oMapper->getFieldMapping())) {
                    if (($newValue === 0 || $newValue === '0') &&
                        ($oldValue === '' || $oldValue === null)) {
                        $bWasChanged = true;
                    }
                    elseif (($newValue === '' || $newValue === null) &&
                        ($oldValue === 0 || $oldValue === '0')) {
                        $bWasChanged = true;
                    }
                    elseif ($oldValue != $newValue) {
                        $bWasChanged = true;
                    }
                    if ($bWasChanged) {
                        $this->hasChanges |= true;
                        $this->aListChange[$sAttrib] = array('oldValue' => $oldValue, 'newValue' => $newValue);
                        if ($this->oLogger) {
                            $this->oLogger->logDetectChanges(get_class($this).'.'.$sAttrib.
                                                             " | old value -> [{$oldValue}] | new value -> [{$newValue}]");                            
                        }
                    }
                }
            }
        }
        return;
    }

    public function hasChanges($sAttrName = '')
    {
        if ($sAttrName != '') {
            if ($this->hasChanges) {
                return array_key_exists($sAttrName, $this->getListChange());
            }
        }
        return $this->hasChanges;
    }

    public function getOldValueFor($sAttrib)
    {
        if (is_array($this->aListChange) && isset($this->aListChange[$sAttrib])) {
            return $this->aListChange[$sAttrib]['oldValue'];
        }
        return $this->aData[$sAttrib];
    }

    public function getListChange()
    {
        return $this->aListChange;
    }

    public function isBeingUsed($sField, $sValue, $bAutoExclude = true)
    {
        $id = $this->oMapper->getAttribAsUniqueKey();

        if ($bAutoExclude) {
            return $this->oMapper->isBeingUsed($sField, $sValue, $this->$id);
        }
        else {
            return $this->oMapper->isBeingUsed($sField, $sValue);
        }
    }

    public function getMapper()
    {
        return $this->oMapper;
    }

    public function getUniqueAttribsDictionary($bAsObject = false, $bAsXml = false) {

        $x = $this->oMapper->getFieldMappingUniqueKey();

        $attribs = $this->getAttribs($bAsObject, $bAsXml);

        $dictionary = array();
        foreach ($x as $key => $value) {
            $dictionary[$key] = $attribs[$key];
        }

        return $dictionary;
    }

    public function getAttribs($bAsObject = false, $bAsXml = false)
    {
        $mReturn = $this->aData;
        if ($bAsObject) {
            $mReturn = $this->_arrayToObject($mReturn);
        }
        else if ($bAsXml) {
            $mReturn = $this->_arrayToXML($mReturn);
        }
        return $mReturn;
    }

    private function _arrayToObject($aArray = null)
    {
        $obj = new stdClass();
        ksort($aArray, SORT_STRING);
        if (is_array($aArray) && count($aArray) > 0) {
            foreach ($aArray as $sAttrib => $sValue) {
                $obj->$sAttrib = $sValue;
            }
        }
        $obj->objectStatus = $this->objectStatus;
        return $obj;
    }

    private function _arrayToXML($aArray = null)
    {
        ksort($aArray, SORT_STRING);
        $xml = '';
        if (is_array($aArray)) {
            foreach ($aArray as $sAttrib => $sValue) {
                $xml .= "<{$sAttrib}>{$sValue}</{$sAttrib}>\n";
            }
        }
        return $xml;
    }

    public function getStatus()
    {
        return $this->objectStatus;
    }

}
