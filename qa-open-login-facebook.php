<?php

/*
	Question2Answer (c) Gideon Greenspan
	Open Login Plugin (c) Alex Lixandru

	http://www.question2answer.org/

	
	File: qa-plugin/open-login/qa-open-login-facebook.php
	Version: 1.0.0
	Description: Login module class for Facebook Open Login plugin


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

class qa_facebook_open {
	
	var $directory;
	var $urltoroot;

	
	function load_module($directory, $urltoroot) {
		$this->directory=$directory;
		$this->urltoroot=$urltoroot;
	}

	
	function check_login() {
		$app_ok = qa_opt('facebook_app_enabled');
		if (!$app_ok) {
			return;
		}
		
		require_once $this->directory . 'qa-open-utils.php';
		
		$facebook = $this->getFacebook();
		$fb_userid=$facebook->getUser();
		
		if ($fb_userid) {
			try {
				$user=$facebook->api('/' . $fb_userid . '?fields=email,name,verified,location,website,about,picture');
				
				$duplicates = 0;
				if (is_array($user))
					$duplicates = qa_log_in_external_user('facebook', $fb_userid, array(
						'email' => @$user['email'],
						'handle' => @$user['name'],
						'confirmed' => @$user['verified'],
						'name' => @$user['name'],
						'location' => @$user['location']['name'],
						'website' => @$user['website'],
						'about' => @$user['bio'],
						'avatar' => strlen(@$user['picture']['data']['url']) ? qa_retrieve_url($user['picture']['data']['url']) : null,
					));
				
				if($duplicates > 0) {
					qa_redirect('logins', array('confirm' => '1', 'to' => qa_path(qa_request())));
				}

			} catch (FacebookApiException $e) {
				/* do nothing */
			}
			
		} else if(isset($_GET['fb_source']) && $_GET['fb_source'] == 'appcenter' && isset($_GET['fb_appcenter']) && isset($_GET['code']) ) {
			// this is a way to track somehow there was an error logging in when coming from Facebook App Center
			qa_redirect_raw(qa_opt('site_url') . '?fbappcntrerr');
		}
	}
	

	function match_source($source) {
		return substr($source, 0, 8) == 'facebook';
	}

	function do_logout() {
		$app_ok = qa_opt('facebook_app_enabled');

		if (!$app_ok) {
			return;
		}
		
		$facebook = $this->getFacebook();
		$facebook->destroySession();
	}
	
	function login_html($tourl, $context) {
		$app_ok = qa_opt('facebook_app_enabled');

		if (!$app_ok) {
			return;
		}
		
		$this->printCode($tourl, false, $context);
	}

	
	function logout_html($tourl) {
	
		$app_ok = qa_opt('facebook_app_enabled');

		if (!$app_ok) {
			return;
		}
			
		$this->printCode($tourl, true, 'menu');
	}
	

	function printCode($tourl, $logout, $context) {
		$facebook = $this->getFacebook();
		
		if($logout) {
			$url = $facebook->getLogoutUrl(array(
				'next'	=> $tourl,
			));
			$img = 'f-logout';
			$title = qa_lang_html('main/nav_logout');
			
		} else {
			$url = $facebook->getLoginUrl(array(
				'scope'			=> 'user_about_me,user_location,user_website',
				'redirect_uri'	=> $tourl,
			));
			$img = 'f-connect';
			$title = qa_lang_html('plugin_open/facebook_login');
		}
		
?>
  <div class="open-login-button <?php echo $img?>" title="<?php echo $title;?>" onclick="window.location='<?php echo $url?>'">&nbsp;</div>
<?php
	
	}
	
	
	function admin_form() {
		$saved=false;
		
		if (qa_clicked('facebook_save_button')) {
			$enabled = qa_post_text('facebook_app_enabled_field');
			qa_opt('facebook_app_enabled', empty($enabled) ? 0 : 1);
			qa_opt('facebook_app_id', qa_post_text('facebook_app_id_field'));
			qa_opt('facebook_app_secret', qa_post_text('facebook_app_secret_field'));
			$saved=true;
		}
		
		$ready=strlen(qa_opt('facebook_app_id')) && strlen(qa_opt('facebook_app_secret'));
		
		return array(
			'ok' => $saved ? 'Facebook application details saved' : null,
			
			'fields' => array(
				array(
					'type' => 'checkbox',
					'label' => 'Enable Facebook Login',
					'value' => qa_opt('facebook_app_enabled') ? true : false,
					'tags' => 'NAME="facebook_app_enabled_field"',
				),
				
				array(
					'label' => 'Facebook App ID:',
					'value' => qa_html(qa_opt('facebook_app_id')),
					'tags' => 'NAME="facebook_app_id_field"',
				),

				array(
					'label' => 'Facebook App Secret:',
					'value' => qa_html(qa_opt('facebook_app_secret')),
					'tags' => 'NAME="facebook_app_secret_field"',
					'error' => $ready ? null : 'To use Facebook Login, please <A HREF="http://developers.facebook.com/setup/" TARGET="_blank">set up a Facebook application</A>.',
				),
			),
			
			'buttons' => array(
				array(
					'label' => 'Save Changes',
					'tags' => 'NAME="facebook_save_button"',
				),
			),
		);
	}
	
	function getFacebook() {
		$app_id=qa_opt('facebook_app_id');
		$app_secret=qa_opt('facebook_app_secret');
		
		if (!(strlen($app_id) && strlen($app_secret)))
			return;
			
		if (!function_exists('json_decode')) { // work around fact that PHP might not have JSON extension installed
			require_once $this->directory.'JSON.php';
			
			function json_decode($json)
			{
				$decoder=new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
				return $decoder->decode($json);
			}
		}
		
		require_once $this->directory.'facebook.php';
		
		$facebook = new Facebook(array(
			'appId'  => $app_id,
			'secret' => $app_secret,
			'cookie' => false,
		));
		
		return $facebook;
	}
}
/*
	Omit PHP closing tag to help avoid accidental output
*/