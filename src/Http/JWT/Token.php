<?php
//*****************************************************************************
//*****************************************************************************
/**
 * JWT Token Class
 *
 * @package         Starlight\Http
 * @subpackage      Router
 * @author          Matt Palermo, Christian J. Clark
 * @copyright       Copyright (c) Swell Development LLC
 * @link            http://www.swelldevelopment.com/
 **/
//*****************************************************************************
//*****************************************************************************

namespace Starlight\Http\JWT;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Token
{
    protected static $leeway = 60;              // Adjust for timing between client and server
    protected static $key = null;               // Secret key for signing tokens

    //=========================================================================
    //=========================================================================
    // Set the key
    //=========================================================================
    //=========================================================================
    public static function key($key)
    {
        static::$key = $key;
        return new static();
    }

    //=========================================================================
    //=========================================================================
    // Generate an access token
    //=========================================================================
    //=========================================================================
    public static function AccessToken($data=[], $ttl=60, $keyAppend=null)
    {
        return static::encode($data, $ttl, $keyAppend);
    }

    //=========================================================================
    //=========================================================================
    // Generate a refresh token
    //=========================================================================
    //=========================================================================
    public static function RefreshToken($data=[], $ttl=null, $keyAppend=null)
    {
        if (!$ttl) { $ttl = (86400 * 7); }
        return static::encode($data, $ttl, $keyAppend);
    }

    //=========================================================================
    //=========================================================================
    // Generic token encode
    //=========================================================================
    //=========================================================================
    public static function encode($data=[], $ttl=null, $keyAppend=null)
    {
        $now = time();
        $ttl = ($ttl) ? ($ttl) : (ini_get("session.gc_maxlifetime"));

        $token = [
            'iss' => 'api-backend',
            'sub' => '',
            'aud' => 'api-frontend',
            'iat' => $now,
            //'nbf' => $now + 10,
            'exp' => $now + $ttl,
            'data' => $data
        ];

        JWT::$leeway = static::$leeway;
        $key = static::$key;
        if ($keyAppend) {
            $key .= $keyAppend;
        }
        $algo = 'HS256';
        return JWT::encode($token, $key, $algo);
    }

    //=========================================================================
    //=========================================================================
    // Decode a token
    //=========================================================================
    //=========================================================================
    public static function decode($jwt, $keyAppend=null)
    {
        if (!static::$key) {
            throw new \Exception('No token signing key provided.');
        }

        $key = static::$key;
        if ($keyAppend) {
            $key .= $keyAppend;
        }
        $algo = 'HS256';
        $headers = new \stdClass();
        return JWT::decode($jwt, new Key($key, $algo), $headers);
    }
}
