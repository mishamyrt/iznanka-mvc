<?php
$dir = dirname(__DIR__);
require $dir . '/vendor/autoload.php';

error_reporting(E_ALL);
set_error_handler('Core\Error::errorHandler');
set_exception_handler('Core\Error::exceptionHandler');

require $dir . '/App/App.php';