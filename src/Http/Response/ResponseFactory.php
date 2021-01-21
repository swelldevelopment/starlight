<?php
//*****************************************************************************
//*****************************************************************************
/**
 * HTTP Response Factory Class
 *
 * @package         Starlight\Http
 * @subpackage      Router
 * @author          Matt Palermo, Christian J. Clark
 * @copyright       Copyright (c) Swell Development LLC
 * @link            http://www.swelldevelopment.com/
 **/
//*****************************************************************************
//*****************************************************************************

namespace Starlight\Http\Response;

class ResponseFactory
{
    protected static $_response;

    //=========================================================================
    //=========================================================================
    /**
     * Passes function calls to the default response object
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
        if (!self::$_response) {
            self::$_response = new \Starlight\Http\Response\Response();
        }

        //-----------------------------------------------------------------
        // See if the method is usable
        //-----------------------------------------------------------------
        if (!method_exists(self::$_response, $name)) {
            throw new \Exception('Method ' . $name . ' does not exist.');
        }
        if (!is_callable([self::$_response, $name])) {
            throw new \Exception('Method ' . $name . ' is not callable.');
        }

        //-----------------------------------------------------------------
        // Execute the call
        //-----------------------------------------------------------------
        $v = call_user_func_array([self::$_response, $name], $args);
        return $v;
    }
}
