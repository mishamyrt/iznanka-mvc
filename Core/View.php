<?php

namespace Core;

/**
 * View
 *
 * PHP version 7.0
 */
class View
{
    public static function display(string $template, array $args = []): void
    {
        echo self::render($template, $args);
    }

    public static function escapePath(string $path): string {
        return substr($path, 0, 1) === '/' ? substr($path, 1) : $path;
    }
    
    /**
     * Render a view template using Twig
     *
     * @param string $template  The template file
     * @param array $args  Associative array of data to display in the view (optional)
     *
     * @return void
     */
    public static function render(string $template, array $args = []): void
    {
        static $twig = null;

        if ($twig === null) {
            $loader = new \Twig_Loader_Filesystem(dirname(__DIR__) . '/App/Views');
            $twig = new \Twig_Environment($loader);
            $anticache = new \Twig_SimpleFunction(
                'anticache', function ($url) {
                    $path = dirname(__DIR__) . '/public/' . self::escapePath($url);
                    return file_exists($path) ? $url . '?' . filemtime($path) : $url;
                }
            );
            $twig->addFunction($anticache);
        }

        echo $twig->render($template, $args);
    }
}
