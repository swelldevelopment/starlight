<?php
//*****************************************************************************
//*****************************************************************************
/**
 * HTTP Response Class
 *
 * @package		Starlight\Http
 * @subpackage	Response
 * @author 		Matt Palermo, Christian J. Clark
 * @copyright	Copyright (c) Swell Development LLC
 * @link		http://www.swelldevelopment.com/
 **/
//*****************************************************************************
//*****************************************************************************

namespace Starlight\Http\Response;

class Response
{
    protected $content;                         // Content of the response
    protected $type = 'html';                   // Type of response
    protected $code = null;                     // Response code

	//=========================================================================
	//=========================================================================
	// Constructor
	//=========================================================================
	//=========================================================================
    public function __construct() {}

	//=========================================================================
	//=========================================================================
    // Set html content
	//=========================================================================
	//=========================================================================
    public function make($content, $code = null)
    {
        return $this->html($content, $code);
    }

	//=========================================================================
	//=========================================================================
    // Set html content
	//=========================================================================
	//=========================================================================
    public function html($content, $code = null)
    {
        $this->content = $content;
        $this->type = 'html';
        $this->code = $code;
        return $this;
    }

	//=========================================================================
	//=========================================================================
    // Set html content
	//=========================================================================
	//=========================================================================
    public function xml($content, $code = null)
    {
        $this->content = $content;
        $this->type = 'xml';
        $this->code = $code;
        return $this;
    }

	//=========================================================================
	//=========================================================================
    // Set json content
	//=========================================================================
	//=========================================================================
    public function json($content, $code = null)
    {
        $this->code = $code;
        $this->content = json_encode($content);
        if (json_last_error()) {
            if (json_last_error() == JSON_ERROR_UTF8) {
                $content = $this->UTF8_Encode($content);
                $this->content = json_encode($content);
            }
            if (json_last_error()) {
                $this->content = json_encode([
                    'status_code' => 500,
                    'status' => 'json_error',
                    'error' => 1,
                    'error_type' => 'internal',
                    'error_msg' => json_last_error() . ' - ' . json_last_error_msg()
                ]);
            }
        }
        $this->type = 'json';
        return $this;
    }

	//=========================================================================
	//=========================================================================
    // Set any headers needed for response
	//=========================================================================
	//=========================================================================
    public function headers()
    {
        switch ($this->type) {
            case 'html':
                header('Content-Type: text/html');
                break;
            case 'xml':
                header('Content-Type: text/xml');
                break;
            case 'json':
                header('Content-Type: application/json');
                break;
        }
    }

	//=========================================================================
	//=========================================================================
    // Convert to string
	//=========================================================================
	//=========================================================================
    public function __toString()
    {
        $this->headers();
        if ($this->code) {
            http_response_code($this->code);
        }
        return $this->content;
    }

    //=========================================================================
    //=========================================================================
    // Encode String or Array as UTF-8
    //=========================================================================
    //=========================================================================
    protected function UTF8_Encode($in)
    {
        if (is_array($in)) {
            foreach ($in as $key => $value) {
                $in[$key] = $this->UTF8_Encode($value);
            }
        }
        else if (is_string($in)) {
            return mb_convert_encoding($in, 'UTF-8', 'UTF-8');
        }
        return $in;
    }
}
