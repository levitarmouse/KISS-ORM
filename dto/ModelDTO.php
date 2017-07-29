<?php

namespace levitarmouse\kiss_orm\dto;

/**
 * Description of ModelDTO
 *
 * @author gprieto
 */
class ModelDTO
{
    public $oDB;
    public $oLogger;
    public $sFileDescriptorModel;

    function __construct($sFileDescriptorModel = null)
    {
        $this->sFileDescriptorModel = $sFileDescriptorModel;
    }
}
