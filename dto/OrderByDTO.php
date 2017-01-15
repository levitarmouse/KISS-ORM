<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace levitarmouse\kiss_orm\dto;

/**
 * Description of OrderByDTO
 *
 * @author gprieto
 */
class OrderByDTO extends \levitarmouse\core\Object
{
    CONST ASC = 'ASC';
    CONST DESC = 'DESC';

    public $asc;
    public $desc;

    public $direction;

    function __construct()
    {

    }
}
