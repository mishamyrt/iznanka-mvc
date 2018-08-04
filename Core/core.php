<?php

namespace Core {

    use App\Config;
    use PDO;

    abstract class Controller
    {

        /**
         * Parameters from the matched route
         * @var array
         */
        protected $route_params = [];

        /**
         * Class constructor
         *
         * @param array $route_params  Parameters from the route
         *
         * @return void
         */
        public function __construct($route_params)
        {
            $this->route_params = $route_params;
        }

        /**
         * Magic method called when a non-existent or inaccessible method is
         * called on an object of this class. Used to execute before and after
         * filter methods on action methods. Action methods need to be named
         * with an "Action" suffix, e.g. indexAction, showAction etc.
         *
         * @param string $name  Method name
         * @param array $args Arguments passed to the method
         *
         * @return void
         */
        public function __call($name, $args)
        {
            $method = $name . 'Action';

            if (method_exists($this, $method)) {
                if ($this->before() !== false) {
                    call_user_func_array([$this, $method], $args);
                    $this->after();
                }
            } else {
                throw new \Exception("Method $method not found in controller " . get_class($this));
            }
        }

        /**
         * Before filter - called before an action method.
         *
         * @return void
         */
        protected function before()
        {
        }

        /**
         * After filter - called after an action method.
         *
         * @return void
         */
        protected function after()
        {
        }
    }
/**
 * Error and exception handler
 *
 * PHP version 7.0
 */
    class Error
    {

        /**
         * Error handler. Convert all errors to Exceptions by throwing an ErrorException.
         *
         * @param int $level  Error level
         * @param string $message  Error message
         * @param string $file  Filename the error was raised in
         * @param int $line  Line number in the file
         *
         * @return void
         */
        public static function errorHandler($level, $message, $file, $line)
        {
            if (error_reporting() !== 0) { // to keep the @ operator working
                throw new \ErrorException($message, 0, $level, $file, $line);
            }
        }

        /**
         * Exception handler.
         *
         * @param Exception $exception  The exception
         *
         * @return void
         */
        public static function exceptionHandler($exception)
        {
            // Code is 404 (not found) or 500 (general error)
            $code = $exception->getCode();
            if ($code != 404) {
                $code = 500;
            }
            http_response_code($code);

            if (\App\Config::SHOW_ERRORS) {
                echo "<h1>Fatal error</h1>";
                echo "<p>Uncaught exception: '" . get_class($exception) . "'</p>";
                echo "<p>Message: '" . $exception->getMessage() . "'</p>";
                echo "<p>Stack trace:<pre>" . $exception->getTraceAsString() . "</pre></p>";
                echo "<p>Thrown in '" . $exception->getFile() . "' on line " . $exception->getLine() . "</p>";
            } else {
                $log = dirname(__DIR__) . '/logs/' . date('Y-m-d') . '.txt';
                ini_set('error_log', $log);

                $message = "Uncaught exception: '" . get_class($exception) . "'";
                $message .= " with message '" . $exception->getMessage() . "'";
                $message .= "\nStack trace: " . $exception->getTraceAsString();
                $message .= "\nThrown in '" . $exception->getFile() . "' on line " . $exception->getLine();

                error_log($message);

                View::renderTemplate("$code.html");
            }
        }
    }

/**
 * Base model
 *
 * PHP version 7.0
 */
    abstract class Model
    {

        /**
         * Get the PDO database connection
         *
         * @return mixed
         */
        protected static function getDB()
        {
            static $db = null;

            if ($db === null) {
                $dsn = 'mysql:host=' . Config::DB_HOST . ';dbname=' . Config::DB_NAME . ';charset=utf8';
                $db = new PDO($dsn, Config::DB_USER, Config::DB_PASSWORD);

                // Throw an Exception when an error occurs
                $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            }

            return $db;
        }
    }
    class Router
    {

        /**
         * Associative array of routes (the routing table)
         * @var array
         */
        protected $routes = [];

        /**
         * Parameters from the matched route
         * @var array
         */
        protected $params = [];

        /**
         * Add a route to the routing table
         *
         * @param string $route  The route URL
         * @param array  $params Parameters (controller, action, etc.)
         *
         * @return void
         */
        public function add($route, $params = [])
        {
            // Convert the route to a regular expression: escape forward slashes
            $route = preg_replace('/\//', '\\/', $route);

            // Convert variables e.g. {controller}
            $route = preg_replace('/\{([a-z-]+)\}/', '(?P<\1>[a-z-]+)', $route);

            // Convert variables with custom regular expressions e.g. {id:\d+}
            $route = preg_replace('/\{([a-z-]+):([^\}]+)\}/', '(?P<\1>\2)', $route);

            // Add start and end delimiters, and case insensitive flag
            $route = '/^' . $route . '$/i';

            $this->routes[$route] = $params;
        }

