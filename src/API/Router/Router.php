<?php
//*****************************************************************************
//*****************************************************************************
/**
 * Router Class
 *
 * @package         Starlight\API
 * @subpackage      Request
 * @author          Matt Palermo, Christian J. Clark
 * @copyright       Copyright (c) Swell Development LLC
 * @link            http://www.swelldevelopment.com/
 **/
//*****************************************************************************
//*****************************************************************************

namespace Starlight\API\Router;
use \Starlight\API\Response;
use \Starlight\API\Request;

//*****************************************************************************
/**
 * Router Class
 */
//*****************************************************************************
class Router
{
    //==========================================================================
    //==========================================================================
    // Router Run Method
    //==========================================================================
    //==========================================================================
    public static function Run($base_dir, Array $args=[])
    {
        //----------------------------------------------------------------------
        // Set Document Root
        //----------------------------------------------------------------------
        define('DOC_ROOT', $base_dir);

        //----------------------------------------------------------------------
        // Defaults / Extract Args
        //----------------------------------------------------------------------
        $internal_error_mode = 2;
        $error_handler = false;
        $class_alias = true;
        $config_index = 'config';
        $config_loc = 'globals';
        extract($args);

        //----------------------------------------------------------------------
        // Set Error Mode / Error Handler
        //----------------------------------------------------------------------
        define('STARLIGHT_ERROR_MODE', $internal_error_mode);
        if ($error_handler && is_callable($error_handler)) {
            define('STARLIGHT_ERROR_HANDLER', $error_handler);
        }
        else {
            define('STARLIGHT_ERROR_HANDLER', false);
        }

        //----------------------------------------------------------------------
        // Set Request, Check that it is valid
        //----------------------------------------------------------------------
        $request = Request\Request::instance();
        if (!$request->IsValid()) {
            static::ErrorAndDie(1);
        }

        //----------------------------------------------------------------------
        // API JWT Key / API URL Base
        //----------------------------------------------------------------------
        $config = false;
        $config_loc_var = ($config_loc == 'session') ? ($_SESSION) : ($GLOBALS);
        if (isset($config_loc_var[$config_index])) {
            $config = $config_loc_var[$config_index];
        }
        if (is_object($config)) {
            if (isset($config->jwt_api_token)) {
                define('API_JWT_KEY', $config->jwt_api_token);
            }
            else {
                static::ErrorAndDie(3);
            }
        }
        else if (is_array($config)) {
            if (array_key_exists('jwt_api_token', $config)) {
                define('API_JWT_KEY', $config['jwt_api_token']);
            }
            else {
                static::ErrorAndDie(3);
            }
        }
        else {
            static::ErrorAndDie(7);
        }

        //----------------------------------------------------------------------
        // Default API Base URL
        //----------------------------------------------------------------------
        if (!defined('API_URL_BASE')) {
            define('API_URL_BASE', '/');
        }

        //----------------------------------------------------------------------
        // Class Aliases
        //----------------------------------------------------------------------
        if ($class_alias) {
            class_alias('\Starlight\API\Response\ResponseJSON', 'ResponseJSON');
            class_alias('\Starlight\API\Response\Responses', 'Responses');
            class_alias('\Starlight\API\Request\Request', 'Request');
        }

        //----------------------------------------------------------------------
        // Get Route Path
        //----------------------------------------------------------------------
        $routePath = $_SERVER["REQUEST_URI"];
        $routePath = '/' . substr($routePath, strlen(API_URL_BASE));
        $routePath = str_replace('//', '/', $routePath);
        $routeParts = explode('/', $routePath);
        define('API_ROUTE_PATH', $routePath);
        if ($routeParts[count($routeParts) - 1] == '') {
            unset($routeParts[count($routeParts) - 1]);
        }

        //----------------------------------------------------------------------
        // API Version
        //----------------------------------------------------------------------
        if (empty($routeParts[1])) {
            static::ErrorAndDie(4);
        }
        define('API_V', $routeParts[1]);
        if (!isset($routeParts[2])) {
            $routeParts[2] = '';
        }

        //----------------------------------------------------------------------
        // Build API Resource
        //----------------------------------------------------------------------
        $resource_parts = $routeParts;
        if (isset($resource_parts[0])) {
            unset($resource_parts[0]);
        }
        if (isset($resource_parts[1])) {
            unset($resource_parts[1]);
        }
        if (count($resource_parts) > 1) {
            array_pop($resource_parts);
        }
        while (!defined('API_RESOURCE')) {
            $api_resource = implode('/', $resource_parts);
            $api_resource_routes = $base_dir . '/' . API_V . '/' . $api_resource . '/routes.php';
            if (file_exists($api_resource_routes)) {
                define('API_RESOURCE', $api_resource);
            }
            else if (!count($resource_parts)) {
                static::ErrorAndDie(5);
            }
            else {
                array_pop($resource_parts);
            }
        }

        //----------------------------------------------------------------------
        // Allow API calls (CORS)
        //----------------------------------------------------------------------
        static::CORS();

        //----------------------------------------------------------------------
        // Autoload Starlight API classes
        //----------------------------------------------------------------------
        spl_autoload_register('\\Starlight\\API\\Router\\Router::AutoLoad');

        //----------------------------------------------------------------------
        // Initialize the Router
        //----------------------------------------------------------------------
        \Starlight\Http\Router\RouterFactory::init(null, API_URL_BASE . '/' . API_V . '/' . API_RESOURCE);

        //----------------------------------------------------------------------
        // Load the routes
        //----------------------------------------------------------------------
        $resourceRoutes = $base_dir . '/' . API_V . '/' . API_RESOURCE . '/routes.php';
        if (file_exists($resourceRoutes)) {
            require($resourceRoutes);
        }
        //----------------------------------------------------------------------
        // 404 - Endpoint Not Found
        //----------------------------------------------------------------------
        else {
            static::ErrorAndDie(6);
        }

        //----------------------------------------------------------------------
        // Dispatch and show response
        //----------------------------------------------------------------------
        try {
            print \Route::dispatch();
        }
        //----------------------------------------------------------------------
        // Handled Exceptions
        //----------------------------------------------------------------------
        catch (\Exception $e) {
            static::HandleDispatchException('Unhandled Exception', $e);
        }
        //----------------------------------------------------------------------
        // Handle Type Errors
        //----------------------------------------------------------------------
        catch (\TypeError $e) {
            static::HandleDispatchException('Type Error', $e);
        }
    }

