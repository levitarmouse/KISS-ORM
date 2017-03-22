<?php

/**
 * GetByExampleDTO class
 *
 * PHP version 5
 *
 * @category  FrontEnd
 * @package   ORM
 * @created   May 22, 2014
 * @author    Gabriel Prieto <gabriel@levitarmouse.com>
 * @copyright 2014 Levitarmouse
 * @license   Levitarmouse http://www.levitarmouse.com
 * @link      http://www.levitarmouse.com
 *
 */

namespace levitarmouse\kiss_orm\dto;

/**
 * Class GetByExampleDTO
 *
 * GetByExampleDTO class
 * Simple get by example dto, just set the attributes wich you want to compare.
 *
 * Set arrays for IN conditions.
 *
 * PHP version 5
 *
 * @category  FrontEnd
 * @package   ORM
 * @created   May 22, 2014
 * @author    Gabriel Prieto <gabriel@levitarmouse.com>
 * @copyright 2014 Levitarmouse
 * @license   Levitarmouse http://www.levitarmouse.com
 * @link      http://www.levitarmouse.com
 *
 */

class DTO
{
    private $_aData;

    public function __construct()
    {
        $this->_aData = array();
    }

    public function getAttribs()
    {
        return $this->_aData;
    }

    public function setAttribs($dictionary)
    {
        $this->_aData = $dictionary;
    }

    public function __get($name)
    {
        $return = ( isset($this->_aData[$name]) ) ? $this->_aData[$name] : null;
        return $return;
    }

    public function __set($name, $value)
    {
        $this->_aData[$name] = $value;
    }

    public function __isset($name)
    {
        $return = ( array_key_exists($name, $this->_aData) ) ? true : false;
        return $return;
    }

    public function __call($name, $arguments)
    {
        throw new Exception('ERROR_METHOD_DOES_NOT_EXIST ['.$name.']');
    }

//    public static function __callStatic($name, $arguments)
//    {
//        throw new Exception('ERROR_STATIC_METHOD_DOES_NOT_EXIST ['.$name.']');
//    }

//    public function __isset($name)
//    {
////        throw new Exception('ERROR_PROPERTY_DOES_NOT_EXIST ['.$name.']');
////        return 'ERROR_PROPERTY_DOES_NOT_EXIST ['.$name.']';
//    }

//    public function __unset($name)
//    {
////        throw new Exception('ERROR_PROPERTY_DOES_NOT_EXIST ['.$name.']');
////        return 'ERROR_PROPERTY_DOES_NOT_EXIST ['.$name.']';
//    }

}