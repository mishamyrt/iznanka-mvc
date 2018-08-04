<?php
use \Core\View;

$router = new Core\Router();
$router->add('', ['controller' => 'Home', 'action' => 'index']);

/**
 * Routing
 */
try {
    $router->dispatch();
} catch (Exception $e) {
    switch ($e->getCode()) {
        case 404:
            View::display('404.twig');
            break;
        default:
            View::display('500.twig');
            break;
    }
}
