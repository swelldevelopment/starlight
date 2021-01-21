<?php
//*****************************************************************************
//*****************************************************************************
/**
 * Router Class
 *
 * @package		Starlight\API
 * @subpackage	Request
 * @author 		Matt Palermo, Christian J. Clark
 * @copyright	Copyright (c) Swell Development LLC
 * @link		http://www.swelldevelopment.com/
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

	//=========================================================================
	//=========================================================================
	// Router Run Method
	//=========================================================================
	//=========================================================================
	public static function Run($base_dir, Array $args=[])
	{
    	$class_alias = true;
		define('DOC_ROOT', $base_dir);
		$config_index = 'config';
		$config_loc = 'globals';
		extract($args);
        $request = Request\Request::instance();

		//*********************************************************
        // Check Content Type
		//*********************************************************
        if (!$request->IsValid()) {
            self::ErrorAndDie(1);
        }

		//*********************************************************
		// API JWT Key / API URL Base
		//*********************************************************
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
				self::ErrorAndDie(3);
			}
		}
		else if (is_array($config)) {
			if (array_key_exists('jwt_api_token', $config)) {
				define('API_JWT_KEY', $config['jwt_api_token']);
			}
			else {
				self::ErrorAndDie(3);
			}
		}
		else {
			self::ErrorAndDie(7);
		}

		//*********************************************************
		// Default API Base URL
		//*********************************************************
		if (!defined('API_URL_BASE')) {
			define('API_URL_BASE', '/');
		}

        //*********************************************************
        // Class Alias
        //*********************************************************
        if ($class_alias) {
            class_alias('\Starlight\API\Response\ResponseJSON', 'ResponseJSON');
            class_alias('\Starlight\API\Response\Responses', 'Responses');
            class_alias('\Starlight\API\Request\Request', 'Request');
        }

		//*********************************************************
		// Get Route Path
		//*********************************************************
		$routePath = $_SERVER["REQUEST_URI"];
		$routePath = '/' . substr($routePath, strlen(API_URL_BASE));
		$routePath = str_replace('//', '/', $routePath);
		$routeParts = explode('/', $routePath);
		define('API_ROUTE_PATH', $routePath);
		if ($routeParts[count($routeParts) - 1] == '') {
    		unset($routeParts[count($routeParts) - 1]);
		}

		//*********************************************************
        // API Version
		//*********************************************************
		if (empty($routeParts[1])) {
			self::ErrorAndDie(4);
		}
		define('API_V', $routeParts[1]);
		if (!isset($routeParts[2])) {
			$routeParts[2] = '';
		}

		//*********************************************************
        // Build API Resource
		//*********************************************************
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
                self::ErrorAndDie(5);
            }
            else {
                array_pop($resource_parts);
            }
        }

		//*********************************************************
		// Allow API calls (CORS)
		//*********************************************************
		self::CORS();

		//*********************************************************
		// Autoload api classes
		//*********************************************************
		spl_autoload_register('\\Starlight\\API\\Router\\Router::AutoLoad');

		//*********************************************************
		// Initialize the Router
		//*********************************************************
		\Starlight\Http\Router\RouterFactory::init(null, API_URL_BASE . '/' . API_V . '/' . API_RESOURCE);

		//*********************************************************
		// Load the routes
		//*********************************************************
		$resourceRoutes = $base_dir . '/' . API_V . '/' . API_RESOURCE . '/routes.php';
		if (file_exists($resourceRoutes)) {
			require($resourceRoutes);
		}
		//*********************************************************
        // 404 - Endpoint Not Found
		//*********************************************************
		else {
			self::ErrorAndDie(6);
		}

		//*********************************************************
		// Dispatch and show response
		//*********************************************************
		try {
		    print \Route::dispatch();
		}
		catch (\Exception $e) {
		    self::ErrorAndDie(100, $e->getMessage());
		}
	}

	//=========================================================================
	//=========================================================================
	// Cross Origin Resource Sharing (CORS) Method
	//=========================================================================
	//=========================================================================
	protected static function CORS(Array $args=[])
	{
		//*********************************************************
		// Allow API calls (CORS)
		//*********************************************************
		if (isset($_SERVER['HTTP_ORIGIN'])) {

			//===================================================================
		    // Decide if the origin in $_SERVER['HTTP_ORIGIN'] 
		    // is one you want to allow, and if so:
			//===================================================================
		    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
		    header('Access-Control-Allow-Credentials: true');
		    header('Access-Control-Max-Age: 1000');
		    header('Access-Control-Expose-Headers: x-jwt');

			//===================================================================
		    // Access-Control headers are received during OPTIONS requests
			//===================================================================
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
		else {
		    header('Access-Control-Allow-Origin: *');
		    header('Access-Control-Expose-Headers: x-jwt');
		    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    		    header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
		        exit(0);
            }
		}
	}

	//=========================================================================
	//=========================================================================
	// AutoLoad Method
	//=========================================================================
	//=========================================================================
	public static function AutoLoad($class)
	{
	    //=================================================================
	    // Create a hash
	    // Adjust for Namespaces
	    //=================================================================
	    $class_hash = md5($class);
	    $class = str_replace('\\', '/', $class);

	    //=================================================================
	    // Only checking for api based classes here
	    //=================================================================
	    if (strpos($class, API_V . '/') !== 0) {
	        return false;
	    }

	    //=================================================================
	    // Attempt to locate and load
	    //=================================================================
	    $class_file = DOC_ROOT . '/' . $class . '.php';
	    if (file_exists($class_file)) {
	        require_once($class_file);
	        return true;
	    }

	    //=================================================================
	    // Not Found
	    //=================================================================
	    return false;
	}

	//=========================================================================
	//=========================================================================
	// Display Error (JSON) and Die
	//=========================================================================
	//=========================================================================
	protected static function ErrorAndDie($sub_error, $error_msg=false)
	{
    	//=================================================================
    	// Determine Error
    	//=================================================================
    	switch ($sub_error) {

            //-------------------------------------------------------------
            // Invalid Content Type
            //-------------------------------------------------------------
            case 1:
                Response\ResponseJSON::RequestError([
                    'message' => 'Invalid request content type or malformed JSON data.',
                    'status_code' => $sub_error
                ]);
                break;

            //-------------------------------------------------------------
            // Invalid JSON Data Sent
            //-------------------------------------------------------------
            case 2:
                Response\ResponseJSON::RequestError([
                    'message' => 'Invalid JSON request data sent.',
                    'status_code' => $sub_error
                ]);
                break;

            //-------------------------------------------------------------
            // API JWT Token not defined
            //-------------------------------------------------------------
            case 3:
                Response\ResponseJSON::RequestError([
                    'message' => 'API_JWT_KEY not defined in configuration.',
                    'status_code' => $sub_error
                ]);
                break;

            //-------------------------------------------------------------
            // 4: Invalid API Version
            //-------------------------------------------------------------
            case 4:
                Response\ResponseJSON::RequestError([
                    'message' => 'Invalid API URL.',
                    'status_code' => $sub_error
                ]);
                break;

            //-------------------------------------------------------------
            // 5 / 6: Invalid Router Path
            //-------------------------------------------------------------
            case 5:
            case 6:
                Response\Responses::NotFound([
                    'status_code' => $sub_error
                ]);
                break;

			//-------------------------------------------------------------
			// Configuration Not Found
			//-------------------------------------------------------------
			case 7:
				Response\ResponseJSON::RequestError([
					'message' => 'Configuration Not Found.',
					'status_code' => $sub_error
				]);
				break;

    	}

    	//=================================================================
        // Exit
    	//=================================================================
        exit;
    }

}
