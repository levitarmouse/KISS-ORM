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
use levitarmouse\kiss_orm\interfaces\CollectionInterface;
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
abstract class ViewModel extends \levitarmouse\core\Object implements CollectionInterface
{

//    const NO_CREATED       = 'NO_CREATED';     // No existe en la DB
//    const FILLED_BY_OBJECT = 'FILLED_BY_OBJECT'; // Se populó con otro objeto
//    const FILLED_BY_ARRAY  = 'FILLED_BY_ARRAY'; // Se populó con un array
//    const ALREADY_EXISTS   = 'ALREADY_EXISTS'; // Ya existe en la DB
//    const CREATE_OK        = 'CREATE_OK';      // Se creó en la DB
//    const CREATE_FAILED    = 'CREATE_FAILED';  // Falló la creación en la DB
//    const UPDATE_OK        = 'UPDATE_OK';      // Se modificó en la DB
//    const UPDATE_FAILED    = 'UPDATE_FAILED';  // Falló la modificación en la DB
//    const REMOVAL_OK       = 'REMOVAL_OK';     // Se eliminó en la DB
//    const REMOVAL_FAILED   = 'REMOVAL_FAILED'; // Falló la eliminación en la DB

    protected $oMapper;

//    protected $hasDescriptor;
//    protected $exists;
//    protected $aListChange;
//    protected $hasChanges;
    protected $aData;
    private $_isLoading;
//    public $objectStatus;
    //public $oTvTopology;
    public $oLogger;
    public $oDb;

    protected $descriptorLocation;

    protected $aCollection;
    protected $collectionIndex;
    protected $collectionSize;

    protected $_dto;

    function __construct(dto\ViewDTO $dto = null)
    {
        $this->_locateSource(get_class($this));

        $this->_dto = $dto;

        $this->aCollection = array();
        $this->collectionIndex = 0;

        if ($dto) {
            if ($dto->oDB) {
                $this->oDb = $dto->oDB;
            }
            if ($dto->oLogger) {
                $this->oLogger = new DbLogger($dto->oLogger);
            }            
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
//        $this->objectStatus = self::NO_CREATED;

//        if ($dto->pkDTO) {
//            $this->loadByPK($dto->pkDTO);
//        } else if ($dto->ukDTO) {
//            $this->loadByUK($dto->ukDTO);
//        }
    }

    private function _locateSource($className) {
        
        $locationByName = str_replace('\\', '/', $className);
        
        $aLocationByName = explode('/', $locationByName);
        $ClassName = array_pop($aLocationByName);
        $ClassPSR0Location = implode('/', $aLocationByName);
        
        $entityLocation = BUSSINES_LOGIC_PATH.$ClassPSR0Location;
        
        $this->descriptorLocation = $entityLocation;
        
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
//                $sField = strtoupper($sField);
                if (in_array(strtoupper($sField), $aFieldMapping)) {
                    $classAttrib = array_search(strtoupper($sField), $aFieldMapping);
                    $this->aData[$classAttrib] = $value;
                }
                else {
                    $this->aData[$sField] = $value;
                }
            }

//            $this->exists       = true;
//            $this->objectStatus = self::ALREADY_EXISTS;
        }

        return;
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

//        $this->loadRelated();

        return;
    }

//    public function initByResultSet($aRsValues)
//    {
//        $this->init($aRsValues);
//        return;
//    }

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
//        $this->objectStatus = self::FILLED_BY_OBJECT;
        return;
    }

    public function fillByArray($array)
    {
        if (is_array($array) && $array) {
            $this->init($array);
        }
//        $this->objectStatus = self::FILLED_BY_ARRAY;
        return;
    }

    /* ********************************************
     * interfaces\CollectionInterface methods START
     * ******************************************** */

    public function getAll()
    {
        $resultSet = $this->oMapper->getAll();

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

    public function fillCollection($resultSet)
    {
        $className = get_class($this);

        if (count($resultSet) >= 1) {
            $this->aCollection = array();
        }

        //$dto = new dto\ViewDTO($this->oDb, $this->oLogger);
        foreach ($resultSet as $key => $row) {
//            $obj = new $className($dto);
            $obj = new $className();
            $obj->fill($row);

            $this->aCollection[] = $obj;
            unset($obj);
        }
        unset($resultSet);
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
//        if (!$orderDto->getAttribs()) {
//            $orderDto = null;
//        }

//        $limit = $limitDto->getAttribs();
        
//        if (!$limitDto || !$limitDto->getAttribs()) {
//            $limitDto = null;
//        }

//        $resultSet = $this->oMapper->getByFilter($filterDTO);
        $resultSet = $this->oMapper->getByFilter($filterDTO, $orderDto, $limitDto);

        $this->fillCollection($resultSet);

        return $this->aCollection;
    }

    public function getByQuantity(  GetByFilterDTO $filterDTO,
                                    OrderByDTO $orderDto = null,
                                    LimitDTO $limitDTO = null)
    {
//        $limitDto = new LimitDTO();
//        $limitDto->firstRow = 0;
//        $limitDto->lastRow  = 0;

//        $resultSet = $this->oMapper->getByFilter($filterDTO);
        if (!$orderDto->getAttribs()) {
            $orderDto = null;
        }
        if (!$limitDTO->getAttribs()) {
            $limitDTO = null;
        }
        $resultSet = $this->oMapper->getByFilter($filterDTO, $orderDto, $limitDTO);

        $this->fillCollection($resultSet);

        return $this->aCollection;
    }

    public function getByDate(  GetByFilterDTO $filterDTO,
                                OrderByDTO $orderDto = null,
                                  LimitDTO $limitDTO = null)
    {
//        $limitDto = new LimitDTO();
//        $limitDto->firstRow = 0;
//        $limitDto->lastRow  = 0;

//        $resultSet = $this->oMapper->getByFilter($filterDTO);
        if (!$orderDto->getAttribs()) {
            $orderDto = null;
        }

        if (!$limitDTO->getAttribs()) {
            $limitDTO = null;
        }
        $resultSet = $this->oMapper->getByFilter($filterDTO, $orderDto, $limitDTO);

        $this->fillCollection($resultSet);

        return $this->aCollection;
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

    public function getCollectionSize()
    {
        return count($this->aCollection);
    }

    public function getNext()
    {
        $index = $this->collectionIndex;

        while ($index < count($this->aCollection)) {

                $index = $this->collectionIndex;
                $this->collectionIndex ++;

                $return = $this->aCollection[$index];

            return $return;
        }
        return null;
    }

    public function getValues($bOnlyChanges = false)
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
                    $aValues[$sAttrib] = $this->aData[$sAttrib];
                }
            }
        }
        return $aValues;
    }

    public function getMapper()
    {
        return $this->oMapper;
    }
}
