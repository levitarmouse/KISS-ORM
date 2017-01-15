<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace levitarmouse\kiss_orm\dto;

/**
 * Description of EntityDTO
 *
 * @author gprieto
 */
//class EntityDTO extends \levitarmouse\core\Object
class EntityDTO
{

    public $oDB;
    public $oLogger;
    public $pkDTO;
    public $ukDTO;

    function __construct($oDB = null, $oLogger = null, PrimaryKeyDTO $pkDTO = null, UniqueKeyDTO $ukDTO = null)
//    function __construct(PrimaryKeyDTO $pkDTO = null, UniqueKeyDTO $ukDTO = null)
    {
        $this->oDB     = $oDB;
        $this->oLogger = $oLogger;
        $this->pkDTO   = $pkDTO;
        $this->ukDTO   = $ukDTO;
    }

}