    //==========================================================================
    //==========================================================================
    // AutoLoad Method
    //==========================================================================
    //==========================================================================
    public static function AutoLoad($class)
    {
        //----------------------------------------------------------------------
        // Create a hash
        // Adjust for Namespaces
        //----------------------------------------------------------------------
        $class_hash = md5($class);
        $class = str_replace('\\', '/', $class);

        //----------------------------------------------------------------------
        // Only checking for api based classes here
        //----------------------------------------------------------------------
        if (strpos($class, API_V . '/') !== 0) {
            return false;
        }

        //----------------------------------------------------------------------
        // Attempt to locate and load
        //----------------------------------------------------------------------
        $class_file = DOC_ROOT . '/' . $class . '.php';
        if (file_exists($class_file)) {
            require_once($class_file);
            return true;
        }

        //----------------------------------------------------------------------
        // Not Found
        //----------------------------------------------------------------------
        return false;
    }

    //==========================================================================
    //==========================================================================
    // Cross Origin Resource Sharing (CORS) Method
    //==========================================================================
    //==========================================================================
    public static function CORS(Array $args=[])
    {
        //----------------------------------------------------------------------
        // HTTP Origin
        //----------------------------------------------------------------------
        $http_origin = false;
        if (isset($args['http_origin'])) {
            $http_origin = $args['http_origin'];
        }
        else if (isset($_SERVER['HTTP_ORIGIN'])) {
            $http_origin = $_SERVER['HTTP_ORIGIN'];
        }

        //----------------------------------------------------------------------
        // Origin based access (Secure)
        //----------------------------------------------------------------------
        if ($http_origin) {

            //==================================================================
            // Decide if the origin in $_SERVER['HTTP_ORIGIN'] 
            // is one you want to allow, and if so:
            //==================================================================
            header("Access-Control-Allow-Origin: {$http_origin}");
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 1000');
            header('Access-Control-Expose-Headers: x-jwt');

            //==================================================================
            // Access-Control headers are received during OPTIONS requests
            //==================================================================
            if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

                if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
                    header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
                }

                if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
                    header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
                }

