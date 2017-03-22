<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace levitarmouse\kiss_orm\dto;

/**
 * Description of UniqueKeyDTO
 *
 * @author gabriel
 */
class UniqueKeyDTO extends DTO
{
    public function __construct($dictionary)
    {
        parent::__construct();
        foreach ($dictionary as $key => $value) {
            $this->$key = $value;
        }
    }
}
