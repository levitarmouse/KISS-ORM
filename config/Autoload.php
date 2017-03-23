<?php

function autoloadAdd_SRC($path) {
    global $aWebServicesPSR0;

    if (is_string($path)) {
        $aWebServicesPSR0[] = $path;
    }
}

function autoloadPSR_0($sFullClassName) {
    global $a_PSR0_Source;

    if (is_array($a_PSR0_Source)) {
        foreach ($a_PSR0_Source as $key => $value) {

            $aSteps = explode('\\', $sFullClassName);
            if ($aSteps) {
                $sFile = $value . implode('/', $aSteps) . '.php';

                if (file_exists($sFile)) {
                    require_once $sFile;
                    return;
                }
            }
        }
    }
}

spl_autoload_register('autoloadPSR_0');
