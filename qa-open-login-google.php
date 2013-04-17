<?php

/*
	Question2Answer (c) Gideon Greenspan
	Open Login Plugin (c) Alex Lixandru

	http://www.question2answer.org/

	
	File: qa-plugin/open-login/qa-open-login-google.php
	Version: 1.0.0
	Description: Login module class for Google Open Login plugin


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

class qa_google_open {
	
	var $directory;
	var $urltoroot;
	
	var $identity = 'https://www.google.com/accounts/o8/id';
	var $endpoint = 'https://www.google.com/accounts/o8/ud';
	var $version = 2;
	var $identifier_select = true;
	var $xcap = array(true, false); # ax and sreg capabilities
	
	
	function load_module($directory, $urltoroot) {
		$this->directory=$directory;
		$this->urltoroot=$urltoroot;
	}

	function checkRequest($openid) {
		$ep = @$openid->data['openid_op_endpoint'];
		return $ep == $this->endpoint;
	}
	
	function check_login() {
		$app_ok = qa_opt('google_app_enabled');
		if (!$app_ok) {
			return;
		}
		
		require_once $this->directory . 'qa-open-utils.php';
		
		$google = $this->getOpenID();
		if( $google->mode && $this->checkRequest($google) && $google->validate()) {
			$attr = $google->getAttributes();
			
			$name = @$attr['namePerson/first'];
			$email = @$attr['contact/email'];
			$identity = $google->identity;
			
			$duplicates = qa_log_in_external_user('google', $identity, array(
				'email' => $email,
				'handle' => $name,
				'confirmed' => 1,
				'name' => $name,
				'location' => '',
				'website' => '',
				'about' => '',
				'avatar' => null,
			));
			
			if($duplicates > 0) {
				qa_redirect('logins', array('confirm' => '1', 'to' => qa_path(qa_request())));
			}
		}
	}
	

	function match_source($source) {
		return substr($source, 0, 6) == 'google';
	}

	function do_logout() {
		// nothing
	}
	
	function login_html($tourl, $context) {
		$app_ok = qa_opt('google_app_enabled');
		if (!$app_ok) {
			return;
		}
		
		$this->printCode($tourl, false, $context);
	}

	
	function logout_html($tourl) {
		$app_ok = qa_opt('google_app_enabled');
		if (!$app_ok) {
			return;
		}
		
		$this->printCode($tourl, true, 'menu');
	}
	

	function printCode($tourl, $logout, $context) {
		$google = $this->getOpenID();
		
		if($logout) {
			$url = qa_path('logout');
			$img = 'g-logout';
			$title = qa_lang_html('main/nav_logout');
			
		} else {
			$google->identity = $this->identity;
			$google->required = array('contact/email', 'namePerson/first');
			$google->returnUrl = $tourl;
			try {
				$url = $google->authUrl();
			} catch (Exception $e) {
				$url = null;
			}
			$img = 'g-connect';
			$title = qa_lang_html('plugin_open/google_login');
		}
		
		if($url):
?>
  <div class="open-login-button <?php echo $img?>" title="<?php echo $title;?>" onclick="window.location='<?php echo $url?>'">&nbsp;</div>
<?php
		endif;
	}
	
	function admin_form() {
		$saved=false;
		
		if (qa_clicked('google_save_button')) {
			$enabled = qa_post_text('google_app_enabled_field');
			qa_opt('google_app_enabled', empty($enabled) ? 0 : 1);
			$saved=true;
		}
		
		return array(
			'ok' => $saved ? 'Google application details saved' : null,
			
			'fields' => array(
				array(
					'type' => 'checkbox',
					'label' => 'Enable Google Login',
					'value' => qa_opt('google_app_enabled') ? true : false,
					'tags' => 'NAME="google_app_enabled_field"',
				),
				
			),
			
			'buttons' => array(
				array(
					'label' => 'Save Changes',
					'tags' => 'NAME="google_save_button"',
				),
			),
		);
	}
	
	function getOpenID() {
		$cachedParams = array(
			'version' => $this->version,
			'endpoint' => $this->endpoint,
			'identifier_select' => $this->identifier_select,
			'xcap' => $this->xcap
		);
		require_once $this->directory.'openid.php';
		
		$host = parse_url(qa_opt('site_url'), PHP_URL_HOST);
		if(empty($host)) {
			$host = $_SERVER['HTTP_HOST'];
		}
		$openid = new LightOpenID($host, $cachedParams);
		return $openid;
	}
}
/*
	Omit PHP closing tag to help avoid accidental output
*/