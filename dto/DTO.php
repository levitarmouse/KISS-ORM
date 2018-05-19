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

class DTO extends \levitarmouse\core\BasicObject
{

    public function setAttribs($arrayDictionary)
    {
        $this->_aData = $arrayDictionary;
    }
}