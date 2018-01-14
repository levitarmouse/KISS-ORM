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

$path = realpath(__DIR__);
include_once $path.'/config/Bootstrap.php';

use Exception;
use levitarmouse\kiss_orm\dto\EntityDTO;
use levitarmouse\kiss_orm\dto\GetByFilterDTO;
use levitarmouse\kiss_orm\dto\LimitDTO;
use levitarmouse\kiss_orm\dto\ModelDTO;
use levitarmouse\kiss_orm\dto\OrderByDTO;
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
 * @copyright 2017 Levitarmouse
 * @link      LM
 */
abstract class ViewModel
                extends \levitarmouse\core\Object
{
    const ALREADY_EXISTS   = 'ALREADY_EXISTS'; // Ya existe en la DB
    const CREATE_FAILED    = 'CREATE_FAILED';  // Falló la creación en la DB
    const CREATE_OK        = 'CREATE_OK';      // Se creó en la DB
    const DESCRIPTOR_NOT_FOUND = 'DESCRIPTOR_NOT_FOUND';     // El descriptor del modelo es requerido
    const FILLED_BY_ARRAY  = 'FILLED_BY_ARRAY'; // Se populó con un array
    const FILLED_BY_OBJECT = 'FILLED_BY_OBJECT'; // Se populó con otro objeto
    const INVALID_DESCRIPTOR = 'INVALID_DESCRIPTOR';     // El descriptor del modelo es requerido
    const NO_CREATED       = 'NO_CREATED';     // No existe en la DB
    const REMOVAL_FAILED   = 'REMOVAL_FAILED'; // Falló la eliminación en la DB
    const REMOVAL_OK       = 'REMOVAL_OK';     // Se eliminó en la DB
    const UPDATE_FAILED    = 'UPDATE_FAILED';  // Falló la modificación en la DB
    const UPDATE_OK        = 'UPDATE_OK';      // Se modificó en la DB
    const DB_DATE_TIME     = 'DB_DATE_TIME';

    protected $oMapper;

    protected $hasDescriptor;
    protected $exists;
    protected $aListChange;
    protected $hasChanges;
    protected $_isLoading;
    public $objectStatus;
    public $oLogger;
    public $oDb;

    protected $descriptorLocation;

    protected $aCollection;
    protected $collectionIndex;
    protected $collectionSize;

    protected $_dto;

    protected $path;

    private  $collectionEnd;

    function __construct($useDescriptor = true)
    {
        parent::__construct();

        $this->_locateSource();

        $this->clearCollection();

        $sFileDescriptor = $this->getFileDescriptorByConvention();

        $oModelDto = new ModelDTO($sFileDescriptor);

        if ($this->descriptorLocation) {
            $oModelDto->sFileDescriptorModel = $this->descriptorLocation.'/'.$sFileDescriptor;
        }

        $className = get_class($this);

        $validateDescriptor = $useDescriptor;

        $aClassName = explode('\\', $className);
        if ($aClassName) {
            $className = array_pop($aClassName);
            if ($className == 'GenericEntity') {
                $validateDescriptor = false;
            }
        }

        if ($validateDescriptor) {
            if (!file_exists($oModelDto->sFileDescriptorModel)) {
                throw new Exception(self::DESCRIPTOR_NOT_FOUND);
            }
        }

        $modelName = get_class($this) . 'Model';
        if (class_exists($modelName)) {
            $this->oMapper = new $modelName($oModelDto);
        } else {
            $this->oMapper = new MapperEntityModel($oModelDto);
        }

        $this->hasDescriptor = $this->oMapper->hasDescriptor();

        if ($this->hasDescriptor) {
            $schema = $this->oMapper->getSchema();
            $mappingSize = $this->oMapper->getFieldMappingSize();

            if (empty($schema) || $mappingSize == 0) {
                throw new \Exception(self::INVALID_DESCRIPTOR);
            }
        }

        $this->aListChange   = array();
        $this->exists        = false;
        $this->hasChanges    = false;
        $this->_isLoading    = false;
        $this->objectStatus  = self::NO_CREATED;

        $this->collectionEnd = false;
    }

    public function reConstructLite() {
        parent::__construct();

        $this->clearCollection();

        $this->aListChange   = array();
        $this->exists        = false;
        $this->hasChanges    = false;
        $this->_isLoading    = false;
        $this->objectStatus  = self::NO_CREATED;

        $this->collectionEnd = false;
    }

    protected function setUseDescriptor($bolean) {
        $this->useDescriptor = $bolean;
    }

    protected function useDescriptor() {
        return $this->useDescriptor;
    }

    protected function clearCollection() {
        $this->aCollection = array();
        $this->collectionIndex = 0;
    }

    protected function _locateSource() {

        $rc = new \ReflectionClass(get_class($this));
        $dirname = dirname($rc->getFileName());

        $this->descriptorLocation = $dirname;
    }

    public function setDbDateTime() {
        return self::DB_DATE_TIME;
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
    protected function _initClassAttribs($aRsValues, $aFieldMapping)
    {
        $this->exists = false;

        if (is_array($aRsValues) && count($aRsValues) > 0) {
            foreach ($aRsValues as $sField => $value) {

                $bMapping = in_array(strtoupper(trim($sField)), $aFieldMapping);

                if ($bMapping) {
//                    $this->aData[array_search($sField, $aFieldMapping)] = $value;
                    $this->aData[array_search(strtoupper($sField), $aFieldMapping)] = $value;
                }
            }

            $this->exists       = true;
            $this->objectStatus = self::ALREADY_EXISTS;
        }

        return;
    }

    public function __set($sAttrib, $sValue)
    {
        $oldValue              = (isset($this->aData[$sAttrib])) ? $this->aData[$sAttrib] : null;
        $newValue              = $sValue;
        $this->detectChanges($sAttrib, $oldValue, $newValue);
        parent::__set($sAttrib, $sValue);
    }

    public function setDetectChanges($value = true) {

        if ($value) {
            $this->_isLoading = false;
        } else {
            $this->_isLoading = true;
        }
    }

    protected function init($aRsValues)
    {
        $aFieldMapping    = $this->oMapper->getFieldMapping();
        $this->_isLoading = true;
        $this->_initClassAttribs($aRsValues, $aFieldMapping);
        $this->_isLoading = false;

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

    public function getAll(OrderByDTO $order = null)
    {
        $this->clearCollection();

        $resultSet = $this->oMapper->getAll($order);

        $className = get_class($this);

        foreach ($resultSet as $key => $row) {
            $obj = new $className();
            $obj->fill($row);

            $this->aCollection[] = $obj;
        }
        unset($resultSet);

        $this->collectionSize = count($this->aCollection);

        return $this->aCollection;
    }

    protected function fillCollection($resultSet)
    {
        $className = get_class($this);

        if (count($resultSet) >= 1) {
            $this->aCollection = array();
        }

        foreach ($resultSet as $key => $row) {

            if ($key === 'lastPage') {
                $this->lastPage = $row;
            } else if ($key === 'unlimitedSize') {
                $this->unlimitedSize = $row;
            } else {
                $obj = new $className();
                $obj->fill($row);

                $this->aCollection[] = $obj;
            }
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
    public function getByFilter(GetByFilterDTO $filterDTO, OrderByDTO $orderDto = null, LimitDTO $limitDto = null, $readyToSend = false)
    {
        $this->collectionEnd = false;

        $this->clearCollection();

        $resultSet = $this->oMapper->getByFilter($filterDTO, $orderDto, $limitDto);

        $this->fillCollection($resultSet);

        if ($limitDto && $limitDto->justFirst()) {
            $this->collectionSize = count($this->aCollection);

            $theFirst = $this->getNext()->getAttribs();
            $this->fill($theFirst);

            return;
        }

        $this->collectionSize = count($this->aCollection);

        if ($readyToSend) {
            return $this->collectionReadyToResponse();
        } else {
            return $this->aCollection;
        }
    }

    public function getCollection()
    {
        return $this->aCollection;
    }

    public function getCollectionAsArray($asModels = false)
    {
        $array = array();

        while ($obj = $this->getNext()) {
            if ($asModels) {
                $array[] = $obj;
            } else{
                $array[] = $obj->getAttribs();
            }
        }

        return $array;
    }

    public function getCollectionSize()
    {
        return count($this->aCollection);
    }

    public function collectionReadyToResponse() {
        $list = array();
        if ($this->aCollection) {
            while ($row = $this->getNext()) {
                $attribs = $row->getAttribs();
                array_push($list, $attribs);
            }
        }
        return $list;
    }

    public function getNext()
    {
        if ($this->collectionSize > 0) {

            if ($this->collectionIndex < $this->collectionSize) {

                    $index = $this->collectionIndex;
                    $this->collectionIndex ++;

                $return = $this->aCollection[$index];

                return $return;
            }
        } else {
            if (!$this->collectionEnd) {
                $this->collectionEnd = true;
                return $this;
            } else {
                return null;
            }
        }
    }

    /* ************************************
     * interfaces\EntityInterface methods
     * ************************************ */

    public function getById($id)
    {
        $aRs = $this->oMapper->getById($id);
        if (is_array($aRs)) {
            $this->fillByArray($aRs);
        } else {
            $this->fillByObject($aRs);
        }
        return true;
    }

    public function getUniqueKeyValues() {
        $ukFields = $this->oMapper->getUniqueKey();
        $values   = $this->getAttribs();
        $dictionary = array();
        foreach ($ukFields as $index => $attrib) {
            if (isset($values[$attrib])) {
                $dictionary[$attrib] = $values[$attrib];
            }
        }
        return array('ukValues' => $dictionary, 'ukSize' => count($ukFields));
    }

    public function getPrimaryKeyValues() {
        $pkFields = $this->oMapper->getPrimaryKey();
        $values   = $this->getAttribs();
        $dictionary = array();
        foreach ($pkFields as $index => $attrib) {
            if (isset($values[$index])) {
                $dictionary[$index] = $values[$index];
            }
        }
        return array('pkValues' => $dictionary, 'pkSize' => count($pkFields));
    }

    public function getByUK()
    {
        $uniqueKeyData = $this->getUniqueKeyValues();
        $currValues = $uniqueKeyData['ukValues'];
        $ukSize = $uniqueKeyData['ukSize'];

        $filter = new GetByFilterDTO();
        foreach ($currValues as $field => $dbField) {
            if (isset($currValues[$field])) {
                $filter->$field = $currValues[$field];
            }
        }

        $filterSize = count($filter->getAttribs());
        if ($filterSize == $ukSize) {
            $resultSet = $this->oMapper->getByFilter($filter);

            if (isset($resultSet[0])) {
                $this->fill($resultSet[0]);
                $this->objectStatus = self::ALREADY_EXISTS;
            }
        } else {
            $this->objectStatus = self::INVALID_DESCRIPTOR.'->UNIQUE_KEY';
        }
    }


    public function getByPK()
    {
        $primaryKeyData = $this->getPrimaryKeyValues();
        $currValues = $primaryKeyData['pkValues'];
        $pkSize = $primaryKeyData['pkSize'];

        $filter = new GetByFilterDTO();
        foreach ($currValues as $field => $dbField) {
            if (isset($currValues[$field])) {
                $filter->$field = $currValues[$field];
            }
        }

        $filterSize = count($filter->getAttribs());
        if ($filterSize == $pkSize) {
            $resultSet = $this->oMapper->getByFilter($filter);

            if (isset($resultSet[0])) {
                $this->fill($resultSet[0]);
            }
        } else {
            $this->objectStatus = self::INVALID_DESCRIPTOR.'->PRIMARY_KEY';
        }
    }

    /* ********************************************
     * interfaces\CollectionInterface methods END
     * ******************************************** */

    /**
     * getValues Devuelve los atributos definidos en el Descriptor
     */
    public function getValues($bOnlyChanges = false)
    {
        $aValues = array();
        // Devuelve los attribs de la clase en un array asociativo
        // donde la key es el nombre del campo en la DB y el valor es el attr
        if ($bOnlyChanges) {
            if (is_array($this->aListChange)) {
                // Solo devuelve los campos sobre los que hubo cambios
                foreach ($this->aListChange as $sAttrName => $aChanges) {
                    $aFieldMapping = $this->oMapper->getFieldMapping(); // TODO corregir esto!
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
                    $aValues[$sAttrib] = $this->aData[$sAttrib];
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

    public function getStatus()
    {
        return $this->objectStatus;
    }

    public function fieldExist($name = '') {

        $aFieldMapping = $this->oMapper->getFieldMapping();

        $exist = array_key_exists($name, $aFieldMapping);

        if (!$exist) {
            $exist = in_array($name, $aFieldMapping);
        }
        return $exist;
    }

    public function checkUnique($boolToCreate = null, $boolToUpdate = null)
    {
        $originalComponent = $this;
        $bCreate = (isset($boolToCreate)) ? $boolToCreate : null;
        $bUpdate = (isset($boolToUpdate)) ? $boolToUpdate : null;

        $originalUK = $originalComponent->getUniqueKeyValues();
        $originalUK_Size   = $originalUK['ukSize'];
        $originalUK_Values = $originalUK['ukValues'];

        $componentModel = clone $originalComponent;
        $filterDTO = new GetByFilterDTO();

        foreach ($originalUK_Values as $key => $value) {
            $filterDTO->$key = $value;
        }
        $componentModel->getByFilter($filterDTO);

        $searchResultSize = $componentModel->getCollectionSize();

        $result = true;
        if ($bCreate) {
            if ($searchResultSize != 0) {
                $result = false;
            }
        }

        if ($bUpdate) {
            if ($searchResultSize != 0) {
                $duplicatedComponent = $componentModel->getNext();

                $dupCompPK = $duplicatedComponent->getPrimaryKeyValues();
                $dupCompPKValues = $dupCompPK['pkValues'];

                $origCompPK = $originalComponent->getPrimaryKeyValues();
                $origCompPKValues = $origCompPK['pkValues'];

                foreach ($origCompPKValues as $key => $value) {
                    $bDuplicated = ($origCompPKValues[$key] != $dupCompPKValues[$key]);
                    if ($bDuplicated) {
                        $result = false;
                        break;
                    }
                }
            }
        }
        return $result;
    }


}