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
    /**
     * Returns the next sequence id
     *
     */
    public function getNextId()
    {
        return $this->oMapper->getNextId();
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

        $aPrimaryKey = $this->oMapper->getPrimaryKey();
        if (is_array($aPrimaryKey) && count($aPrimaryKey) > 0) {
            try {
                foreach ($aPrimaryKey as $objAttrib => $dbField) {
//                    $attrib = $this->oMapper->getAttribByFieldName($dbField);
                    $aWhere[$dbField] = $this->$objAttrib;
                }

                if (count($aWhere) == 0) {
                    throw new Exception('MAPPED_ENTITY_ERROR_COULD_NOT_DETERMINE_CONDITION_FOR_MODIFICATION');
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

    public function get($asArray = false) {

        $attribs = $this->getValues();

        $filterDTO = new dto\GetByFilterDTO($attribs);
        $orderDto = null;
        $limitDto = null;

        $result = $this->getByFilter($filterDTO, $orderDto, $limitDto, $asArray);

        return $result;
    }
}
