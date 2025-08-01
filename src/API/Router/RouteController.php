<?php
//*****************************************************************************
//*****************************************************************************
/**
 * Route Controller Class
 *
 * @package         Starlight\API
 * @subpackage      Router
 * @author          Matt Palermo, Christian J. Clark
 * @copyright       Copyright (c) Swell Development LLC
 * @link            http://www.swelldevelopment.com/
 **/
//*****************************************************************************
//*****************************************************************************

namespace Starlight\API\Router;
use \Starlight\Http\JWT\Token;

class RouteController
{
    //------------------------------------------------------------
    // Holds the current auth token (if applicable)
    //------------------------------------------------------------
    protected $authToken;

    //------------------------------------------------------------
    // Default before filters to use
    //------------------------------------------------------------
    protected $filterBefore = ['auth'];

    //=========================================================================
    //=========================================================================
    /**
     * Call any before filters if set
     *
     * @return mixed
     */
    //=========================================================================
    //=========================================================================
    public function filter_before(Array $args)
    {
        if (!is_array($this->filterBefore)) {
            $this->filterBefore = [$this->filterBefore];
        }

        //------------------------------------------------------------
        // Run each filter
        //------------------------------------------------------------
        foreach ($this->filterBefore as $filter) {
            if (!method_exists($this, 'filter_' . $filter)) {
                continue;
            }
            $v = call_user_func_array([$this, 'filter_' . $filter], [$args]);
            if ($v) {
                return $v;
            }
        }
    }

    //=========================================================================
    //=========================================================================
    /**
     * Authorization filter
     */
    //=========================================================================
    //=========================================================================
    protected function filter_auth()
    {
        //------------------------------------------------------------
        // Start Payload (Failed authentication only)
        //------------------------------------------------------------
        $payload = [];

        //------------------------------------------------------------
        // Check for the authorization header
        //------------------------------------------------------------
        $headers = getallheaders(); // \phpOpenFW\Helpers\HTTP::GetAllHeaders();

        //------------------------------------------------------------
        // Check for Authorization Header
        //------------------------------------------------------------
        if (isset($headers['Authorization'])) {
            $jwt = str_replace('Bearer ', '', $headers['Authorization']);
            $auth_type = 'header';
        }
        //------------------------------------------------------------
        // Check for the jwt in a cookie
        //------------------------------------------------------------
        else if (isset($_COOKIE['jwt'])) {
            $jwt = $_COOKIE['jwt'];
            $auth_type = 'cookie';
        }

        //------------------------------------------------------------
        // Do we have a token?
        //------------------------------------------------------------
        if (!empty($jwt)) {
            try {
                $this->authToken = Token::key(API_JWT_KEY)->decode($jwt);
                $this->auth_success();
                return;
            }
            catch (\Exception $e) {
                $this->auth_exception($auth_type, $e);
                if (STARLIGHT_ERROR_MODE > 2) {
                    $file = $e->getFile();
                    $line = $e->getLine();
                    $msg = $e->getMessage();
                    $payload['message'] = $file . ' @ Line ' . $line . ': ' . $msg;
                }
            }
        }

        //------------------------------------------------------------
        // Return Response
        //------------------------------------------------------------
        return $this->not_authenticated($payload);
    }

    //=========================================================================
    //=========================================================================
    /**
     * Not authenticated
     */
    //=========================================================================
    //=========================================================================
    protected function not_authenticated($payload, $exit=true)
    {
        if ($exit) {
            print \Responses::NotAuthenticated($payload);
            exit;
        }
        else {
            return \Responses::NotAuthenticated($payload);
        }
    }

    //=========================================================================
    //=========================================================================
    /**
     * Handle authorization exception
     */
    //=========================================================================
    //=========================================================================
    protected function auth_exception($authType, \Exception $exception)
    {
        // Override this method to handle auth exceptions
    }

    //=========================================================================
    //=========================================================================
    /**
     * Successful Authentication
     */
    //=========================================================================
    //=========================================================================
    protected function auth_success()
    {
        // Override this method to handle auth success
    }

}
