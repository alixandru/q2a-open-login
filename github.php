<?php
/**
 * Copyright 2011 Facebook, Inc.
 * Copyright 2013 Alexandru Ionut Lixandru, alex.punctsivirgula.ro
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may
 * not use this file except in compliance with the License. You may obtain
 * a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 *
 * Based on Facebook API libary by Naitik Shah <naitik@facebook.com>
 */

if (!function_exists('curl_init')) {
  throw new Exception('Github needs the CURL PHP extension.');
}
if (!function_exists('json_decode')) {
  throw new Exception('Github needs the JSON PHP extension.');
}

/**
 * Provides access to the Github Platform.
 */
class Github
{

   public static $LOGIN_URL = 'https://github.com/login/oauth/authorize';
   public static $AUTH_URL = 'https://github.com/login/oauth/access_token';
   public static $API_URL = 'https://api.github.com/';
  /**
   * Default options for curl.
   */
  public static $CURL_OPTS = array(
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 60,
    CURLOPT_USERAGENT      => 'github-php',
  );

  /**
   * The Application ID.
   *
   * @var string
   */
  protected $appId;

  /**
   * The Application API Secret.
   *
   * @var string
   */
  protected $apiSecret;

  /**
   * A CSRF state variable to assist in the defense against CSRF attacks.
   */
  protected $state;

  /**
   * The OAuth access token received in exchange for a valid authorization
   * code.  null means the access token has yet to be determined.
   *
   * @var string
   */
  protected $accessToken = null;

  /**
   * Initialize a Github Application.
   *
   * The configuration:
   * - appId: the application ID
   * - secret: the application secret
   * - fileUpload: (optional) boolean indicating if file uploads are enabled
   *
   * @param array $config The application configuration
   */
  public function __construct($config) {
    $this->appId = $config['appId'];
    $this->apiSecret = $config['secret'];

    $state = $this->getPersistentData('state');
    if (!empty($state)) {
      $this->state = $this->getPersistentData('state');
    }
  }

  public function check() {
	return $this->getAccessToken() !== false;
  }
  
  /**
   * Sets the access token for api calls.  
   *
   * @param string $access_token an access token.
   */
  public function setAccessToken($access_token) {
    $this->accessToken = $access_token;
	$this->setPersistentData('access_token', $access_token);
  }

  /**
   * Determines the access token that should be used for API calls.
   * The first time this is called, $this->accessToken is set equal
   * to either a valid user access token, or it's set to the application
   * access token if a valid user access token wasn't available.  Subsequent
   * calls return whatever the first call returned.
   *
   * @return string The access token
   */
  public function getAccessToken() {
    if ($this->accessToken !== null) {
      // we've done this already and cached it.  Just return.
      return $this->accessToken;
    }
	
    // fetch an access token
	$token = $this->getAccessTokenFromCode( $this->getCode() );
	
	if($token === false) {
		// as a fallback, just return whatever is in the persistent store
		$token = $this->getPersistentData('access_token');
	}
	
    $this->setAccessToken($token);

    return $this->accessToken;
  }


  /**
   * Get a Login URL for use with redirects. By default, full page redirect is
   * assumed. If you are using the generated URL with a window.open() call in
   * JavaScript, you can pass in display=popup as part of the $params.
   *
   * The parameters:
   * - redirect_uri: the url to go to after a successful login
   * - scope: comma separated list of requested extended perms
   *
   * @param array $params Provide custom parameters
   * @return string The URL for the login flow
   */
  public function getLoginUrl($params=array()) {
    $this->establishCSRFTokenState();
    $currentUrl = $this->getCurrentUrl();

    // if 'scope' is passed as an array, convert to comma separated list
    $scopeParams = isset($params['scope']) ? $params['scope'] : null;
    if ($scopeParams && is_array($scopeParams)) {
      $params['scope'] = implode(',', $scopeParams);
    }
	
	$lp = array_merge(array('client_id' => $this->appId,
                    'redirect_uri' => $currentUrl, // possibly overwritten
                    'state' => $this->state),
                  $params);
	$url = self::$LOGIN_URL . '?' . http_build_query($lp, null, '&amp;');
    return $url;
  }


  /**
   * Get the authorization code from the query parameters, if it exists,
   * and otherwise return false to signal no authorization code was
   * discoverable.
   *
   * @return mixed The authorization code, or false if the authorization
   *               code could not be determined.
   */
  protected function getCode() {
    if (isset($_REQUEST['code'])) {
      if ($this->state !== null &&
          isset($_REQUEST['state']) &&
          $this->state === $_REQUEST['state']) {
		  
        // CSRF state has done its job, so clear it
        $this->state = null;
        $this->clearPersistentData('state');
        return $_REQUEST['code'];
      } else {
        return false;
      }
    }

    return false;
  }

  /**
   * Lays down a CSRF state token for this process.
   *
   * @return void
   */
  protected function establishCSRFTokenState() {
    if ($this->state === null) {
      $this->state = md5(uniqid(mt_rand(), true));
      $this->setPersistentData('state', $this->state);
    }
  }

  protected function getAccessTokenFromCode($code, $redirect_uri = null) {
    if (empty($code)) {
      return false;
    }

    if ($redirect_uri === null) {
      $redirect_uri = $this->getCurrentUrl();
    }

    try {
      $access_token_response =
        $this->_oauthRequest(
          self::$AUTH_URL,
		  'POST',
          $params = array('client_id' => $this->appId,
                          'client_secret' => $this->apiSecret,
                          'redirect_uri' => $redirect_uri,
                          'code' => $code));
    } catch (Exception $e) {
      // most likely that user very recently revoked authorization.
      // In any event, we don't have an access token, so say so.
      return false;
    }

    if (empty($access_token_response)) {
      return false;
    }

    $response_params = array();
    parse_str($access_token_response, $response_params);
    if (!isset($response_params['access_token'])) {
      return false;
    }

    return $response_params['access_token'];
  }

