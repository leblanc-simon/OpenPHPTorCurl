<?php
# This program is free software. It comes without any warranty, to
# the extent permitted by applicable law. You can redistribute it
# and/or modify it under the terms of the Do What The Fuck You Want
# To Public License, Version 2, as published by Sam Hocevar. See
# http://sam.zoy.org/wtfpl/COPYING for more details.

namespace OpenPHPTorCurl;

/**
 * Class to call an URL via Tor
 *
 * Example :
 * $browser = new OpenPHPTorCurl\Browser();
 * $browser->setUrl('http://www.example.com')
 *         ->add('first_param', 'value')
 *         ->addFile('file', '/home/my-file.txt')
 *         ->setUserAgent('firefox');
 * if ($browser->post() === false) {
 *   $browser->getError();
 * } else {
 *   $browser->getStatusCode();
 *   $browser->getHeaders();
 *   $browser->getContent();
 * }
 *
 * !!! CAUTION !!!
 * Tor must run when you call script
 * This script doesn't check if Tor is running
 * !!! CAUTION !!!
 *
 *
 * @author  Simon Leblanc <contact@leblanc-simon.eu>
 * @license http://sam.zoy.org/wtfpl/COPYING    WTFPL
 * @package OpenPHPTorCurl
 *
 * @throws  OpenPHPTorCurl\Exception   1  : proxy not defined
 * @throws  OpenPHPTorCurl\Exception   2  : host proxy must be a http string
 * @throws  OpenPHPTorCurl\Exception   3  : port proxy must an integer
 * @throws  OpenPHPTorCurl\Exception   4  : URL must be http, https, ftp or ftps
 * @throws  OpenPHPTorCurl\Exception   5  : Bad parameter
 * @throws  OpenPHPTorCurl\Exception   6  : Bad parameter
 * @throws  OpenPHPTorCurl\Exception   7  : File doesn't exist
 */
