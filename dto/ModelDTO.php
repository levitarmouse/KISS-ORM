<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

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

    function __construct($oDB = null, $oLogger = null, $sFileDescriptorModel = null)
    {
        $this->oDB                  = $oDB;
        $this->oLogger              = $oLogger;
        $this->sFileDescriptorModel = $sFileDescriptorModel;
    }

    //put your code here
}
