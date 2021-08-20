<?php
//*****************************************************************************
//*****************************************************************************
/**
 * Response Class
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

class Responses
{

    //=========================================================================
    //=========================================================================
    // 200 - Success Response
    //=========================================================================
    //=========================================================================
    public static function Success(Array $payload=[])
    {
        return ResponseJSON::Success($payload);
    }

    //=========================================================================
    //=========================================================================
    // 200 - No Data Response
    //=========================================================================
    //=========================================================================
    public static function NoData(Array $args=[])
    {
        $args['status'] = 'no_data';
        return ResponseJSON::Success($args);
    }

    //=========================================================================
    //=========================================================================
    // 308 - Permanent Redirect Response
    //=========================================================================
    //=========================================================================
    public static function PermanentRedirect(Array $args=[])
    {
        $args['status'] = 'permanent_redirect';
        return ResponseJSON::Redirect($args, 308);
    }

    //=========================================================================
    //=========================================================================
    // 307 - Temporary Redirect Response
    //=========================================================================
    //=========================================================================
    public static function TemporaryRedirect(Array $args=[])
    {
        $args['status'] = 'temporary_redirect';
        return ResponseJSON::Redirect($args, 307);
    }

    //=========================================================================
    //=========================================================================
    // 303 - See Other Response
    //=========================================================================
    //=========================================================================
    public static function SeeOther(Array $args=[])
    {
        $args['status'] = 'see_other';
        return ResponseJSON::Redirect($args, 303);
    }

    //=========================================================================
    //=========================================================================
    // 302 - Found Response
    //=========================================================================
    //=========================================================================
    public static function Found(Array $args=[])
    {
        $args['status'] = 'found';
        return ResponseJSON::Redirect($args, 302);
    }

    //=========================================================================
    //=========================================================================
    // 301 - Moved Permanently Response
    //=========================================================================
    //=========================================================================
    public static function MovedPermanently(Array $args=[])
    {
        $args['status'] = 'moved_permanently';
        return ResponseJSON::Redirect($args, 301);
    }

    //=========================================================================
    //=========================================================================
    // 400 - Bad Request Response
    //=========================================================================
    //=========================================================================
    public static function BadRequest(Array $args=[])
    {
        if (!isset($args['status'])) {
            $args['status'] = 'bad_request';
        }
        return ResponseJSON::RequestError($args);
    }

    //=========================================================================
    //=========================================================================
    // 400 - Missing Parameters Response
    //=========================================================================
    //=========================================================================
    public static function MissingParameters(Array $args=[])
    {
        $args['status'] = 'missing_parameters';
        return ResponseJSON::RequestError($args);
    }

    //=========================================================================
    //=========================================================================
    // 400 - Failed Validation Response
    //=========================================================================
    //=========================================================================
    public static function FailedValidation(Array $args=[])
    {
        $args['status'] = 'failed_validation';
        return ResponseJSON::RequestError($args);
    }

    //=========================================================================
    //=========================================================================
    // 401 - Failed Authentication Response
    //=========================================================================
    //=========================================================================
    public static function FailedAuthentication(Array $args=[])
    {
        $args['status'] = 'failed_authentication';
        return ResponseJSON::AccessError($args);
    }

    //=========================================================================
    //=========================================================================
    // 401 - Not Authenticated Response
    //=========================================================================
    //=========================================================================
    public static function NotAuthenticated(Array $args=[])
    {
        $args['status'] = 'not_authenticated';
        return ResponseJSON::AccessError($args);
    }

    //=========================================================================
    //=========================================================================
    // 403 - Forbidden Response
    //=========================================================================
    //=========================================================================
    public static function Forbidden(Array $args=[])
    {
        if (!isset($args['status'])) {
            $args['status'] = 'forbidden';
        }
        return ResponseJSON::AccessError($args, 403);
    }

    //=========================================================================
    //=========================================================================
    // 404 - Not Found Response
    //=========================================================================
    //=========================================================================
    public static function NotFound(Array $args=[])
    {
        $args['status'] = 'not_found';
        return ResponseJSON::AccessError($args, 404);
    }

    //=========================================================================
    //=========================================================================
    // 404 - Data Not Found Response
    //=========================================================================
    //=========================================================================
    public static function DataNotFound(Array $args=[])
    {
        $args['status'] = 'data_not_found';
        return ResponseJSON::NotFoundError($args, 404);
    }

    //=========================================================================
    //=========================================================================
    // 500 - Internal Error Response
    //=========================================================================
    //=========================================================================
    public static function InternalError(Array $args=[])
    {
        return ResponseJSON::InternalError($args);
    }

    //=========================================================================
    //=========================================================================
    // 500 - Invalid Method Parameters Response
    //=========================================================================
    //=========================================================================
    public static function InvalidMethodParamentersError(Array $args=[])
    {
        $args['status'] = 'invalid_method_parameters';
        return ResponseJSON::InternalError($args);
    }

}
