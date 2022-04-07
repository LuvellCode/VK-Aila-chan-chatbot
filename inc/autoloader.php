<?php
/**
 * Created by Hito.
 * Date: 05.04.2020
 * Time: 15:58
 * VK: https://vk.com/igordux
 */
spl_autoload_register(function ($class) {
    $dir = 'classes/';
    $file = $dir.$class.'.php';
    if (file_exists($file)) {
        require_once($file);
        return true;
    }
    return false;
});