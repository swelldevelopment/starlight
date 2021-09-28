<?php
//*****************************************************************************
//*****************************************************************************
/**
 * JSON Response Class
 *
 * @package         Starlight\Http
 * @subpackage      Router
 * @author          Christian J. Clark
 * @copyright       Copyright (c) Swell Development LLC
 * @link            http://www.swelldevelopment.com/
 **/
//*****************************************************************************
//*****************************************************************************

namespace Starlight\API\Response;

class ResponseJSON
{

    //=========================================================================
    //=========================================================================
    // Internal Error Response
    //=========================================================================
    //=========================================================================
    public static function InternalError(Array $payload=[], $http_code=500)
    {
        //=================================================================
        // Parameter Validation / Defaults
        //=================================================================
        $payload['error_type'] = 'internal';
        if (!$http_code || !is_int($http_code)) {
            $http_code = 500;
        }
        if (!array_key_exists('status', $payload) || empty($payload['status'])) {
            $payload['status'] = 'internal_error';
        }

        //=================================================================
        // Output Error in JSON and exit
        //=================================================================
        self::Response($http_code, 1, $payload);
    }

    //=========================================================================
    //=========================================================================
    // Request Error Response
    //=========================================================================
    //=========================================================================
    public static function RequestError(Array $payload=[], $http_code=400)
    {
        //=================================================================
        // Parameter Validation / Defaults
        //=================================================================
        $payload['error_type'] = 'request';
        if (empty($http_code) || !is_int($http_code)) {
            $http_code = 400;
        }
        if (!array_key_exists('status', $payload) || empty($payload['status'])) {
            $payload['status'] = 'request_error';
        }

        //=================================================================
        // Output Response
        //=================================================================
        self::Response($http_code, 1, $payload);
    }

    //=========================================================================
    //=========================================================================
    // Access Error Response
    //=========================================================================
    //=========================================================================
    public static function AccessError(Array $payload=[], $http_code=401)
    {
        //=================================================================
        // Parameter Validation / Defaults
        //=================================================================
        $payload['error_type'] = 'access';
        if (empty($http_code) || !is_int($http_code)) {
            $http_code = 401;
        }
        if (!array_key_exists('status', $payload) || empty($payload['status'])) {
            $payload['status'] = 'access_error';
        }

        //=================================================================
        // Output Response
        //=================================================================
        self::Response($http_code, 1, $payload);
    }

    //=========================================================================
    //=========================================================================
    // Not Found Error Response
    //=========================================================================
    //=========================================================================
    public static function NotFoundError(Array $payload=[], $http_code=404)
    {
        //=================================================================
        // Parameter Validation / Defaults
        //=================================================================
        $payload['error_type'] = 'not_found';
        if (empty($http_code) || !is_int($http_code)) {
            $http_code = 404;
        }
        if (!array_key_exists('status', $payload) || empty($payload['status'])) {
            $payload['status'] = 'not_found';
        }

        //=================================================================
        // Output Response
        //=================================================================
        self::Response($http_code, 1, $payload);
    }

    //=========================================================================
    //=========================================================================
    // Redirect Response
    //=========================================================================
    //=========================================================================
    public static function Redirect(Array $payload=[], $http_code=307)
    {
        //=================================================================
        // Parameter Validation / Defaults
        //=================================================================
        if (empty($http_code) || !is_int($http_code)) {
            $http_code = 307;
        }
        if (!array_key_exists('status', $payload) || empty($payload['status'])) {
            $payload['status'] = 'temporary_redirect';
            $http_code = 307;
        }
        if (!array_key_exists('uri', $payload) || empty($payload['uri'])) {
            $payload['uri'] = '/';
        }

        //=================================================================
        // Output Response
        //=================================================================
        self::Response($http_code, 1, $payload);
    }

    //=========================================================================
    //=========================================================================
    // Success Response
    //=========================================================================
    //=========================================================================
    public static function Success(Array $payload=[], $http_code=200)
    {
        //=================================================================
        // Parameter Validation / Defaults
        //=================================================================
        if (empty($http_code) || !is_int($http_code)) {
            $http_code = 200;
        }

        //=================================================================
        // Output Response
        //=================================================================
        self::Response($http_code, 0, $payload);
    }

    //=========================================================================
    //=========================================================================
    // Response
    //=========================================================================
    //=========================================================================
    public static function Response($http_code, $error, Array $payload=[], Array $args=[])
    {
        //=================================================================
        // Defaults / Extract Args
        //=================================================================
        $return = false;
        $status = 'success';
        $status_code = 0;
        $error_type = false;
        $exit = false;
        extract($args);

        //=================================================================
        // Status, Status Code, Error Type
        //=================================================================
        if (array_key_exists('status_code', $payload) && !empty($payload['status_code'])) {
            $status_code = $payload['status_code'];
            unset($payload['status_code']);
        }
        if (array_key_exists('status', $payload) && !empty($payload['status'])) {
            $status = $payload['status'];
            unset($payload['status']);
        }
        if (array_key_exists('error_type', $payload) && !empty($payload['error_type'])) {
            $error_type = $payload['error_type'];
            unset($payload['error_type']);
        }
        if (array_key_exists('exit', $payload)) {
            $exit = $payload['exit'];
            unset($payload['exit']);
        }

        //=================================================================
        // Format Response
        //=================================================================
        $response = [
            'http_code' => $http_code,
            'error' => $error,
            'status' => $status,
            'status_code' => $status_code
        ];
        if ($error && $error_type) {
            $response['error_type'] = $error_type;
        }
        $response['payload'] = $payload;

        //=================================================================
        // Output Response
        //=================================================================
        $response = \Starlight\Http\Response\ResponseFactory::json($response, $http_code);

        //=================================================================
        // Return or Print Response?
        //=================================================================
        if ($return && !$exit) {
            return $response;
        }
        else {
            print $response;
            if ($exit) {
                exit;
            }
        }
    }

}
