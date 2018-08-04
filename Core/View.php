<?php

namespace Core;

/**
 * View
 *
 * PHP version 7.0
 */
class View
{
    /**
     * Render a view template using Twig
     *
     * @param string $template  The template file
     * @param array $args  Associative array of data to display in the view (optional)
     *
     * @return void
     */
    public static function display($template, $args = [])
    {
        echo self::render($template, $args);
    }
    public static function render($template, $args = [])
    {
        static $twig = null;

        if ($twig === null) {
            $loader = new \Twig_Loader_Filesystem(dirname(__DIR__) . '/App/Views');
            $twig = new \Twig_Environment($loader);
            $anticache = new \Twig_SimpleFunction(
                'anticache', function ($url) {
                    $path = dirname(__DIR__) . '/public/' . Tools::escapeFilePath($url);
                    return file_exists($path) ? $url . '?' . filemtime($path) : $path;
                }
            );
            $twig->addFunction($anticache);
        }

        echo $twig->render($template, $args);
    }
}
