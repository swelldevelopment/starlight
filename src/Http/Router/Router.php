<?php
//*****************************************************************************
//*****************************************************************************
/**
 * HTTP Router Class
 *
 * @package         Starlight\Http
 * @subpackage      Router
 * @author          Matt Palermo, Christian J. Clark
 * @copyright       Copyright (c) Swell Development LLC
 * @link            http://www.swelldevelopment.com/
 **/
//*****************************************************************************
//*****************************************************************************

namespace Starlight\Http\Router;

class Router
{

    //=========================================================================
    // Class Members
    //=========================================================================
    protected $filters = [];                            // Filters for routes
    protected $routes = [];                             // Array of all routes (including named routes)
    protected $namedRoutes = [];                        // Array of all named routes
    protected $basePath = '';                           // Can be used to ignore leading part of the Request URL
    protected $match;                                   // Holds the latest match data
    protected $matchTypes = [                           // Array of default match types (regex helpers)
        'i' => '[0-9]++',
        'a' => '[0-9A-Za-z]++',
        'h' => '[0-9A-Fa-f]++',
        '*' => '.+?',
        '**' => '.++',
        '' => '[^/\.]++'
    ];

    //=========================================================================
    //=========================================================================
    /**
     * Create router
     *
     * @param array $routes
     * @param string $basePath
     * @param array $matchTypes
     */
    //=========================================================================
    //=========================================================================
    public function __construct($routes = [], $basePath = '', $matchTypes = [])
    {
        $this->addRoutes($routes);
        $this->setBasePath($basePath);
        $this->addMatchTypes($matchTypes);
    }

    //=========================================================================
    //=========================================================================
    /**
     * Set a route filter
     *
     * @param array $name
     * @param string $filter
     */
    //=========================================================================
    //=========================================================================
    public function filter($name, $filter)
    {
        $this->filters[$name] = $filter;
    }

    //=========================================================================
    //=========================================================================
    /**
     * Retrieves all routes.
     *
     * @return array All routes.
     */
    //=========================================================================
    //=========================================================================
    public function getRoutes()
    {
        return $this->routes;
    }

    //=========================================================================
    //=========================================================================
    /**
     * Add multiple routes at once from array in the following format:
     *
     * @param array $routes
     * @return void
     * @throws Exception
     */
    //=========================================================================
    //=========================================================================
    public function addRoutes($routes)
    {
        if (!is_array($routes)) {
            throw new \Exception('Routes must be an array');
        }
        foreach ($routes as $route) {
            call_user_func_array(array($this, 'map'), $route);
        }
    }

    //=========================================================================
    //=========================================================================
    /**
     * Set the base path.
     */
    //=========================================================================
    //=========================================================================
    public function setBasePath($basePath)
    {
        $this->basePath = $basePath;
    }

    //=========================================================================
    //=========================================================================
    /**
     * Add named match types. It uses array_merge so keys can be overwritten.
     *
     * @param array $matchTypes The key is the name and the value is the regex.
     */
    //=========================================================================
    //=========================================================================
    public function addMatchTypes($matchTypes)
    {
        $this->matchTypes = array_merge($this->matchTypes, $matchTypes);
    }

    //=========================================================================
    //=========================================================================
    /**
     * Map a route to a target
     *
     * @param string $method One of 5 HTTP Methods, or a pipe-separated list of multiple HTTP Methods (GET|POST|PATCH|PUT|DELETE|OPTIONS)
     * @param string $route The route regex, custom regex must start with an @. You can use multiple pre-set regex filters, like [i:id]
     * @param mixed $target The target where this route should point to. Can be anything.
     * @param string $name Optional name of this route. Supply if you want to reverse route this url in your application.
     * @throws Exception
     */
    //=========================================================================
    //=========================================================================
    public function map($method, $route, $target, $name = null)
    {
        $this->routes[] = array($method, $route, $target, $name);

        if ($name) {
            if (isset($this->namedRoutes[$name])) {
                throw new \Exception("Can not redeclare route '{$name}'");
            } else {
                $this->namedRoutes[$name] = $route;
            }
        }

        return;
    }

    //=========================================================================
    //=========================================================================
    /**
     * Match a given Request Url against stored routes
     * @param string $requestUrl
     * @param string $requestMethod
     * @return array|boolean Array with route information on success, false on failure (no match).
     */
    //=========================================================================
    //=========================================================================
    public function match($requestUrl = null, $requestMethod = null)
    {
        $match = $this->routeMatch($requestUrl, $requestMethod);

        //------------------------------------------------------------
        // We allow the target to be an array to supply other filters with it
        //------------------------------------------------------------
        $match['controller'] = null;
        $match['filters'] = [];
        if (isset($match['target']) && is_array($match['target'])) {

            //------------------------------------------------------------
            // Set filters
            //------------------------------------------------------------
            if (isset($match['target']['before'])) {
                $match['filters']['before'] = $match['target']['before'];
            }
            if (isset($match['target']['after'])) {
                $match['filters']['after'] = $match['target']['after'];
            }

            //------------------------------------------------------------
            // Set controller
            //------------------------------------------------------------
            if (isset($match['target']['controller'])) {
                $match['controller'] = $match['controller'];
            }

            //------------------------------------------------------------
            // Set other attributes
            //------------------------------------------------------------
            foreach ($match['target'] as $k => $v) {
                if (isset($match[$k]) || isset($match['filters'][$k])) {
                    continue;
                }
                $match[$k] = $v;
                continue;
            }

            //------------------------------------------------------------
            // Use target value
            //------------------------------------------------------------
            $match['target'] = $match['target']['target'];
        }

        $this->match = $match;
        return $match;
    }

