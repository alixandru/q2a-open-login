<?php
	
/*
	Question2Answer (c) Gideon Greenspan
	Open Login Plugin (c) Alex Lixandru

	http://www.question2answer.org/

	
	File: qa-plugin/open-login/qa-open-page-logins.php
	Version: 2.0.0
	Description: Implements the business logic for the plugin custom page


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


class qa_open_logins_page {
	var $directory;
	var $urltoroot;

	function load_module($directory, $urltoroot) {
		$this->directory=$directory;
		$this->urltoroot=$urltoroot;
	}

	function init_queries( $tableslc )
	{
		// check if the plugin is initialized
		
		$ok = qa_opt('open_login_ok');
		if ( $ok == 1 ) {
			return null;
		}
		
		$queries = array();
		
		$columns=qa_db_read_all_values(qa_db_query_sub('describe ^userlogins'));
		if( !in_array('oemail', $columns ) )
		{
			$queries[] = 'ALTER TABLE ^userlogins ADD `oemail` VARCHAR( 80 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL';
		}

		$columns=qa_db_read_all_values(qa_db_query_sub('describe ^users'));
		if( !in_array('oemail', $columns ) )
		{
			$queries[] = 'ALTER TABLE ^users ADD `oemail` VARCHAR( 80 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL';
		}
		
		if(count($queries)) {
			return $queries;
		}
		
		// we're already set up
		qa_opt('open_login_ok', '1');
		return null;
	}

	function match_request($request)
	{
		$parts=explode('/', $request);
		return count($parts) == 1 && $parts[0]=='logins';
	}
	
	function process_request($request)
	{
		require_once QA_INCLUDE_DIR.'qa-db-users.php';
		require_once QA_INCLUDE_DIR.'qa-app-format.php';
		require_once QA_INCLUDE_DIR.'qa-app-users.php';
		require_once QA_INCLUDE_DIR.'qa-db-selects.php';
		require_once $this->directory . 'qa-open-utils.php';
		
		//	Check we're not using single-sign on integration, that we're logged in
		
		if (QA_FINAL_EXTERNAL_USERS)
			qa_fatal_error('User accounts are handled by external code');
		
		$userid=qa_get_logged_in_userid();
		
		if (!isset($userid))
			qa_redirect('login');
			

		//	Get current information on user
		$useraccount = qa_db_user_find_by_id__open($userid);
		
		$findemail = $useraccount['oemail']; // considering this is an openid user, so use the openid email
		if(empty($findemail)) {
			$findemail = $useraccount['email']; // fallback
		}
		
		// find other un-linked accounts with the same email
		$otherlogins = qa_db_user_login_find_other__open($userid, $findemail);
			
		if (qa_clicked('dosaveprofile')) {
			qa_opt('open_login_remember', qa_post_text('remember') ? '1' : '0');
			qa_redirect('logins', array('state' => 'profile-saved'));
		}
		
		if (qa_clicked('docancel')) {
			$tourl = qa_post_text('to');
			if(!empty($tourl)) {
				qa_redirect($tourl);
			}
		}
		
		if (qa_clicked('domerge') && !empty($otherlogins)) {
			// a request to merge (link) multiple accounts was made
			require_once QA_INCLUDE_DIR.'qa-app-users-edit.php';
			$recompute = false;
			
			// see which account was selected, if any
			foreach($otherlogins as $login) {
				// see if this openid login was checked for merge
				$key = "login_{$login['source']}_" . md5($login['identifier']);
				$value = qa_post_text($key);
				if(!empty($value)) {
					// ok, we need to merge this one and delete the old user
					$olduserid = $login['userid'];
					
					// update login
					qa_db_user_login_sync(true);
					qa_db_user_login_set__open($login['source'], $login['identifier'], 'userid', $userid);
					qa_db_user_login_sync(false);
					
					// delete old user if no other connections to it exist
					$other_logins_for_user = qa_db_user_login_find_mine__open($olduserid, qa_open_login_get_new_source($login['source'], $login['identifier']));
					if(empty($other_logins_for_user)) {
						// safe to delete user profile
						qa_delete_user($olduserid);
						$recompute = true;
					}
					
				} else {
					// see if a regular QA user was checked for merge
					$key = "user_{$login['userid']}_" . md5($login['userid']);
					$value = qa_post_text($key);
					if(!empty($value)) {
						// we'll simply delete the selected user
						qa_delete_user($login['userid']);
						$recompute = true;
					}
				}
			}
			
			// recompute the stats, if needed
			if($recompute) {
				require_once QA_INCLUDE_DIR.'qa-db-points.php';
				qa_db_userpointscount_update();
			}
			
			$conf = qa_post_text('confirm');
			$tourl = qa_post_text('to');
			if($conf && !empty($tourl)) {
				qa_redirect($tourl);
			}
			
			// update the array
			$otherlogins = qa_db_user_login_find_other__open($userid, $findemail);
			
		}
		
		//	Get more information on user, including accounts already linked 
		$mylogins = qa_db_user_login_find_mine__open($userid, $useraccount['sessionsource']);
		
		if (qa_clicked('dosplit') && !empty($mylogins)) {
			// a request to split (un-link) some accounts was made
			foreach($mylogins as $login) {
				// see which account was selected, if any
				$key = "login_{$login['source']}_" . md5($login['identifier']);
				$value = qa_post_text($key);
				if(!empty($value)) {
					// ok, we need to delete this one
					$olduserid = $login['userid'];
					
					// delete login
					qa_db_user_login_sync(true);
					qa_db_user_login_delete__open($login['source'], $login['identifier'], $userid);
					qa_db_user_login_sync(false);
				}
			}
			
			// update the array
			$mylogins = qa_db_user_login_find_mine__open($userid, $useraccount['sessionsource']);
		}

		
		//	Prepare content for theme
		$qa_content=qa_content_prepare();
		$qa_content['title']=qa_lang_html('plugin_open/my_logins_title');
		
		$disp_conf = qa_get('confirm');
		if(!$disp_conf) {
			// display some summary about the user
			$qa_content['form_profile']=array(
				'title' => qa_lang_html('plugin_open/my_current_user'),
				'tags' => 'ENCTYPE="multipart/form-data" METHOD="POST" ACTION="'.qa_self_html().'" CLASS="open-login-profile"',
				'style' => 'wide',
				'fields' => array(
					'handle' => array(
						'label' => qa_lang_html('users/handle_label'),
						'value' => qa_html($useraccount['handle']),
						'type' => 'static',
					),
					
					'email' => array(
						'label' => qa_lang_html('users/email_label'),
						'value' => qa_html($useraccount['email']),
						'type' => 'static',
					),
					
					'remember' => array(
						'type' => 'checkbox',
						'label' => qa_lang_html('users/remember_label'),
						'note' => qa_lang_html('plugin_open/remember_me'),
						'tags' => 'NAME="remember"',
						'value' => qa_opt('open_login_remember') ? true : false,
					),
				),
				
				'buttons' => array(
					'save' => array(
						'tags' => 'onClick="qa_show_waiting_after(this, false);"',
						'label' => qa_lang_html('users/save_profile'),
					),
				),
				
				'hidden' => array(
					'dosaveprofile' => '1'
				),

			);
			
			if (qa_get_state()=='profile-saved') {
				$qa_content['form_profile']['ok']=qa_lang_html('users/profile_saved');
			}
			
			$has_content = false;
			if(!empty($mylogins)) {
				// display the logins already linked to this user account
				$qa_content['form_mylogins']=array(
					'title' => qa_lang_html('plugin_open/associated_logins'),
					'tags' => 'ENCTYPE="multipart/form-data" METHOD="POST" ACTION="'.qa_self_html().'" CLASS="open-login-accounts"',
					'style' => 'wide',
					'fields' => array(),
					'buttons' => array(
						'cancel' => array(
							'tags' => 'onClick="qa_show_waiting_after(this, false);"',
							'label' => qa_lang_html('plugin_open/split_accounts'),
							'note' => '<small>' . qa_lang_html('plugin_open/split_accounts_note') . '</small>',
						),
					),
					'hidden' => array(
						'dosplit' => '1',
					),
				);
				
				$data = array();
				foreach($mylogins as $i => $login) {
					$email = $login['oemail'] ? '(' . qa_html($login['oemail']) . ')' : '';
					$data["f$i"] = array(
						'label' => '<strong>' . ucfirst($login['source']) . '</strong> ' . $email,
						'tags' => 'NAME="login_' . $login['source'] . '_' . md5($login['identifier']) . '"',
						'type' => 'checkbox',
						'style' => 'tall'
					);
				}
				$qa_content['form_mylogins']['fields'] = $data;
				$has_content = true;
			}
		}
		
		
		if(!empty($otherlogins)) {
			// display other logins which could be linked to this user account
			$qa_content['form_merge']=array(
				'title' => $disp_conf ? qa_lang_html('plugin_open/other_logins_conf_title') : qa_lang_html('plugin_open/other_logins'),
				'tags' => 'ENCTYPE="multipart/form-data" METHOD="POST" ACTION="'.qa_self_html().'" CLASS="open-login-others"',
				'style' => 'wide',
				'note' => $disp_conf ? qa_lang_html('plugin_open/other_logins_conf_text'): null,
				'fields' => array(),
				'buttons' => array(
					'save' => array(
						'tags' => 'onClick="qa_show_waiting_after(this, false);"',
						'label' => qa_lang_html('plugin_open/merge_accounts'),
					),
				),
				'hidden' => array(
					'domerge' => '1',
					'confirm' => $disp_conf,
					'to' => qa_get('to'),
				),
			);
			
			$data = array(); 
			foreach($otherlogins as $i => $login) {
				$type = 'login';
				$name = ucfirst($login['source']);
				$email = $login['uloemail'] ? '(' . qa_html($login['uloemail']) . ')' : '';
				
				if(!$login['source']) { // this is a regular site login, not an openid login
					$type = 'user';
					$name = qa_lang_html('plugin_open/local_user');
					$email = '(' . $login['handle'] . ')';
					$login['source'] = $login['userid'];
					$login['identifier'] = $login['userid'];
				}
				
				$data["f$i"] = array(
					'label' => '<strong>' . $name . '</strong> ' . $email,
					'tags' => 'NAME="' . $type . '_' . $login['source'] . '_' . md5($login['identifier']) . '"',
					'type' => 'checkbox',
					'style' => 'tall'
				);
			}
			$qa_content['form_merge']['fields'] = $data;
			$has_content = true;
			
			// add a note to the Save button
			if($disp_conf) { 
				// confirmations are displayed only after logging in
				$qa_content['form_merge']['buttons']['cancel'] = array(
					'tags' => 'NAME="docancel"',
					'label' => qa_lang_html('main/cancel_button'),
					'note' => '<small>' . qa_lang_html('plugin_open/merge_accounts_note') . '</small>',
				);
			} else {
				// when accessing the logins page, no confirmation is displayed
				$qa_content['form_merge']['buttons']['save']['note'] = 
					'<small>' . qa_lang_html('plugin_open/merge_accounts_note') . '</small>';
			}
			
		} else if($disp_conf) {
			qa_redirect(qa_get('to'));
		}
		
		if(!$has_content) {
			// no linked logins
			$qa_content['form_nodata']=array(
				'title' => '<br>' . qa_lang_html('plugin_open/no_logins_title'),
				'style' => 'light',
				'fields' => array(
					'note' => array(
						'note' => qa_lang_html('plugin_open/no_logins_text'),
						'type' => 'static'
					)
				),
			);
		}
		
		$qa_content['navigation']['sub']=qa_account_sub_navigation();
		$qa_content['script_onloads'][]='$(function(){ window.setTimeout(function() { qa_conceal(".form-notification-ok"); }, 1500); });';
		
		return $qa_content;

	}
	
	
	function admin_form() {
		$saved=false;
		
		if (qa_clicked('general_save_button')) {
			
			// loop through all providers and see which one was enabled
			$allProviders = scandir( $this->directory . 'Hybrid' . DIRECTORY_SEPARATOR . 'Providers' );
			
			$activeProviders = array();
			foreach($allProviders as $providerFile) {
				if(substr($providerFile,0,1) == '.') {
					continue;
				}

				$provider = str_ireplace('.php', '', $providerFile);
				$key = strtolower($provider);

				$enabled = qa_post_text("{$key}_app_enabled_field");
				$shortcut = qa_post_text("{$key}_app_shortcut_field");
				qa_opt("{$key}_app_enabled", empty($enabled) ? 0 : 1);
				qa_opt("{$key}_app_shortcut", empty($shortcut) ? 0 : 1);
				qa_opt("{$key}_app_id", qa_post_text("{$key}_app_id_field"));
				qa_opt("{$key}_app_secret", qa_post_text("{$key}_app_secret_field"));
				
				if(!empty($enabled)) {
					$activeProviders[] = $provider;
				}
			}
			
			// at the end save a list of all active providers
			file_put_contents( $this->directory . 'providers.php', 
				'<' . '?' . 'php return "' . implode(',', $activeProviders) . '" ?' . '>'
			);
			
			// also save the other configurations
			$hidecss = qa_post_text('open_login_css');
			qa_opt('open_login_css', empty($hidecss) ? 0 : 1);
			
			$zocial = qa_post_text('open_login_zocial');
			qa_opt('open_login_zocial', empty($zocial) ? 0 : 1);
			$saved=true;
		}
		
		$form = array(
			'ok' => $saved ? 'Open Login preferences saved' : null,
			
			'fields' => array(
				array(
					'type' => 'checkbox',
					'label' => 'Don\'t inline CSS. I included the styles in my theme\'s CSS file',
					'value' => qa_opt('open_login_css') ? true : false,
					'tags' => 'NAME="open_login_css"',
				),
				
				array(
					'type' => 'checkbox',
					'label' => 'Use <a href="http://zocial.smcllns.com/">Zocial buttons</a> (works with inlined CSS; must be included manually otherwise)',
					'value' => qa_opt('open_login_zocial') ? true : false,
					'tags' => 'NAME="open_login_zocial"',
				),
				
				array(
					'type' => 'static',
					'label' => '<br /><strong>Available login providers</strong>',
				),
			),
			
			'buttons' => array(
				array(
					'label' => 'Save Changes',
					'tags' => 'NAME="general_save_button"',
				),
			),
		);
		
		
		$allProviders = scandir( $this->directory . 'Hybrid' . DIRECTORY_SEPARATOR . 'Providers' );
		
		foreach($allProviders as $providerFile) {
			if(substr($providerFile,0,1) == '.' || $providerFile == 'OpenID.php') {
				continue;
			}
			
			$provider = str_ireplace('.php', '', $providerFile);
			$key = strtolower($provider);
			
			$form['fields'][] = array(
				'type' => 'checkbox',
				'label' => 'Enable ' . $provider,
				'value' => qa_opt("{$key}_app_enabled") ? true : false,
				'tags' => "NAME=\"{$key}_app_enabled_field\"",
			);
			
			$form['fields'][] = array(
				'type' => 'checkbox',
				'label' => 'Show ' . $provider . ' button in the header',
				'value' => qa_opt("{$key}_app_shortcut") ? true : false,
				'tags' => "NAME=\"{$key}_app_shortcut_field\"",
			);
			
			$form['fields'][] = array(
				'label' => $provider . ' App ID:',
				'value' => qa_html(qa_opt("{$key}_app_id")),
				'tags' => "NAME=\"{$key}_app_id_field\"",
			);

			$form['fields'][] = array(
				'label' => $provider . ' App Secret:',
				'value' => qa_html(qa_opt("{$key}_app_secret")),
				'tags' => "NAME=\"{$key}_app_secret_field\"",
			);
			
			$docUrl = "http://hybridauth.sourceforge.net/userguide/IDProvider_info_{$provider}.html";
			if($provider == 'Google' || $provider == 'Yahoo') {
				$form['fields'][] = array(
					'type' => 'static',
					'label' => 'By default, <strong>' . $provider . '</strong> uses OpenID and does not need any keys, so these fields should ' .
								'be left blank. However, if you replaced the provider file with the one that uses OAuth, and not OpenID, you ' .
								'need to provide the app keys. In this case, click on <a href="' . $docUrl . '" target="_blank">' . $docUrl . '</a> ' .
								'for information on how to get them.',
				);
				
			} else {
				$form['fields'][] = array(
					'type' => 'static',
					'label' => 'For information on how to setup your application with <strong>' . $provider . '</strong> ' .
								'see the <strong>Registering application</strong> section from <a href="' . $docUrl . '" target="_blank">' . $docUrl . '</a>.',
				);
			}
			
			$form['fields'][] = array(
				'type' => 'static',
				'label' => '&nbsp;',
			);
		}
		
		return $form;

	}
	
}

/*
	Omit PHP closing tag to help avoid accidental output
*/