  /**
   * Invoke the API.
   *
   * @param string $path The path (required)
   * @param string $method The http method (default 'GET')
   * @param array $params The query/post data
   *
   * @return mixed The decoded response object
   * @throws Exception
   */
  public function api($path, $method = 'GET', $params = array()) {
    if (is_array($method) && empty($params)) {
      $params = $method;
      $method = 'GET';
    }

    $result = json_decode($this->_oauthRequest(
      self::$API_URL . $path, 
	  $method, 
      $params
    ), true);

    // results are returned, errors are thrown
    if (is_array($result) && isset($result['error'])) {
      throw new Exception($result['error']);
    }

    return $result;
  }

  /**
   * Make a OAuth Request.
   *
   * @param string $url The path (required)
   * @param array $params The query/post data
   *
   * @return string The decoded response object
   * @throws Exception
   */
  protected function _oauthRequest($url, $method, $params) {
    if (!isset($params['access_token']) && !isset($params['code'])) {
      $params['access_token'] = $this->getAccessToken();
    }

    // json_encode all params values that are not strings
    foreach ($params as $key => $value) {
      if (!is_string($value)) {
        $params[$key] = json_encode($value);
      }
    }

    return $this->makeRequest($url, $method, $params);
  }

  /**
   * Makes an HTTP request. This method can be overridden by subclasses if
   * developers want to do fancier things or use something other than curl to
   * make the request.
   *
   * @param string $url The URL to make the request to
   * @param array $params The parameters to use for the POST body
   * @param CurlHandler $ch Initialized curl handle
   *
   * @return string The response text
   */
  protected function makeRequest($url, $method, $params, $ch=null) {
    if (!$ch) {
      $ch = curl_init();
    }
	
    $opts = self::$CURL_OPTS;

	if(strtoupper($method) == 'POST') {
		$opts[CURLOPT_POST] = true;
		$opts[CURLOPT_POSTFIELDS] = http_build_query($params, null, '&');
	} else {
		$opts[CURLOPT_HTTPGET] = true;
		$url .= '?' . http_build_query($params, null, '&');
	}
    $opts[CURLOPT_URL] = $url;
	
	// print_r($params);die;
	
    // disable the 'Expect: 100-continue' behaviour. This causes CURL to wait
    // for 2 seconds if the server does not support this header.
    if (isset($opts[CURLOPT_HTTPHEADER])) {
      $existing_headers = $opts[CURLOPT_HTTPHEADER];
      $existing_headers[] = 'Expect:';
      $opts[CURLOPT_HTTPHEADER] = $existing_headers;
    } else {
      $opts[CURLOPT_HTTPHEADER] = array('Expect:');
    }

    curl_setopt_array($ch, $opts);
    $result = curl_exec($ch);

    if (curl_errno($ch) == 60) { // CURLE_SSL_CACERT
      echo('Invalid or no certificate authority found, '.
                     'using bundled information');
      curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__) . '/gh_ca_chain_bundle.crt');
      
	  $result = curl_exec($ch);
    }
	
	// this should be here ONLY while testing. NEVER in production as it introduces man-in-the-middle vulnerability!
    if ($result === false) {
	  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // never uncomment this
	  $result = curl_exec($ch);
	}

    if ($result === false) {
      $e = new Exception(curl_error($ch));
      curl_close($ch);
      throw $e;
    }
    curl_close($ch);
    return $result;
  }

  /**
   * Returns the Current URL, stripping it of known FB parameters that should
   * not persist.
   *
   * @return string The current URL
   */
  protected function getCurrentUrl() {
    if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1)
      || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'
    ) {
      $protocol = 'https://';
    }
    else {
      $protocol = 'http://';
    }
	
	######### fix by Alex
	$host = parse_url(qa_opt('site_url'), PHP_URL_HOST);
	if(empty($host)) {
		$host = $_SERVER['HTTP_HOST'];
	}
	#######################
	
    $currentUrl = $protocol . $host . $_SERVER['REQUEST_URI'];
    $parts = parse_url($currentUrl);

    // use port if non default
    $port =
      isset($parts['port']) &&
      (($protocol === 'http://' && $parts['port'] !== 80) ||
       ($protocol === 'https://' && $parts['port'] !== 443))
      ? ':' . $parts['port'] : '';

    // rebuild
    return $protocol . $parts['host'] . $port . $parts['path'];
  }

  /**
   * Destroy the current session
   */
  public function destroySession() {
    $this->setAccessToken(null);
    $this->clearAllPersistentData();
  }

  protected function setPersistentData($key, $value) {
    $session_var_name = $this->constructSessionVariableName($key);
    $_SESSION[$session_var_name] = $value;
  }

  protected function getPersistentData($key, $default = false) {
    $session_var_name = $this->constructSessionVariableName($key);
    return isset($_SESSION[$session_var_name]) ?
      $_SESSION[$session_var_name] : $default;
  }

  protected function clearPersistentData($key) {
    $session_var_name = $this->constructSessionVariableName($key);
    unset($_SESSION[$session_var_name]);
  }

  protected function clearAllPersistentData() {
	$kSupportedKeys = array('state', 'access_token');
    foreach ($kSupportedKeys as $key) {
      $this->clearPersistentData($key);
    }
  }

  protected function constructSessionVariableName($key) {
    return implode('_', array('gh',
                              $this->appId,
                              $key));
  }
}
