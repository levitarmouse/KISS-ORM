<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace levitarmouse\kiss_orm\dto;

/**
 * Description of GetFiltered
 *
 * @author gprieto
 */
class GetByFilterDTO extends FilterDTO
{

    public function getFilter()
    {
        return $this->getAttribs();
    }
}
