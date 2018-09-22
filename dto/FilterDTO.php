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
    const BTWE = '$BTWE';
    const NE   = '$NE';
    const GT   = '$GT';
    const GTE  = '$GTE';
    const LT   = '$LT';
    const LTE  = '$LTE';
    const LIKE = '$LIKE';
    const MIN = '$MIN';
    const MAX = '$MAX';
    const CONCAT = '||$CONCAT$||';
    const SUBFILTER = '||$SUBFILTER$||';
    
    public $raw;


    // TODO
//    public function addAnd($key, $value) {
//
//    }

    // TODO
//    public static function addOr($value) {
//
//        return self::O_R.$value;
//    }

    // TODO
    public static function Between($min, $max) {

        $values = new \stdClass();
        $values->min = $min;
        $values->max = $max;

        $strConcat = json_encode($values);

        $exp = self::getBtwExpression().self::CONCAT.$strConcat;

        return $exp;
    }

    public static function getBtwExpression() {

        $exp = self::BTW.self::MIN.self::A_ND.self::MAX;

        return $exp;
    }

    public static function addFilter($logic, $value) {
        $expression = $logic.$value;
        return FilterDTO::SUBFILTER.$expression;
    }

}
