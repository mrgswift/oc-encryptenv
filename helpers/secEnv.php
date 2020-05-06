<?php
use mrgswift\EncryptEnv\Action\Decrypt;

if (!function_exists('secEnv')) {

    function secEnv($name, $fallback='') {

        $configval = (new Decrypt)->get($name);

        return isset($configval) ? $configval : $fallback;
    }
}