class Browser
{
    static private $proxy = null;
    static private $check_url = 'https://check.torproject.org/';
    static private $user_agent = array(
      'default' => 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64) OpenPHPTorCurl/1.0.0.0',
      'firefox' => 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:14.0) Gecko/20100101 Firefox/14.0.1',
      'chrome'  => 'Mozilla/5.0 (X11; U; Linux x86_64; en-US) AppleWebKit/532.0 (KHTML, like Gecko) Chrome/4.0.202.0 Safari/532.0',
      'opera'   => 'Opera/9.80 (X11; Linux x86_64; U; Ubuntu; fr) Presto/2.10.289 Version/12.01',
      'ie'      => 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.0; Trident/5.0)',
      'iphone'  => 'Mozilla/5.0 (iPhone; U; CPU like Mac OS X; en) AppleWebKit/420+ (KHTML, like Gecko) Version/3.0 Mobile/1A543 Safari/419.3',
      'android' => 'Mozilla/5.0 (Linux; U; Android 2.3.3; fr-fr; GT-I9100 Build/GINGERBREAD) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1',
    );
    
    private $curl   = null;
    private $url    = null;
    private $datas  = array();
    private $result = false;
    
    private $headers        = null;
    private $response       = null;
    private $status_code    = null;
    private $error          = array();
    
    private $http_method = 'GET';
    
    private $use_cookies    = false;
    private $cookies_file   = null;
    private $cookies_datas  = array();
    
    private $use_user_agent = 'default';
    
    
    /**
     * Init proxy and url
     *
     * @param   string  $url    The URL to call
     * @param   string  $proxy  The proxy to call via Tor
     * @param   int     $port   The port to call via Tor
     * @access  public
     */
    public function __construct($url = null, $host = 'http://127.0.0.1', $port = 9050)
    {
        if ($host !== null && $port !== null) {
            self::setProxy($host, $port);
        }
        
        $this->curl = curl_init();
        
        if ($url !== null) {
            $this->setUrl($url);
        }
        
        $this->setUserAgent($this->use_user_agent);
    }
    
    
    /**
     * Destruct data and close curl session
     *
     * @access  public
     */
    public function __destruct()
    {
        curl_close($this->curl);
    }
    
    
    /**
     * Define the url to call
     *
     * @param   string  $url    the url to call
     * @return  OpenPHPTorCurl\Browser
     * @access  public
     */
    public function setUrl($url)
    {
        if (is_string($url) === false || preg_match('~^(f|ht)tps?://(.*)$~', $url) === 0) {
            throw new Exception('URL must be http or ftp', 4);
        }
        
        Debug::info('Set the URL with : '.$url);
        
        $this->url = $url;
        curl_setopt($this->curl, CURLOPT_URL, $url);
        
        return $this;
    }
    
    
    /**
     * Define the user-agent to use
     *
     * @param   string  $user_agent     The user-agent to usewhen call URL
     * @return  OpenPHPTorCurl\Browser
     * @access  public
     */
    public function setUserAgent($user_agent)
    {
        if (isset(self::$user_agent[$user_agent]) === true) {
            $this->use_user_agent = self::$user_agent[$user_agent];
        } else {
            $this->use_user_agent = $user_agent;
        }
        
        return $this;
    }
    
    
    /**
     * Add a parameter in the query
     *
     * @param   string  $arg    The name of the parameter
     * @param   string  $value  The value of the parameter
     * @return  OpenPHPTorCurl\Browser
     * @access  public
     */
    public function add($arg, $value)
    {
        if (is_string($arg) === false || is_string($value) === false) {
            throw new Exception('arg and value must be string', 5);
        }
        
        $this->datas[$arg] = $value;
        
        return $this;
    }
    
    
    /**
     * Add a file in the query's parameter
     *
     * @param   string  $arg        The name of the parameter
     * @param   string  $filename   The filename to add in the parameter
     * @return  OpenPHPTorCurl\Browser
     * @access  public
     */
    public function addFile($arg, $filename)
    {
        if (is_string($arg) === false || is_string($filename) === false) {
            throw new Exception('arg and filename must be string', 6);
        }
        
        if (is_file($filename) === false) {
            throw new Exception('filename doesn\'t exist', 7);
        }
        
        $this->datas[$arg] = '@'.$filename;
        
        return $this;
    }
    
    
    /**
     * Call an URL with GET HTTP Method
     *
     * @param   string  $url    The URL to call
     * @return  bool            True if the call success, false else
     * @access  public
     */
    public function get($url = null)
    {
        if ($url !== null) {
            $this->setUrl($url);
        }
        
        if (count($this->datas) > 0) {
            $url = $this->url.'?'.http_build_query($this->datas);
            $this->setUrl($url);
        }
        
        $this->http_method = 'GET';
        
        return $this->exec();
    }
    
    
    /**
     * Call an URL with POST HTTP Method
     *
     * @param   string  $url    The URL to call
     * @return  bool            True if the call success, false else
     * @access  public
     */
    public function post($url = null)
    {
        if ($url !== null) {
            $this->setUrl($url);
        }
        
        $this->http_method = 'POST';
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $this->datas);
        
        return $this->exec();
    }
    
    
    /**
     * Check if you are using Tor
     *
     * @return  bool    True if you use Tor, false else
     * @access  public
     * @todo    code method :-)
     */
    public function check()
    {
        
    }
    
    
    /**
     * Return HTTP status code of the response
     *
     * @return  int     HTTP status code
     * @access  public
     */
    public function getStatusCode()
    {
        return $this->status_code;
    }
    
    
    /**
     * Return HTTP header of the response
     *
     * @return  array   HTTP header's response
     * @access  public
     */
    public function getHeaders()
    {
        return $this->headers;
    }
    
    
    /**
     * Return the content of the call
     *
     * @return  string      the content of the call
     * @access  public
     */
    public function getContent()
    {
        return $this->response;
    }
    
    
    /**
     * Return the error
     *
     * @return  null|array<code, error>         null if no error, array with code and error message if error
     * @access  public
     */
    public function getError()
    {
        return is_array($this->error) && count($this->error) === 2 ? $this->error : null;
    }
    
    
    /**
     * Exec a call to URL
     *
     * @return  bool    True if call is success, false else
     * @access  private
     */
    private function exec()
    {
        $this->reset();
        
        // set the option to call URL by Tor
        curl_setopt($this->curl, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
        curl_setopt($this->curl, CURLOPT_PROXY, self::getProxy());
        
        // set the HTTP method
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $this->http_method);
        
        // use cookies
        if ($this->use_cookies === true && $this->cookies_file !== null && is_file($this->cookies_file) === true) {
            Debug::info('Use cookies file : '.$this->cookies_file);
            curl_setopt($this->curl, CURLOPT_COOKIEFILE, $this->cookies_file);
        } elseif ($this->use_cookies === true && is_array($this->cookies_datas) === true && count($this->cookies_datas) > 0) {
            Debug::info('Use cookies datas : '.http_build_query($this->cookies_datas, '', '; '));
            curl_setopt($this->curl, CURLOPT_COOKIE, http_build_query($this->cookies_datas, '', '; '));
        }
        
        // user-agent
        Debug::info('Use user-agent : '.$this->use_user_agent);
        curl_setopt($this->curl, CURLOPT_USERAGENT, $this->use_user_agent);
        
        // Follow redirect
        curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true);
        
        // Return content
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        
        // Return headers
        curl_setopt($this->curl, CURLOPT_HEADER, true);
        
        // Send request
        Debug::info('send the request');
        $response = curl_exec($this->curl);
        Debug::info('request sended');
        
        if ($response !== false) {
            $this->status_code  = (int)curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
            list($headers, $this->response) = explode("\r\n\r\n", $response, 2);
            $this->processHeaders($headers);
            return true;
        } else {
            Debug::error('while exec the request : '.curl_error($this->curl));
            $this->error = array('code' => curl_errno($this->curl), 'error' => curl_error($this->curl));
            return false;
        }
    }
    
    
    /**
     * Process the HTTP header's of the response
     *
     * @param   string  $headers    HTTP header's response
     * @return  OpenPHPTorCurl\Browser
     * @access  private
     */
    private function processHeaders($headers)
    {
        Debug::info('process headers : '.$headers);
        
        $this->headers = array();
        
        foreach (explode("\r\n", $headers) as $line_header) {
            if (preg_match('~^HTTP/1.(0|1)~', $line_header) !== 0) {
                continue;
            }
            list($key, $value) = explode(':', $line_header, 2);
            $this->headers[$key] = trim($value);
        }
        
        return $this;
    }
    
    
    /**
     * Reset the return value
     *
     * @access  private
     */
    private function reset()
    {
        Debug::info('reset datas');
        
        $this->headers      = null;
        $this->response     = null;
        $this->status_code  = null;
        $this->result       = false;
        $this->error        = array();
    }
    
    
    /**
     * Define the proxy to use Tor
     *
     * @param   string  $host   The host to use
     * @param   int     $port   The port to use
     * @access  public
     * @static
     */
    static public function setProxy($host, $port)
    {
        if (is_string($host) === false || preg_match('~http://[a-z0-9.-]+~', $host) === 0) {
            throw new Exception('host must be an http string', 2);
        }
        
        if (is_int($port) === false) {
            throw new Exception('port must be an integer', 3);
        }
        
        Debug::info('Set the proxy with : '.$host.':'.$port);
        self::$proxy = $host.':'.(string)$port;
    }
    
    
    /**
     * Get the proxy to use Tor
     *
     * @return  string      The proxy to use Tor
     * @access  private
     * @static
     */
    static private function getProxy()
    {
        if (self::$proxy === null) {
            throw new Exception('proxy isn\'t defined', 1);
        }
        
        return self::$proxy;
    }
}


class Exception extends \Exception {}


/**
 * Class to debugging your call
 *
 * @author  Simon Leblanc <contact@leblanc-simon.eu>
 * @license http://sam.zoy.org/wtfpl/COPYING    WTFPL
 * @package OpenPHPTorCurl
 */
class Debug
{
    /**
     *
     */
    static private $enabled = false;
    
    /**
     *
     */
    static public function enable() { self::setState(true); }
    
    /**
     *
     */
    static public function disable() { self::setState(false); }
    
    /**
     *
     */
    static private function setState($state) { self::$enabled = (bool)$state; }
    
    /**
     *
     */
    static public function info($msg) { self::write('info', $msg); }
    
    /**
     *
     */
    static public function error($msg) { self::write('error', $msg); }
    
    /**
     *
     */
    static public function warn($msg) { self::write('warn', $msg); }
    
    /**
     *
     */
    static private function write($type, $msg)
    {
        if (self::$enabled === true) {
            echo '['.strtoupper($type).'] '.date('Y-m-d H:i:s').' '.$msg."\n";
        }
    }
}