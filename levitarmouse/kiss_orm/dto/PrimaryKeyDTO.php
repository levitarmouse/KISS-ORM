<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace levitarmouse\kiss_orm\dto;

/**
 * Description of PrimaryKeyDTO
 *
 * @author gabriel
 */
class PrimaryKeyDTO // extends DTO
{
    protected $primaryKey;

    public function __construct($primaryKey = null)
    {
        $this->setPK($primaryKey);
    }

    public function setPK($primaryKey)
    {
        $this->primaryKey = $primaryKey;
    }

    public function getPK() {
        return $this->primaryKey;
    }
}
