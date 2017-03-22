<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace levitarmouse\kiss_orm;

/**
 * Description of LoggerDecorator
 *
 * @author gprieto
 */
class DbLogger implements \levitarmouse\core\log\LoggerInterface
{
    protected $oLogger;
    public function __construct($oLogger)
    {
        $this->oLogger = $oLogger;
    }

    public function logDetectChanges($msg)
    {
        $this->logDebug($msg);
    }

    public function logDbTimes($msg)
    {
        $this->logDebug($msg);
    }

    public function logDebug($message)
    {
        $this->oLogger->logDebug($message);
    }

    public function logwarning($message)
    {
        $this->oLogger->logWarning($message);
    }

    public function logNotice($message)
    {
        $this->oLogger->logNotice($message);
    }

    public function logInfo($message)
    {
        $this->oLogger->logInfo($message);
    }

    public function logTrace($message)
    {
        $this->oLogger->logDebug($message);
    }
}
