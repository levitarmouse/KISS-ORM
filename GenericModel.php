<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace levitarmouse\kiss_orm;

/**
 * Description of GenericEntity
 *
 * @author gabriel
 */
class GenericModel {
    
    protected static $mapper;
    
    private static function init() {
        if (!self::$mapper) {
            self::$mapper = new Mapper();
        }
    }
    
    public static function rawyQuery($query, $bindings) {
        
        self::init();
        $mapper = self::$mapper;
        
        $rs = self::$mapper->select($query, $bindings);
        
        return $rs;
        
    }    
}
