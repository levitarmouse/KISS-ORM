<?php

namespace levitarmouse\kiss_orm\dto;

/**
 * Description of EntityDTO
 *
 * @author gprieto
 */
class ViewDTO extends \levitarmouse\core\StdObject
{
    public $useDescriptor;

    function __construct($useDescriptor = true)
    {
        $this->start($useDescriptor);
    }

    public function start($useDescriptor)
    {
        $this->useDescriptor = (isset($useDescriptor)) ? $useDescriptor : true;

        $this->started = true;
    }

    public function started() {
        return $this->started;
    }
}
