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
use levitarmouse\kiss_orm\dto\PrimaryKeyDTO;
use levitarmouse\kiss_orm\dto\UniqueKeyDTO;
//use levitarmouse\kiss_orm\interfaces\CollectionInterface;
//use levitarmouse\kiss_orm\interfaces\EntityInterface;
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
abstract class EntityModel extends ViewModel
implements interfaces\EntityInterface, interfaces\CollectionInterface
{
    const ALREADY_EXISTS   = 'ALREADY_EXISTS'; // Ya existe en la DB
    const CREATE_OK        = 'CREATE_OK';      // Se creó en la DB
    const CREATE_FAILED    = 'CREATE_FAILED';  // Falló la creación en la DB
    const UPDATE_OK        = 'UPDATE_OK';      // Se modificó en la DB
    const UPDATE_FAILED    = 'UPDATE_FAILED';  // Falló la modificación en la DB
    const REMOVAL_OK       = 'REMOVAL_OK';     // Se eliminó en la DB
    const REMOVAL_FAILED   = 'REMOVAL_FAILED'; // Falló la eliminación en la DB

    /**
     * Returns the next sequence id
     *
     */
    public function getNextId()
    {
        return $this->oMapper->getNextId();
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

    /**
     * @return nothing
     */
//    public function loadRelated()
//    {
//        return;
//    }

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
//                \levitarmouse\tools\logs\Logger::log($iResult);
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

        $aPrimaryKey = $this->oMapper->getFieldMappingPrimaryKey();

        if ($aPrimaryKey) {
            $aUniqueKey = $aPrimaryKey;
        } else {
            $aUniqueKey = $this->oMapper->getFieldMappingUniqueKey();
        }

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

    public function getUniqueAttribsDictionary($bAsObject = false, $bAsXml = false) {

        $x = $this->oMapper->getFieldMappingUniqueKey();

        $attribs = $this->getAttribs($bAsObject, $bAsXml);

        $dictionary = array();
        foreach ($x as $key => $value) {
            $dictionary[$key] = $attribs[$key];
        }

        return $dictionary;
    }
}