        /**
         * Get all the routes from the routing table
         *
         * @return array
         */
        public function getRoutes()
        {
            return $this->routes;
        }

        /**
         * Match the route to the routes in the routing table, setting the $params
         * property if a route is found.
         *
         * @param string $url The route URL
         *
         * @return boolean  true if a match found, false otherwise
         */
        public function match($url)
        {
            foreach ($this->routes as $route => $params) {
                if (preg_match($route, $url, $matches)) {
                    // Get named capture group values
                    foreach ($matches as $key => $match) {
                        if (is_string($key)) {
                            $params[$key] = $match;
                        }
                    }

                    $this->params = $params;
                    return true;
                }
            }

            return false;
        }

        /**
         * Get the currently matched parameters
         *
         * @return array
         */
        public function getParams()
        {
            return $this->params;
        }

        /**
         * Dispatch the route, creating the controller object and running the
         * action method
         *
         * @param string $url The route URL
         *
         * @return void
         */
        public function dispatch()
        {
            $url = $_SERVER['REQUEST_URI'];
            $url = substr($url, -1) === '/' ? substr($url, 0, -1) : $url;
            $url = $this->removeQueryStringVariables($url);

            if ($this->match($url)) {
                $controller = $this->params['controller'];
                $controller = $this->convertToStudlyCaps($controller);
                $controller = $this->getNamespace() . $controller;

                if (class_exists($controller)) {
                    $controller_object = new $controller($this->params);

                    $action = $this->params['action'];
                    $action = $this->convertToCamelCase($action);

                    if (preg_match('/action$/i', $action) == 0) {
                        $controller_object->$action();

                    } else {
                        throw new \Exception("Method $action in controller $controller cannot be called directly - remove the Action suffix to call this method");
                    }
                } else {
                    throw new \Exception("Controller class $controller not found");
                }
            } else {
                throw new \Exception('No route matched.', 404);
            }
        }

        /**
         * Convert the string with hyphens to StudlyCaps,
         * e.g. post-authors => PostAuthors
         *
         * @param string $string The string to convert
         *
         * @return string
         */
        protected function convertToStudlyCaps($string)
        {
            return str_replace(' ', '', ucwords(str_replace('-', ' ', $string)));
        }

        /**
         * Convert the string with hyphens to camelCase,
         * e.g. add-new => addNew
         *
         * @param string $string The string to convert
         *
         * @return string
         */
        protected function convertToCamelCase($string)
        {
            return lcfirst($this->convertToStudlyCaps($string));
        }

        /**
         * Remove the query string variables from the URL (if any). As the full
         * query string is used for the route, any variables at the end will need
         * to be removed before the route is matched to the routing table. For
         * example:
         *
         *   URL                           $_SERVER['QUERY_STRING']  Route
         *   -------------------------------------------------------------------
         *   localhost                     ''                        ''
         *   localhost/?                   ''                        ''
         *   localhost/?page=1             page=1                    ''
         *   localhost/posts?page=1        posts&page=1              posts
         *   localhost/posts/index         posts/index               posts/index
         *   localhost/posts/index?page=1  posts/index&page=1        posts/index
         *
         * A URL of the format localhost/?page (one variable name, no value) won't
         * work however. (NB. The .htaccess file converts the first ? to a & when
         * it's passed through to the $_SERVER variable).
         *
         * @param string $url The full URL
         *
         * @return string The URL with the query string variables removed
         */
        protected function removeQueryStringVariables($url)
        {
            if ($url != '') {
                $parts = explode('&', $url, 2);

                if (strpos($parts[0], '=') === false) {
                    $url = $parts[0];
                } else {
                    $url = '';
                }
            }

            return $url;
        }

        /**
         * Get the namespace for the controller class. The namespace defined in the
         * route parameters is added if present.
         *
         * @return string The request URL
         */
        protected function getNamespace()
        {
            $namespace = 'App\Controllers\\';

            if (array_key_exists('namespace', $this->params)) {
                $namespace .= $this->params['namespace'] . '\\';
            }

            return $namespace;
        }
    }
    class Tools
    {
        public static function escapeUrl(string $url): string
        {
            return substr($url, -1) === '/' ? substr($url, 0, -1) : $url;
        }
        public static function escapeFilePath(string $url): string
        {
            return substr($url, 0, 1) === '/' ? substr($url, 1) : $url;
        }
    }

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
}
