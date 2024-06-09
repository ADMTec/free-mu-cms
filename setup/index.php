<?php
    ob_start();
    session_save_path(realpath(dirname(__FILE__) . '/..') . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'sessions');
    session_start();
    error_reporting(E_ALL);
    define('PHP_VER_MIN', '8.1.0');
    define('INSTALL_DIR', realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR);
    if(file_exists(realpath(dirname(__FILE__) . '/..') . DIRECTORY_SEPARATOR . 'constants.php')){
        require_once(realpath(dirname(__FILE__) . '/..') . DIRECTORY_SEPARATOR . 'constants.php');
    }
    require_once(SYSTEM_PATH . DIRECTORY_SEPARATOR . 'common.php');
    require_once(BASEDIR . 'vendor/autoload.php');

	use Tracy\Debugger;
	
	Debugger::enable(Debugger::PRODUCTION, BASEDIR . 'application' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'Tracy');
	
    if(!version_compare(PHP_VER_MIN, PHP_VERSION, '<')){
        throw new Exception('You must be using PHP ' . PHP_VER_MIN . ' or better. You are currently using: ' . PHP_VERSION);
    }
	
    date_default_timezone_set('UTC');
	
    $request = load_class('request');
	
    require(SYSTEM_PATH . DIRECTORY_SEPARATOR . 'controller.class.php');
	
    $router = load_class('router');
    $router->route($request);
	
    ob_end_flush();

