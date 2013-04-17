<?php

/*
	Question2Answer (c) Gideon Greenspan
	Open Login Plugin (c) Alex Lixandru

	http://www.question2answer.org/

	
	File: qa-plugin/open-login/qa-open-login-github.php
	Version: 1.0.0
	Description: Login module class for Github Open Login plugin


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

class qa_github_open {
	
	var $directory;
	var $urltoroot;

	
	function load_module($directory, $urltoroot) {
		$this->directory=$directory;
		$this->urltoroot=$urltoroot;
	}

	
	function check_login() {
		$app_ok = qa_opt('github_app_enabled');
		if (!$app_ok) {
			return;
		}
		
		require_once $this->directory . 'qa-open-utils.php';
		
		$github = $this->getGithub();
		$auth = $github->check();
		
		if ($auth)
			try {
				$user = $github->api('user');
				
				$duplicates = 0; // for Github this will remain 0 
				if (is_array($user))
					$duplicates = qa_log_in_external_user('github', @$user['id'], array(
						'email' => '', // for Github there's no email address returned
						'handle' => @$user['login'],
						'confirmed' => 1,
						'name' => @$user['login'],
						'location' => '',
						'website' => '',
						'about' => '',
						'avatar' => strlen(@$user['avatar_url']) ? qa_retrieve_url($user['avatar_url']) : null,
					));
				
				if($duplicates > 0) {
					qa_redirect('logins', array('confirm' => '1', 'to' => qa_path(qa_request())));
				}

			} catch (Exception $e) {
				/* do nothing */
			}
	}
	

	function match_source($source) {
		return substr($source, 0, 6) == 'github';
	}

	function do_logout() {
		$app_ok = qa_opt('github_app_enabled');

		if (!$app_ok) {
			return;
		}

		$github = $this->getGithub();
		$github->destroySession();
	}
	
	function login_html($tourl, $context) {
		$app_ok = qa_opt('github_app_enabled');

		if (!$app_ok) {
			return;
		}
		
		$this->printCode($tourl, false, $context);
	}

	
	function logout_html($tourl) {
	
		$app_ok = qa_opt('github_app_enabled');

		if (!$app_ok) {
			return;
		}
			
		$this->printCode($tourl, true, 'menu');
	}
	

	function printCode($tourl, $logout, $context) {
		$github = $this->getGithub();
		
		if($logout) {
			$url = $tourl;
			$img = 'h-logout';
			$title = qa_lang_html('main/nav_logout');
			
		} else {
			$url = $github->getLoginUrl(array(
				'scope'			=> 'user',
				'redirect_uri'	=> $tourl,
			));
			$img = 'h-connect';
			$title = qa_lang_html('plugin_open/github_login');
		}
		
?>
  <div class="open-login-button <?php echo $img?>" title="<?php echo $title;?>" onclick="window.location='<?php echo $url?>'">&nbsp;</div>
<?php
	
	}
	
	
	function admin_form() {
		$saved=false;
		
		if (qa_clicked('github_save_button')) {
			$enabled = qa_post_text('github_app_enabled_field');
			qa_opt('github_app_enabled', empty($enabled) ? 0 : 1);
			qa_opt('github_app_id', qa_post_text('github_app_id_field'));
			qa_opt('github_app_secret', qa_post_text('github_app_secret_field'));
			$saved=true;
		}
		
		$ready=strlen(qa_opt('github_app_id')) && strlen(qa_opt('github_app_secret'));
		
		return array(
			'ok' => $saved ? 'Github application details saved' : null,
			
			'fields' => array(
				array(
					'type' => 'checkbox',
					'label' => 'Enable Github Login',
					'value' => qa_opt('github_app_enabled') ? true : false,
					'tags' => 'NAME="github_app_enabled_field"',
				),
				
				array(
					'label' => 'Github Client ID:',
					'value' => qa_html(qa_opt('github_app_id')),
					'tags' => 'NAME="github_app_id_field"',
				),

				array(
					'label' => 'Github Client Secret:',
					'value' => qa_html(qa_opt('github_app_secret')),
					'tags' => 'NAME="github_app_secret_field"',
					'error' => $ready ? null : 'To use Github Login, please <A HREF="https://github.com/settings/applications/new" TARGET="_blank">set up a Github application</A>.',
				),
			),
			
			'buttons' => array(
				array(
					'label' => 'Save Changes',
					'tags' => 'NAME="github_save_button"',
				),
			),
		);
	}
	
	function getGithub() {
		$app_id=qa_opt('github_app_id');
		$app_secret=qa_opt('github_app_secret');
		
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
		
		require_once $this->directory.'github.php';
		
		$github = new Github(array(
			'appId'  => $app_id,
			'secret' => $app_secret
		));
		
		return $github;
	}
}
/*
	Omit PHP closing tag to help avoid accidental output
*/