                exit(0);
            }
        }
        //----------------------------------------------------------------------
        // Wildcard access (INSECURE!!!)
        //----------------------------------------------------------------------
        else {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Expose-Headers: x-jwt');
            if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
                header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
                exit(0);
            }
        }
    }

    //%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    //%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    // Internal Methods
    //%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    //%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%

    //==========================================================================
    //==========================================================================
    // Handle Dispatch Exception
    //==========================================================================
    //==========================================================================
    protected static function HandleDispatchException($error_type, $e)
    {
        //----------------------------------------------------------------------
        // Is the error handler set?
        //----------------------------------------------------------------------
        if (STARLIGHT_ERROR_HANDLER && STARLIGHT_ERROR_MODE < 3) {
            call_user_func(STARLIGHT_ERROR_HANDLER,
                $e->getCode(),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                ['type' => $error_type]
            );
        }

        //----------------------------------------------------------------------
        // Mode 1: Return 500 with generic error message (default)
        //----------------------------------------------------------------------
        if (STARLIGHT_ERROR_MODE == 1) {
            static::ErrorAndDie(100, 'An internal error occurred.');
        }
        //----------------------------------------------------------------------
        // Mode 2: Return 500 with specific error message
        //----------------------------------------------------------------------
        else if (STARLIGHT_ERROR_MODE == 2) {
            static::ErrorAndDie(100, $error_type . ': ' . $e->getMessage(), $e);
        }
        //----------------------------------------------------------------------
        // Mode 3: Debug
        //----------------------------------------------------------------------
        else if (STARLIGHT_ERROR_MODE == 3) {
            $error_msg = $error_type . ': ' . $e->getMessage();
            $error_msg .= ' @ ' . $e->getFile() . ':' . $e->getLine();
            if (ini_get('display_errors')) {
                trigger_error($error_msg);
            }
            else {
                print $error_msg . "\n\n";
            }
            if (isset($e->xdebug_message)) {
                print $e->xdebug_message;
            }
            else {
                print_r($e->getTrace());
            }
        }
    }

    //==========================================================================
    //==========================================================================
    // Display Error (JSON) and Die
    //==========================================================================
    //==========================================================================
    protected static function ErrorAndDie($sub_error, $error_msg=false)
    {
        //----------------------------------------------------------------------
        // Determine Error
        //----------------------------------------------------------------------
        switch ($sub_error) {

            //------------------------------------------------------------------
            // Invalid Content Type
            //------------------------------------------------------------------
            case 1:
                Response\ResponseJSON::RequestError([
                    'message' => 'Invalid request content type or malformed JSON data.',
                    'status_code' => $sub_error
                ]);
                break;

            //------------------------------------------------------------------
            // Invalid JSON Data Sent
            //------------------------------------------------------------------
            case 2:
                Response\ResponseJSON::RequestError([
                    'message' => 'Invalid JSON request data sent.',
                    'status_code' => $sub_error
                ]);
                break;

            //------------------------------------------------------------------
            // API JWT Token not defined
            //------------------------------------------------------------------
            case 3:
                Response\ResponseJSON::RequestError([
                    'message' => 'API_JWT_KEY not defined in configuration.',
                    'status_code' => $sub_error
                ]);
                break;

            //------------------------------------------------------------------
            // 4: Invalid API Version
            //------------------------------------------------------------------
            case 4:
                Response\ResponseJSON::RequestError([
                    'message' => 'Invalid API URL.',
                    'status_code' => $sub_error
                ]);
                break;

            //------------------------------------------------------------------
            // 5 / 6: Invalid Router Path
            //------------------------------------------------------------------
            case 5:
            case 6:
                Response\Responses::NotFound([
                    'status_code' => $sub_error
                ]);
                break;

            //------------------------------------------------------------------
            // Configuration Not Found
            //------------------------------------------------------------------
            case 7:
                Response\ResponseJSON::RequestError([
                    'message' => 'Configuration Not Found.',
                    'status_code' => $sub_error
                ]);
                break;

            //------------------------------------------------------------------
            // Internal Error
            //------------------------------------------------------------------
            case 100:
                Response\ResponseJSON::InternalError([
                    'message' => $error_msg,
                    'status_code' => $sub_error
                ]);
                break;

        }

        //----------------------------------------------------------------------
        // Exit
        //----------------------------------------------------------------------
        exit;
    }

}
