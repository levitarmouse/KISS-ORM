<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace levitarmouse\kiss_orm\dto;

/**
 * Description of FilterDTO
 *
 * @author gabriel
 */
abstract class FilterDTO extends DTO {

    const A_ND = '$AND';
    const O_R  = '$OR';
    const BTW  = '$BTW';
    const NE   = '$NE';
    const GT   = '$GT';
    const GTE  = '$GTE';
    const LT   = '$LT';
    const LTE  = '$LTE';
    const LIKE = '$LIKE';


    // TODO
    public function addAnd($key, $value) {

    }

    // TODO
    public static function addOr($value) {

        return self::O_R.$value;
    }

    // TODO
    public function addBetween($key, $value) {

    }

}