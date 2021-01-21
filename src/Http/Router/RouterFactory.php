<?php
//*****************************************************************************
//*****************************************************************************
/**
 * HTTP Router Factory Class
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

//*****************************************************************************
//*****************************************************************************
/**
 * This class passes calls to the router object
 *
 * @package app\classes\facades
 */
//*****************************************************************************
//*****************************************************************************
class RouterFactory
{
    protected static $_router;
    protected static $_match;
    protected static $_actions = ['GET', 'POST', 'PATCH', 'PUT', 'DELETE', 'OPTIONS'];
    protected static $_scope = '';

    //=========================================================================
    //=========================================================================
    // Initialize router
    //=========================================================================
    //=========================================================================
    public static function init($router = null, $routeBase = null, $classAlias = true)
    {
        //------------------------------------------------------------
        // Initialize the router and get the routes
        //------------------------------------------------------------
        $router = ($router) ?: (new \Starlight\Http\Router\Router([], $routeBase));
        static::$_router = $router;

        //------------------------------------------------------------
        // Alias the classes
        //------------------------------------------------------------
        if ($classAlias) {
            class_alias('\Starlight\Http\Response\ResponseFactory', 'Response');
            class_alias('\Starlight\Http\Router\RouterFactory', 'Route');
        }
    }

    //=========================================================================
    //=========================================================================
    /**
     * Passes function calls to the default router object
     *
     * @param $name
     * @param $args
     * @return mixed
     * @throws \Exception
     */
    //=========================================================================
    //=========================================================================
    public static function __callStatic($name, $args)
    {
        //------------------------------------------------------------
        // Action route mapping
        //------------------------------------------------------------
        if (in_array(strtoupper($name), self::$_actions) || $name == 'any') {
            return call_user_func_array([__CLASS__, '_actionMap'], array_merge([$name], $args));
        }

        //------------------------------------------------------------
        // See if the method is usable
        //------------------------------------------------------------
        if (!method_exists(self::$_router, $name)) {
            throw new \Exception('Method ' . $name . ' in does not exist.');
        }
        if (!is_callable([self::$_router, $name])) {
            throw new \Exception('Method ' . $name . ' in is not callable.');
        }

        //------------------------------------------------------------
        // Execute the call
        //------------------------------------------------------------
        return call_user_func_array([self::$_router, $name], $args);
    }

    //=========================================================================
    //=========================================================================
    // Automatically map a resource controller to actionable routes
    //=========================================================================
    //=========================================================================
    public static function resource($route, $target, $name = null)
    {
        //------------------------------------------------------------
        // Adjustments
        //------------------------------------------------------------
        $route = '/' . $route;
        if (!is_array($target)) {
            $target = ['target' => $target];
        }

        //------------------------------------------------------------
        // Map the routes
        //------------------------------------------------------------
        static::get(str_replace('//', '/', $route), ['target' => $target['target'] . '@index']);
        static::get(str_replace('//', '/', $route . '/create'), ['target' => $target['target'] . '@create']);
        static::post(str_replace('//', '/', $route), ['target' => $target['target'] . '@store']);
        static::get(str_replace('//', '/', $route . '/[i:id]'), ['target' => $target['target'] . '@show']);
        static::get(str_replace('//', '/', $route . '/[i:id]/edit'), ['target' => $target['target'] . '@edit']);
        static::put(str_replace('//', '/', $route . '/[i:id]'), ['target' => $target['target'] . '@update']);
        static::patch(str_replace('//', '/', $route . '/[i:id]'), ['target' => $target['target'] . '@update']);
        static::delete(str_replace('//', '/', $route . '/[i:id]'), ['target' => $target['target'] . '@destroy']);
        static::options(str_replace('//', '/', $route), ['target' => $target['target'] . '@options']);
    }

    //=========================================================================
    //=========================================================================
    // Map an action route
    //=========================================================================
    //=========================================================================
    protected static function _actionMap($method, $route, $target, $name = null)
    {
        $method = strtoupper($method);
        $prefix = self::$_scope;

        //------------------------------------------------------------
        // See if we have filters for the scope
        //------------------------------------------------------------
        if (is_array(self::$_scope)) {
            if (!is_array($target)) {
                $target = ['target' => $target];
            }
            $prefix = self::$_scope['target'];

            //------------------------------------------------------------
            // Set before/after filters
            //------------------------------------------------------------
            $filters = [];
            if (isset(self::$_scope['before'])) {
                $filters['before'] = self::$_scope['before'];
            }
            if (isset(self::$_scope['after'])) {
                $filters['before'] = self::$_scope['after'];
            }

            //------------------------------------------------------------
            // Controller
            //------------------------------------------------------------
            if (self::$_scope['controller']) {
                $target['controller'] = self::$_scope['controller'];
            }

            //------------------------------------------------------------
            // Set any other attributes needed
            //------------------------------------------------------------
            foreach (self::$_scope as $k => $v) {
                if ($target[$k] || $filters[$k]) {
                    continue;
                }
                $target[$k] = $v;
            }

            //------------------------------------------------------------
            // Add all filters
            //------------------------------------------------------------
            foreach ($filters as $k => $v) {

                if (!is_array($v)) {
                    $v = [$v];
                }

                //------------------------------------------------------------
                // Filter doesn't exist, create it
                //------------------------------------------------------------
                if (!$target[$k]) {
                    $target[$k] = $v;
                }
                //------------------------------------------------------------
                // Filter exists and is already an array, add to it
                //------------------------------------------------------------
                elseif (is_array($target[$k])) {
                    $target[$k] = array_merge($v, $target[$k]);
                }
                //------------------------------------------------------------
                // Filter exists, but is not an array, convert it and add to it
                //------------------------------------------------------------
                else {
                    $target[$k] = array_merge($v, [$target[$k]]);
                }
            }
            //die('<pre>'.print_r($target, true));
        }

        //------------------------------------------------------------
        // Change any to match all
        //------------------------------------------------------------
        if ($method == 'ANY') {
            $method = implode('|', self::$_actions);
        }
        $route = str_replace('//', '/', $prefix . $route);
        self::map(strtoupper($method), $route, $target, $name);
    }

    //=========================================================================
    //=========================================================================
    /**
     * Get a scoped route map
     *
     * @param $scope
     */
    //=========================================================================
    //=========================================================================
    public static function scope($scope, $closure, $options = [])
    {
        self::$_scope = $scope;
        $closure();
        self::$_scope = null;
    }
}
