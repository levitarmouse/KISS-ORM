<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace levitarmouse\kiss_orm\database;

/**
 * Description of DB
 *
 * @author gabriel
 */
class Database
{
    public  $oDb;

    public function __construct($oProxy) {
        $this->oDb = $oProxy;
    }

    public function selectWithBindings($sSql, $aBindings)
    {

        foreach ($aBindings as $key => $value) {
            if (is_array($value)) {

                $toReplace = ':'.$key;

                $bndStr = '';
                $first = true;
                foreach ($value as $index => $bindValue) {
                    $comma = ($first) ? '' : ', ';
                    $bndStr .= $comma.':'.$key.$index;
                    $aBindings[$key.$index] = $bindValue;
                    $first = false;
                }
                
                $sSql = str_replace($toReplace, $bndStr, $sSql);
                
                unset($aBindings[$key]);
            }
        }
        
        
//        foreach ($aBindings as $key => $value) {
//            $bLike = strlen(strstr($value, '{{LIKE}}')) > 1;
//            if ($bLike) {
//                $value = str_replace('{{LIKE}}', '%', $value);
//            }
//            $sSql = str_replace(':'.$key, "'".$value."'", $sSql);
//        }

        $aReturn = $this->oDb->select($sSql, $aBindings);
        return $aReturn;
    }

    public function select($sQuery)
    {
        $aReturn = $this->oDb->select($sQuery);
        return $aReturn;
    }

    public function executeWithBindings($sSql, $aBindings)
    {
//        foreach ($aBindings as $key => $value) {
//            $sSql = str_replace(':'.$key, "'".$value."'", $sSql);
//        }

        $aReturn = $this->oDb->execute($sSql, $aBindings);
        return $aReturn;
    }

    public function insertWithBindings($sSql, $aBindings)
    {
//        foreach ($aBindings as $key => $value) {
//            $sSql = str_replace(':'.$key, "'".$value."'", $sSql);
//        }

        $aReturn = $this->oDb->insert($sSql, $aBindings);
        return $aReturn;
    }

    public function insert($sQuery)
    {
        $aReturn = $this->oDb->insert($sQuery);
        return $aReturn;
    }

    public function execute($sQuery)
    {
        $aReturn = $this->oDb->execute($sQuery);
        return $aReturn;
    }
}