<?php

/*
	Question2Answer (c) Gideon Greenspan
	Open Login Plugin (c) Alex Lixandru

	http://www.question2answer.org/

	
	File: qa-plugin/open-login/qa-open-login-yahoo.php
	Version: 1.0.0
	Description: Login module class for Yahoo Open Login plugin


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

class qa_yahoo_open {
	
	var $directory;
	var $urltoroot;
	
	var $identity = 'https://me.yahoo.com/';
	var $endpoint = 'https://open.login.yahooapis.com/openid/op/auth';
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
		$app_ok = qa_opt('yahoo_app_enabled');
		if (!$app_ok) {
			return;
		}
		
		require_once $this->directory . 'qa-open-utils.php';
		
		$yahoo = $this->getOpenID();
		if( $yahoo->mode && $this->checkRequest($yahoo) && $yahoo->validate()) {
			$attr = $yahoo->getAttributes();
			
			$name = @$attr['namePerson/friendly'];
			$fullname = @$attr['namePerson'];
			$email = @$attr['contact/email'];
			$identity = $yahoo->identity;
			
			$duplicates = qa_log_in_external_user('yahoo', $identity, array(
				'email' => $email,
				'handle' => $name,
				'confirmed' => 1,
				'name' => $fullname,
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
		return substr($source, 0, 5) == 'yahoo';
	}
	
	
	function login_html($tourl, $context) {
		$app_ok = qa_opt('yahoo_app_enabled');
		if (!$app_ok) {
			return;
		}
		
		$this->printCode($tourl, false, $context);
	}

	
	function logout_html($tourl) {
		$app_ok = qa_opt('yahoo_app_enabled');
		if (!$app_ok) {
			return;
		}
		
		$this->printCode($tourl, true, 'menu');
	}
	

	function printCode($tourl, $logout, $context) {
		$yahoo = $this->getOpenID();
		
		if($logout) {
			$url = qa_path('logout');
			$img = 'y-logout';
			$title = qa_lang_html('main/nav_logout');
			
		} else {
			$yahoo->identity = $this->identity;
			$yahoo->required = array('contact/email', 'namePerson/friendly', 'namePerson');
			$yahoo->returnUrl = $tourl;
			try {
				$url = $yahoo->authUrl();
			} catch (Exception $e) {
				$url = null;
			}
			$img = 'y-connect';
			$title = qa_lang_html('plugin_open/yahoo_login');
		}
		
		if($url):
?>
  <div class="open-login-button <?php echo $img?>" title="<?php echo $title;?>" onclick="window.location='<?php echo $url?>'">&nbsp;</div>
<?php
		endif;
	}
	
	function admin_form() {
		$saved=false;
		
		if (qa_clicked('yahoo_save_button')) {
			$enabled = qa_post_text('yahoo_app_enabled_field');
			qa_opt('yahoo_app_enabled', empty($enabled) ? 0 : 1);
			$saved=true;
		}
		
		return array(
			'ok' => $saved ? 'yahoo application details saved' : null,
			
			'fields' => array(
				array(
					'type' => 'checkbox',
					'label' => 'Enable Yahoo Login',
					'value' => qa_opt('yahoo_app_enabled') ? true : false,
					'tags' => 'NAME="yahoo_app_enabled_field"',
				),
				
			),
			
			'buttons' => array(
				array(
					'label' => 'Save Changes',
					'tags' => 'NAME="yahoo_save_button"',
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
