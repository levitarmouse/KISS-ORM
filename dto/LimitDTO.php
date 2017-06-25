<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace levitarmouse\kiss_orm\dto;

/**
 * Description of LimitDTO
 *
 * @author gprieto
 *
 * @param integer $firstRow
 * @param integer $lastRow
 */
class LimitDTO extends DTO
{
    protected $justFirst;
    
    public $firstRow;
    public $lastRow;
    public $pageNumber;
    public $pageSize;
    
    public function __construct() {
        parent::__construct();
        $this->firstRow   = 0;
        $this->lastRow    = 10;
        
        $this->pageNumber = null;
        $this->pageSize   = null;
    }

    public function setJustFirst($value = true) {
        $this->justFirst = $value;
    }

    public function justFirst() {
        return $this->justFirst;
    }

}