    //=========================================================================
    //=========================================================================
    /**
     * Match a given Request Url against stored routes
     * @param string $requestUrl
     * @param string $requestMethod
     * @return array|boolean Array with route information on success, false on failure (no match).
     */
    //=========================================================================
    //=========================================================================
    public function routeMatch($requestUrl = null, $requestMethod = null)
    {
        $params = [];
        $match = false;

        //------------------------------------------------------------
        // set Request Url if it isn't passed as parameter
        //------------------------------------------------------------
        if ($requestUrl === null) {
            $requestUrl = (isset($_SERVER['REQUEST_URI'])) ? ($_SERVER['REQUEST_URI']) : ('/');
        }

        //------------------------------------------------------------
        // Strip query string (?a=b) from Request URL
        //------------------------------------------------------------
        if (($strpos = strpos($requestUrl, '?')) !== false) {
            $requestUrl = substr($requestUrl, 0, $strpos);
        }

        //------------------------------------------------------------
        // strip base path from request url
        //------------------------------------------------------------
        $requestUrl = substr($requestUrl, strlen($this->basePath));
        $requestUrl = str_replace('//', '/', '/' . $requestUrl);
        if ($requestUrl != '/' && substr($requestUrl, -1) == '/') {
            $requestUrl = substr($requestUrl, 0, -1);
        }

        //------------------------------------------------------------
        // Set Request Method if it isn't passed as a parameter
        //------------------------------------------------------------
        if ($requestMethod === null) {
            $requestMethod = (isset($_SERVER['REQUEST_METHOD'])) ? ($_SERVER['REQUEST_METHOD']) : ('GET');
        }

        foreach ($this->routes as $handler) {
            list($methods, $route, $target, $name) = $handler;

            $method_match = (stripos($methods, $requestMethod) !== false);

            //------------------------------------------------------------
            // Method did not match, continue to next route.
            //------------------------------------------------------------
            if (!$method_match) {
                continue;
            }

            //------------------------------------------------------------
            // * wildcard (matches all)
            //------------------------------------------------------------
            if ($route === '*') {
                $match = true;
            }
            else if ($route === '/' && empty($requestUrl)) {
                $match = true;
            }

            //------------------------------------------------------------
            // @ regex delimiter
            //------------------------------------------------------------
            else if (isset($route[0]) && $route[0] === '@') {
                $pattern = '`' . substr($route, 1) . '`u';
                $match = preg_match($pattern, $requestUrl, $params) === 1;
            }
            //------------------------------------------------------------
            // No params in url, do string comparison
            //------------------------------------------------------------
            else if (($position = strpos($route, '[')) === false) {
                $match = strcmp($requestUrl, $route) === 0;
            }
            else {
                //------------------------------------------------------------
                // Compare longest non-param string with url
                //------------------------------------------------------------
                if (strncmp($requestUrl, $route, $position) !== 0) {
                    continue;
                }
                $regex = $this->compileRoute($route);
                $match = preg_match($regex, $requestUrl, $params) === 1;
            }

            if ($match) {

                if ($params) {
                    foreach ($params as $key => $value) {
                        if (is_numeric($key)) {
                            unset($params[$key]);
                        }
                    }
                }

                return array(
                    'target' => $target,
                    'params' => $params,
                    'name' => $name
                );
            }
        }
        return false;
    }

    //=========================================================================
    //=========================================================================
    /**
     * Get the latest match data
     * @return Latest match
     */
    //=========================================================================
    //=========================================================================
    public function getMatch()
    {
        return $this->match;
    }

    //=========================================================================
    //=========================================================================
    /**
     * Compile the regex for a given route (EXPENSIVE)
     */
    //=========================================================================
    //=========================================================================
    private function compileRoute($route)
    {
        if (preg_match_all('`(/|\.|)\[([^:\]]*+)(?::([^:\]]*+))?\](\?|)`', $route, $matches, PREG_SET_ORDER)) {

            $matchTypes = $this->matchTypes;
            foreach ($matches as $match) {
                list($block, $pre, $type, $param, $optional) = $match;

                if (isset($matchTypes[$type])) {
                    $type = $matchTypes[$type];
                }
                if ($pre === '.') {
                    $pre = '\.';
                }

                //------------------------------------------------------------
                // Older versions of PCRE require the 'P' in (?P<named>)
                //------------------------------------------------------------
                $pattern = '(?:'
                    . ($pre !== '' ? $pre : null)
                    . '('
                    . ($param !== '' ? "?P<{$param}>" : null)
                    . $type
                    . '))'
                    . ($optional !== '' ? '?' : null);

                $route = str_replace($block, $pattern, $route);
            }

        }
        return "`^{$route}$`u";
    }

