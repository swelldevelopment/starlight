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
        if (!isset($args['status'])) {
            $args['status'] = 'no_data';
        }
        return ResponseJSON::Success($args);
    }

    //=========================================================================
    //=========================================================================
    // 201 - Created
    //=========================================================================
    //=========================================================================
    public static function Created(Array $args=[])
    {
        if (!isset($args['status'])) {
            $args['status'] = 'created';
        }
        return ResponseJSON::Success($args, 201);
    }

    //=========================================================================
    //=========================================================================
    // 202 - Accepted
    //=========================================================================
    //=========================================================================
    public static function Accepted(Array $args=[])
    {
        if (!isset($args['status'])) {
            $args['status'] = 'accepted';
        }
        return ResponseJSON::Success($args, 202);
    }

    //=========================================================================
    //=========================================================================
    // 204 - No Content
    //=========================================================================
    //=========================================================================
    public static function NoContent(Array $args=[])
    {
        if (!isset($args['status'])) {
            $args['status'] = 'no-content';
        }
        return ResponseJSON::Success($args, 204);
    }

    //=========================================================================
    //=========================================================================
    // 205 - Reset Content
    //=========================================================================
    //=========================================================================
    public static function ResetContent(Array $args=[])
    {
        if (!isset($args['status'])) {
            $args['status'] = 'reset-content';
        }
        return ResponseJSON::Success($args, 205);
    }

    //=========================================================================
    //=========================================================================
    // 308 - Permanent Redirect Response
    //=========================================================================
    //=========================================================================
    public static function PermanentRedirect(Array $args=[])
    {
        if (!isset($args['status'])) {
            $args['status'] = 'permanent_redirect';
        }
        return ResponseJSON::Redirect($args, 308);
    }

    //=========================================================================
    //=========================================================================
    // 307 - Temporary Redirect Response
    //=========================================================================
    //=========================================================================
    public static function TemporaryRedirect(Array $args=[])
    {
        if (!isset($args['status'])) {
            $args['status'] = 'temporary_redirect';
        }
        return ResponseJSON::Redirect($args, 307);
    }

    //=========================================================================
    //=========================================================================
    // 303 - See Other Response
    //=========================================================================
    //=========================================================================
    public static function SeeOther(Array $args=[])
    {
        if (!isset($args['status'])) {
            $args['status'] = 'see_other';
        }
        return ResponseJSON::Redirect($args, 303);
    }

    //=========================================================================
    //=========================================================================
    // 302 - Found Response
    //=========================================================================
    //=========================================================================
    public static function Found(Array $args=[])
    {
        if (!isset($args['status'])) {
            $args['status'] = 'found';
        }
        return ResponseJSON::Redirect($args, 302);
    }

    //=========================================================================
    //=========================================================================
    // 301 - Moved Permanently Response
    //=========================================================================
    //=========================================================================
    public static function MovedPermanently(Array $args=[])
    {
        if (!isset($args['status'])) {
            $args['status'] = 'moved_permanently';
        }
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
        if (!isset($args['status'])) {
            $args['status'] = 'missing_parameters';
        }
        return ResponseJSON::RequestError($args);
    }

    //=========================================================================
    //=========================================================================
    // 400 - Failed Validation Response
    //=========================================================================
    //=========================================================================
    public static function FailedValidation(Array $args=[])
    {
        if (!isset($args['status'])) {
            $args['status'] = 'failed_validation';
        }
        return ResponseJSON::RequestError($args);
    }

    //=========================================================================
    //=========================================================================
    // 401 - Failed Authentication Response
    //=========================================================================
    //=========================================================================
    public static function FailedAuthentication(Array $args=[])
    {
        if (!isset($args['status'])) {
            $args['status'] = 'failed_authentication';
        }
        return ResponseJSON::AccessError($args);
    }

    //=========================================================================
    //=========================================================================
    // 401 - Not Authenticated Response
    //=========================================================================
    //=========================================================================
    public static function NotAuthenticated(Array $args=[])
    {
        if (!isset($args['status'])) {
            $args['status'] = 'not_authenticated';
        }
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
        if (!isset($args['status'])) {
            $args['status'] = 'not_found';
        }
        return ResponseJSON::AccessError($args, 404);
    }

    //=========================================================================
    //=========================================================================
    // 404 - Data Not Found Response
    //=========================================================================
    //=========================================================================
    public static function DataNotFound(Array $args=[])
    {
        if (!isset($args['status'])) {
            $args['status'] = 'data_not_found';
        }
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
        if (!isset($args['status'])) {
            $args['status'] = 'invalid_method_parameters';
        }
        return ResponseJSON::InternalError($args);
    }

}
