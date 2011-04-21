<?php

/* InstapaperOAuth
 * ------------------------------------------------------
 * A PHP library for working with Instapaper's OAuth API
 * Randy Hoyt (randy@randyhoyt.com) http://randyhoyt.com
 *
 * Much thanks to Abraham Williams (abraham@abrah.am)
 * http://abrah.am, whose wrapper functions for OAuthRequest
 * in twitteroauth https://github.com/abraham/twitteroauth
 * have been incorporated into InstapaperOAuth.
 * 
 */

/**
 * Load OAuth lib. You can find it at http://oauth.net
 */

require_once('OAuth.php');


/**
 * InstapaperOAuth class
 */

class InstapaperOAuth {
  
    public $http_code;									/* Contains the last HTTP status code returned. */
    public $url;										/* Contains the last API call. */
    public $host = "http://www.instapaper.com/api/1/";	/* Set up the API root URL. */  
    public $timeout = 30;								/* Set timeout default. */
    public $connecttimeout = 30;                        /* Set connect timeout. */   
    public $ssl_verifypeer = FALSE;						/* Verify SSL Cert. */
    public $format = 'json';							/* Response format; valid values are 'json' and 'qline' */  
    public $decode_format = TRUE;						/* Decode returned data data. */  
    public $http_info;									/* Contains the last HTTP headers returned. */
    public $useragent = 'InstapaperOAuth';  			/* Set the useragnet. */
    //public $retry = TRUE;  							/* Immediately retry the API call if the response was not successful. */



   /* Debug helpers
    */
    
    function lastStatusCode() { return $this->http_status; }
    function lastAPICall() { return $this->last_api_call; }

    
   /* construct InstapaperOAuth object
    */
    
    function __construct($consumer_key, $consumer_secret, $oauth_token = NULL, $oauth_token_secret = NULL) {
        $this->sha1_method = new OAuthSignatureMethod_HMAC_SHA1();
        $this->consumer = new OAuthConsumer($consumer_key, $consumer_secret);
        if (!empty($oauth_token) && !empty($oauth_token_secret)) {
            $this->token = new OAuthConsumer($oauth_token, $oauth_token_secret);
        } else {
            $this->token = NULL;
        }
      }

   

   /* Exchange of username and password for access token and secret.
    * 
    *     Array
	*     (
    *         [oauth_token] => 4dNwBiwLrVi6ORdax401Ql2jTJUIN7sWTO6nBD3ndtRMh8b5at
    *         [oauth_token_secret] => d0mIOY9iEV0hmDa8F700v3UhW8JRdZkjD3GBI249dFf4hPft8q
    *     )
    */
      
	function get_access_token($username, $password) {
		$parameters = array();
		$parameters['x_auth_username'] = $username;
    	$parameters['x_auth_password'] = $password;
		$parameters['x_auth_mode'] = 'client_auth';
		$request = $this->oAuthRequest($this->host . "oauth/access_token", 'POST', $parameters);
		$token = OAuthUtil::parse_parameters($request);    
		$this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
		return $token;
	}

	
   /* Retrieve information about the currently logged in user.
    * 
    *    Array
    *    (
    *        [0] => stdClass Object
    *            (
    *                [type] => user
    *                [user_id] => 
    *                [username] => 
    *                [subscription_is_active] => 0 or 1
    *            )
    *    )
    */
	
	function verify_credentials() {

		$parameters = array();
		$parameters['format'] = $this->format;
		$request = $this->oAuthRequest($this->host . "account/verify_credentials", 'POST', $parameters);				
		$user = $this->maybe_decode($request);
		return $user;

	}
	
	

	
   /*
    * Get a list of the account's user-created folders.
    * 
    *     Array
    *     (
    *         [0] => stdClass Object
    *             (
    *                 [type] =>
    *                 [folder_id] =>
    *                 [title] =>
    *                 [sync_to_mobile] =>
    *                 [position] =>
    *             )
    *         [...]
    *     )          
    */
	
	function list_folders() {

		$parameters = array();
		$parameters['format'] = $this->format;
		$request = $this->oAuthRequest($this->host . "folders/list", 'POST', $parameters);				
		$folders = $this->maybe_decode($request);
		return $folders;
	
	}
	
	
   /**
    * Lists the user's bookmarks.
    * 
    *     Array
    *     (
    *         [0] => stdClass Object
    *             (
    *                 [type] =>
    *                 [folder_id] =>
    *                 [title] =>
    *                 [sync_to_mobile] =>
    *                 [position] =>
    *                  )
    *              [...]
    *          )          
    */

	function list_bookmarks($limit="",$folder_id="",$have="") {

		$parameters = array();
		$parameters['format'] = $this->format;		
		$parameters['limit'] = $limit;
    	$parameters['folder_id'] = $folder_id;
		$parameters['have'] = $have;
		$request = $this->oAuthRequest($this->host . "bookmarks/list", 'POST', $parameters);
		$bookmarks = $this->maybe_decode($request);
		return $bookmarks;
	
	}
	
	
	