    //=========================================================================
    //=========================================================================
    // Run the route matching
    //=========================================================================
    //=========================================================================
    public function dispatch()
    {
        //------------------------------------------------------------
        // Dispatch the route
        //------------------------------------------------------------
        $match = $this->match();

        //------------------------------------------------------------
        // No match found, 404
        //------------------------------------------------------------
        if ($match === false) {
            header($_SERVER["SERVER_PROTOCOL"] . ' 404 Not Found');
            return '404';
        }

        //------------------------------------------------------------
        // If the target is a controller / method string
        // (Controller@method), extract to controller / target values
        //------------------------------------------------------------
        if (isset($match['target']) && !($match['target'] instanceof \Closure) && strpos($match['target'], '@') !== false) {
            //------------------------------------------------------------
            // String with controller@method
            //------------------------------------------------------------
            $parts = explode('@', $match['target']);
            $match['target'] = $parts[1];
            $match['controller'] = $parts[0];
        }

        //------------------------------------------------------------
        // See if we need to run route filters first
        //------------------------------------------------------------
        $this->_filter($match, 'before');

        //------------------------------------------------------------
        // Route match found with a Closure, just run it
        //------------------------------------------------------------
        if (isset($match['target']) && is_object($match['target']) && ($match['target'] instanceof \Closure)) {
            $response = call_user_func_array($match['target'], $match['params']);
        }
        //------------------------------------------------------------
        // Get response from a controller
        //------------------------------------------------------------
        else  {

            //------------------------------------------------------------
            // A controller has been supplied, use that and
            // the target will be the method
            //------------------------------------------------------------
            if ($match['controller']) {
                $obj = $this->_matchObject($match['controller'], $match);
                $method = $match['target'];
            }
            else {
                throw new \Exception('Invalid route controller.');
            }

            //------------------------------------------------------------
            // Run the route method
            //------------------------------------------------------------
            if (!method_exists($obj, $method)) {
                throw new \Exception('Invalid route method ' . get_class($obj) . '@' . $method);
            }
            $response = call_user_func_array([$obj, $method], $match['params']);
        }

        //------------------------------------------------------------
        // See if we need to run route filters first
        //------------------------------------------------------------
        $this->_filter($match, 'after', $response);

        return $response;
    }


    //=========================================================================
    //=========================================================================
    // Run a filter
    //=========================================================================
    //=========================================================================
    protected function _filter($match, $filter, $response = null)
    {
        //------------------------------------------------------------
        // See if there is a controller filter to run
        //------------------------------------------------------------
        if ($match['controller']) {
            $obj = $this->_matchObject($match['controller'], $match);

            //------------------------------------------------------------
            // See if there is a filter method and is usable
            //------------------------------------------------------------
            if (method_exists($obj, 'filter_' . $filter) && is_callable([$obj, 'filter_' . $filter])) {
                $this->_callFilter([$obj, 'filter_' . $filter], array_merge([$response], $match['params']));
            }

            //------------------------------------------------------------
            // See if there is a generic "filter" method
            //------------------------------------------------------------
            if (method_exists($obj, 'filter') && is_callable([$obj, 'filter'])) {
                $this->_callFilter([$obj, 'filter'], array_merge([$filter, $response], $match['params']));
            }
        }

        //------------------------------------------------------------
        // See if we need to run route filters
        //------------------------------------------------------------
        if (isset($match['filters'][$filter]) && $match['filters'][$filter]) {
            if (!is_array($match['filters'][$filter])) {
                $match['filters'][$filter] = [$match['filters'][$filter]];
            }
            foreach ($match['filters'][$filter] as $f) {

                //------------------------------------------------------------
                // Named filter, see if we have it defined
                //------------------------------------------------------------
                if (is_string($f)) {
                    $f = $this->filters[$f];
                }

                $this->_callFilter($f, array_merge([$match], $match['params']));
            }
        }
    }

    //=========================================================================
    //=========================================================================
    // Call a filter and throw error if output found
    //=========================================================================
    //=========================================================================
    protected function _callFilter($name, $args)
    {
        if (PHP_VERSION >= 8) {
            $args = array_values($args);
        }
        $val = call_user_func_array($name, $args);

        //------------------------------------------------------------
        // Before filter failed, throw exception
        //------------------------------------------------------------
        if ($val) {
            throw new \Exception($val);
        }
    }

    //=========================================================================
    //=========================================================================
    // Get a controller object for a match
    //=========================================================================
    //=========================================================================
    protected function _matchObject($clss, &$match)
    {
        $objKey = md5($clss);
        if (!isset($match['objects'])) {
            $match['objects'] = [];
        }
        $match['objects'][$objKey] = isset($match['objects'][$objKey]) ? $match['objects'][$objKey] : new $clss($this,
            $match);
        return $match['objects'][$objKey];
    }
}
