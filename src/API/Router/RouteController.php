<?php
//*****************************************************************************
//*****************************************************************************
/**
 * Route Controller Class
 *
 * @package		Starlight\API
 * @subpackage	Router
 * @author 		Matt Palermo, Christian J. Clark
 * @copyright	Copyright (c) Swell Development LLC
 * @link		http://www.swelldevelopment.com/
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
    public function filter_before()
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
            $v = call_user_func_array([$this, 'filter_' . $filter], func_get_args());
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
        // Check for the authorization header
        //------------------------------------------------------------
        $headers = \phpOpenFW\Helpers\HTTP::GetAllHeaders();

        //------------------------------------------------------------
        // Check for Authorization Header
        //------------------------------------------------------------
        if (isset($headers['Authorization'])) {
            $jwt = str_replace('Bearer ', '', $headers['Authorization']);
            try {
                $this->authToken = Token::key(API_JWT_KEY)->decode($jwt);
                $this->auth_success();
                return;
            }
            catch (\Exception $e) {
                $this->auth_exception('header', $e);
            }
        }

        //------------------------------------------------------------
        // Check for the jwt in a cookie
        //------------------------------------------------------------
        else if (isset($_COOKIE['jwt'])) {
            try {
                $this->authToken = Token::key(API_JWT_KEY)->decode($_COOKIE['jwt']);
                $this->auth_success();
                return;
            }
            catch (\Exception $e) {
                $this->auth_exception('cookie', $e);
            }
        }

        //------------------------------------------------------------
        // Return Response
        //------------------------------------------------------------
        return $this->not_authenticated();
    }

    //=========================================================================
    //=========================================================================
    /**
     * Not authenticated
     */
    //=========================================================================
    //=========================================================================
    protected function not_authenticated($exit=true)
    {
        if ($exit) {
            print \Responses::NotAuthenticated();
            exit;
        }
        else {
            return \Responses::NotAuthenticated();
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