	/* -----------------------------------------------------------
	 * There are more methods available through the Instapaper API.
	 * Stay tuned for updates or -- better yet -- add a method and
	 * contribute back to the library.
	 * ----------------------------------------------------------- */

	
	

/*   Helper functions for formatting responses
 *   -----------------------------------------
 */
	
	
   /*   Check the InstapaperOAuth object's settings and (if required)
    *   decode the response.        
    */	
	
	function maybe_decode($request) {
		if ($this->decode_format == TRUE) {
			if ($this->format == "qline")
				return $this->qline_decode($request);
			else 
				return json_decode($request); 
		} else {		
			return $request;
		}
	}		

	
   /*   Decodes Instapaper's qline format, a simple custom format supported for environments
    *   without convenient JSON decoding, to the same resulting format as json_decode.      
    */	
	
	function qline_decode($qline_string) {
	
		$results = array();

		$lines = explode("\n",$qline_string);
		foreach($lines as $line) {
		
			$attributes = new stdClass();
			$pairs = explode("&",$line);
			foreach($pairs as $pair) {
				$parts = explode("=",$pair);
				$attributes->$parts[0] = $parts[1]; 
			}
			
			$results[] = $attributes; 

		}

		return $results;
	
	}




/*   Wrapper functions for oAuthRequest
 *   ----------------------------------   
 *   from twitteroauth https://github.com/abraham/twitteroauth
 *   by Abraham Williams (abraham@abrah.am) http://abrah.am
 *     
 */	
	

   /*
    * GET wrapper for oAuthRequest.
    */
	
    function get($url, $parameters = array()) {
        $response = $this->oAuthRequest($url, 'GET', $parameters);
        if ($this->format === 'json' && $this->decode_json) {
            return json_decode($response);
        }
        return $response;
    }
    
  
   /*
    * POST wrapper for oAuthRequest.
    */

    function post($url, $parameters = array()) {
        $response = $this->oAuthRequest($url, 'POST', $parameters);
        if ($this->format === 'json' && $this->decode_json) {
            return json_decode($response);
        }
        return $response;
    }

   /*
    * DELETE wrapper for oAuthReqeust.
    */

    function delete($url, $parameters = array()) {
        $response = $this->oAuthRequest($url, 'DELETE', $parameters);
        if ($this->format === 'json' && $this->decode_json) {
            return json_decode($response);
        }
        return $response;
    }


   /*
    * Format and sign an OAuth / API request
    */
    function oAuthRequest($url, $method, $parameters) {
        if (strrpos($url, 'https://') !== 0 && strrpos($url, 'http://') !== 0) {
            $url = "{$this->host}{$url}.{$this->format}";
        }
        $request = OAuthRequest::from_consumer_and_token($this->consumer, $this->token, $method, $url, $parameters);
        $request->sign_request($this->sha1_method, $this->consumer, $this->token);
        switch ($method) {
            case 'GET':
                return $this->http($request->to_url(), 'GET');
            default:
                return $this->http($request->get_normalized_http_url(), $method, $request->to_postdata());
        }
    }

   /*
    * Make an HTTP request
    *
    * @return API results
    */
    function http($url, $method, $postfields = NULL) {
        $this->http_info = array();
        $ci = curl_init();
        /* Curl settings */
        curl_setopt($ci, CURLOPT_USERAGENT, $this->useragent);
        curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, $this->connecttimeout);
        curl_setopt($ci, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ci, CURLOPT_HTTPHEADER, array('Expect:'));
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, $this->ssl_verifypeer);
        curl_setopt($ci, CURLOPT_HEADERFUNCTION, array($this, 'getHeader'));
        curl_setopt($ci, CURLOPT_HEADER, FALSE);

        switch ($method) {
            case 'POST':
                curl_setopt($ci, CURLOPT_POST, TRUE);
                if (!empty($postfields)) {
                    curl_setopt($ci, CURLOPT_POSTFIELDS, $postfields);
                }
                break;
            case 'DELETE':
                curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
                if (!empty($postfields)) {
                    $url = "{$url}?{$postfields}";
                }
        }

        curl_setopt($ci, CURLOPT_URL, $url);
        $response = curl_exec($ci);
        $this->http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
        $this->http_info = array_merge($this->http_info, curl_getinfo($ci));
        $this->url = $url;
        curl_close ($ci);
        return $response;
    }

  
   /*
    * Get the header info to store.
    */
  
	function getHeader($ch, $header) {
	    $i = strpos($header, ':');
	    if (!empty($i)) {
	        $key = str_replace('-', '_', strtolower(substr($header, 0, $i)));
	        $value = trim(substr($header, $i + 2));
	        $this->http_header[$key] = $value;
	    }
	    return strlen($header);
	}

}

?>