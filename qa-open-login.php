<?php

/*
	Question2Answer (c) Gideon Greenspan
	Open Login Plugin (c) Alex Lixandru

	http://www.question2answer.org/

	
	File: qa-plugin/open-login/qa-open-login.php
	Version: 2.0.0
	Description: Login module class for handling OpenID/OAuth logins 
	through HybridAuth library


	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: http://www.question2answer.org/license.php
*/

class qa_open_login {
	
	var $directory;
	var $urltoroot;
	var $provider;

	function load_module($directory, $urltoroot, $provider) {
		$this->directory = $directory;
		$this->urltoroot = $urltoroot;
		$this->provider = $provider;
	}

	
	function check_login() {
		
		$action = null;
		$key = null;
		
		if( !empty($_GET['hauth_start']) ) {
			$key = trim(strip_tags($_GET['hauth_start']));
			$action = 'process';
			
		} else if( !empty($_GET['hauth_done']) ) {
			$key = trim(strip_tags($_GET['hauth_done']));
			$action = 'process';
			
		} else if( !empty($_GET['login']) ) {
			$key = trim(strip_tags($_GET['login']));
			$action = 'login';
			
		} else if(isset($_GET['fb_source']) && $_GET['fb_source'] == 'appcenter' &&
				isset($_SERVER['HTTP_REFERER']) && stristr($_SERVER['HTTP_REFERER'], 'www.facebook.com') !== false &&
				isset($_GET['fb_appcenter']) && $_GET['fb_appcenter'] == '1' && isset($_GET['code']) ) {
			// allow AppCenter users to login directly
			$key = 'facebook';
			$action = 'login';
		}
		
		if($key == null || strcasecmp($key, $this->provider) != 0) {
			return false;
		}
		
		if($action == 'login') {
			// handle the login

			// after login come back to the same page
			$loginCallback = qa_path('', array(), qa_opt('site_url'));
			
			require_once $this->directory . 'Hybrid/Auth.php';
			require_once $this->directory . 'qa-open-utils.php';
			
			// prepare the configuration of HybridAuth
			$config = $this->getConfig($loginCallback);
			
			try {
				// try to login
				$hybridauth = new Hybrid_Auth( $config );
				$adapter = $hybridauth->authenticate( $this->provider );
				
				// if ok, create/refresh the user account
				$user = $adapter->getUserProfile();
				
				$duplicates = 0;
				if (!empty($user))
					$duplicates = qa_log_in_external_user($key, $user->identifier, array(
						'email' => @$user->email,
						'handle' => @$user->displayName,
						'confirmed' => !empty($user->emailVerified),
						'name' => @$user->displayName,
						'location' => @$user->city,
						'website' => @$user->webSiteURL,
						'about' => @$user->description,
						'avatar' => strlen(@$user->photoURL) ? qa_retrieve_url($user->photoURL) : null,
					));
				
				if($duplicates > 0) {
					qa_redirect('logins', array('confirm' => '1', 'to' => qa_path(qa_request())));
				}
				
			} catch(Exception $e) {
				// not really interested in the error message - for now
				// however, in case we have errors 6 or 7, then we have to call logout to clean everything up
				if($e->getCode() == 6 || $e->getCode() == 7) {
					$adapter->logout();
				}
				
				// redirect
				qa_redirect(qa_request(), array('provider' => $this->provider, 'code' => $e->getCode()));
			}
		}
		
		if($action == 'process') {
			require_once( "Hybrid/Auth.php" );
			require_once( "Hybrid/Endpoint.php" ); 
			Hybrid_Endpoint::process();
		}

		return false;
	}
	
	
	function do_logout() {
		// after login come back to the same page
		$loginCallback = qa_path('', array(), qa_opt('site_url'));
		
		require_once( "Hybrid/Auth.php" );
		
		// prepare the configuration of HybridAuth
		$config = $this->getConfig($loginCallback);
		
		try {
			// try to logout
			$hybridauth = new Hybrid_Auth( $config );
		
			if($hybridauth->isConnectedWith( $this->provider )) {
				$adapter = $hybridauth->getAdapter( $this->provider );
				$adapter->logout();
			}
			
		} catch(Exception $e) {
			// not really interested in the error message - for now
			// however, in case we have errors 6 or 7, then we have to call logout to clean everything up
			if($e->getCode() == 6 || $e->getCode() == 7) {
				$adapter->logout();
			}
		}
	}
	
	
	function match_source($source) {
		// the session source will be in the format 'provider-xyx'
		$pos = strpos($source, '-');
		if($pos === false) {
			$pos = strlen($source);
		}
		
		// identify the provider out of the session source
		$provider = substr($source, 0, $pos);
		
		// verify if the identified provider matches the current one
		return stripos($this->provider, $provider) !== false;
	}

	
	function login_html($tourl, $context) {
		$this->printCode($tourl, false, $context);
	}

	
	function logout_html($tourl) {
		$this->printCode($tourl, true, 'menu');
	}
	

	function printCode($tourl, $logout, $context) {
		$key = strtolower($this->provider);
		$showInHeader = qa_opt("{$key}_app_shortcut") ? true : false;
		
		if(!$logout && !$showInHeader && $context == 'menu') {
			// do not show login button in the header for this
			return;
		}
		
		if($logout) {
			$url = $tourl;
			$img = "$key-logout";
			$title = qa_lang_html('main/nav_logout');
			$text = '&nbsp;';
			if(!$showInHeader) {
				// most likely there's no graphical button for this, so print a plain link
				echo '<a class="qa-nav-user-link" href="' . qa_html($tourl) . '">' . $title . '</a>';
				return;
			}
			
		} else {
			if(strpos($tourl, '?') === false) {
				$tourl .= '?login=' . $key;
			} else {
				$tourl .= '&login=' . $key;
			}

			$url = $tourl;
			$img = "$key-connect";
			$title = qa_lang_html_sub('plugin_open/login_using', $this->provider);
			$text = '&nbsp;';
			
			if($context != 'menu') {
				$text = $title;
			}
		}
		
?>
  <div class="open-login-button context-<?php echo $context, ' ', $img?>" title="<?php echo $title;?>" onclick="window.location='<?php echo $url ?>'"><?php echo $text ?></div>
<?php
	
	}
	
	
	function getConfig($url) {
		$key = strtolower($this->provider);
		return array(
			'base_url' => $url, 
			'providers' => array ( 
				$this->provider => array (
					'enabled' => true,
					'keys' => array(
						'id' => qa_opt("{$key}_app_id"), 
						'key' => qa_opt("{$key}_app_id"), 
						'secret' => qa_opt("{$key}_app_secret")
					),
					'scope' => $this->provider == 'Facebook' ? 'email,user_about_me,user_location,user_website' : null,
				)
			),
			'debug_mode' => false,
			'debug_file' => ''
		);
	}

}

/*
	Omit PHP closing tag to help avoid accidental output
*/