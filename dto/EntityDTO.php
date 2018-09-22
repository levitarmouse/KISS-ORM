<?php

namespace levitarmouse\kiss_orm\dto;

/**
 * Description of EntityDTO
 *
 * @author gprieto
 */
//class EntityDTO extends \levitarmouse\core\StdObject
class EntityDTO extends ViewDTO
{
    public function __construct($useDescriptor = true)
    {
        parent::__construct($useDescriptor);
    }
}
