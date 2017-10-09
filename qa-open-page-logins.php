<?php
	
/*
	Question2Answer (c) Gideon Greenspan
	Open Login Plugin (c) Alex Lixandru

	http://www.question2answer.org/

	
	File: qa-plugin/open-login/qa-open-page-logins.php
	Version: 3.0.0
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

	function init_queries( $tableslc ) {
		// check if the plugin is initialized
		
		$ok = qa_opt('open_login_ok');
		if ( $ok == 3 ) {
			return null;
		}
		
		$queries = array();
		
		$columns=qa_db_read_all_values(qa_db_query_sub('describe ^userlogins'));
		if( !in_array('oemail', $columns ) )
		{
			$queries[] = 'ALTER TABLE ^userlogins ADD `oemail` VARCHAR( 80 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL';
		}
		if( !in_array('ohandle', $columns ) )
		{
			$queries[] = 'ALTER TABLE ^userlogins ADD `ohandle` VARCHAR( 80 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL';
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
		qa_opt('open_login_ok', '3');
		return null;
	}

	function match_request($request) {
		$parts=explode('/', $request);
		return (count($parts) == 1 && $parts[0]=='logins'); 
	}
	
	function process_request($request) {
		require_once QA_INCLUDE_DIR.'qa-db-users.php';
		require_once QA_INCLUDE_DIR.'qa-app-format.php';
		require_once QA_INCLUDE_DIR.'qa-app-users.php';
		require_once QA_INCLUDE_DIR.'qa-db-selects.php';
		require_once $this->directory . 'qa-open-utils.php';
		
		//	Check we're not using single-sign on integration, that we're logged in
		
		if (QA_FINAL_EXTERNAL_USERS)
			qa_fatal_error('User accounts are handled by external code');
		
		$userid = qa_get_logged_in_userid();
		if (!isset($userid)) {
			qa_redirect('login');
		}

		//	Get current information on user
		$useraccount = qa_db_user_find_by_id__open($userid);
		
		//  Check if settings were updated
		$this->check_settings();
		
		//  Check if we're unlinking an account
		$mylogins = $this->check_unlink($useraccount);
		
		//  Check if we need to associate another provider
		$tolink = $this->check_associate($useraccount);
		
		//  Check if we're merging multiple accounts
		$otherlogins = $this->check_merge($useraccount, $mylogins, $tolink);
		
		//	Prepare content for theme
		$disp_conf = qa_get('confirm') || !empty($tolink);
		$qa_content = qa_content_prepare();
		
		qa_set_template('logins');
		//  Build page
		if(!$disp_conf) { 
			// just visiting the regular page
			$qa_content['title'] = qa_lang_html('plugin_open/my_logins_title');
			$qa_content['navigation']['sub'] = qa_user_sub_navigation($useraccount['handle'], '', true);
			$qa_content['script_onloads'][]='$(function(){ window.setTimeout(function() { qa_conceal(".form-notification-ok"); }, 1500); });';
			
			$this->display_summary($qa_content, $useraccount);
			$this->display_logins($qa_content, $useraccount, $mylogins);
			$this->display_duplicates($qa_content, $useraccount, $otherlogins);
			$this->display_services($qa_content, !empty($mylogins) || !empty($otherlogins));
		
		} else {
			// logged in and there are duplicates
			$qa_content['title']= qa_lang_html('plugin_open/other_logins_conf_title');
			
			if(!$this->display_duplicates($qa_content, $useraccount, $otherlogins)) {
				$tourl = urldecode(qa_get('to'));
				if(!empty($tourl)) {
					qa_redirect_raw(qa_opt('site_url').$tourl);
				} else {
					if($tolink) {
						// unable to link the login
						$provider = ucfirst($tolink['source']);
						qa_redirect('logins', array('provider' => $provider, 'code' => 99));
						
					} else {
						// no merge to confirm
						qa_redirect('', array('provider' => '', 'code' => 98));
					}
				}
			}
		}
		
		
		return $qa_content;

	}
	
	/* *** Form processing functions *** */
	
	function check_settings() {
		if (qa_clicked('dosaveprofile')) {
			/* nothing here for now */
		}
	}
	
	function check_merge(&$useraccount, &$mylogins, $tolink) {
		global $qa_cached_logged_in_user, $qa_logged_in_userid_checked;
		
		$userid = $findid = $useraccount['userid'];
		$findemail = $useraccount['oemail']; // considering this is an openid user, so use the openid email
		if(empty($findemail)) {
			$findemail = $useraccount['email']; // fallback
		}
		
		if($tolink) {
			// user is logged in with $userid but wants to merge $findid
			$findemail = null;
			$findid = $tolink['userid'];
			
		} else if(qa_get('confirm') == 2 || qa_post_text('confirm') == 2) {
			// bogus confirm page, stop right here
			qa_redirect('logins');
		}
		
		// find other un-linked accounts with the same email
		$otherlogins = qa_db_user_login_find_other__open($findid, $findemail, $userid);
		
		if (qa_clicked('domerge') && !empty($otherlogins)) {
			// if cancel was requested, just redirect
			if($_POST['domerge'] == 0) {
				$tourl = urldecode(qa_post_text('to'));
				if(!empty($tourl)) {
					qa_redirect_raw(qa_opt('site_url').$tourl);
				} else {
					qa_redirect($tolink ? 'logins' : '');
				}
			}
			
			// a request to merge (link) multiple accounts was made
			require_once QA_INCLUDE_DIR.'qa-app-users-edit.php';
			$recompute = false;
			$email = null;
			$baseid = $_POST["base{$_POST['domerge']}"]; // POST[base1] or POST[base2]
			
			// see which account was selected, if any
			if($baseid != 0) // just in case
			foreach($otherlogins as $login) {
				// see if this is the currently logged in account
				$loginid = $login['details']['userid'];
				$is_current = $loginid == $userid;
				
				// see if this user was selected for merge
				if(isset($_POST["user_$loginid"]) || $is_current) {
					if($baseid != $loginid) {
						// this account should be deleted as it's different from the selected base id
						if(!empty($login['logins'])) {
							// update all associated logins
							qa_db_user_login_sync(true);
							qa_db_user_login_replace_userid__open($loginid, $baseid);
							qa_db_user_login_sync(false);
						}
						
						// delete old user but keep the email
						qa_delete_user($loginid);
						$recompute = true;
						if(empty($email)) $email = $login['details']['email'];
						if(empty($email)) $email = $login['details']['oemail'];
					}
				}
			}
			
			// recompute the stats, if needed
			if($recompute) {
				require_once QA_INCLUDE_DIR.'qa-db-points.php';
				qa_db_userpointscount_update();
				
				// check if the current account has been deleted
				if($userid != $baseid) {
					$oldsrc = $useraccount['sessionsource'];
					qa_set_logged_in_user($baseid, $useraccount['handle'], false, $oldsrc);
					$useraccount = qa_db_user_find_by_id__open($baseid);
					$userid = $baseid;
					
					// clear some cached data
					qa_db_flush_pending_result('loggedinuser');
					$qa_logged_in_userid_checked = false;
					unset($qa_cached_logged_in_user);
				}
				
				// also check the email address on the remaining user account
				if(empty($useraccount['email']) && !empty($email)) {
					// update the account if the email address is not used anymore
					$emailusers=qa_db_user_find_by_email($email);
					if (count($emailusers) == 0) {
						qa_db_user_set($userid, 'email', $email);
						$useraccount['email'] = $email; // to show on the page
					}
				}
			}
			
			$conf = qa_post_text('confirm');
			$tourl = qa_post_text('to');
			if($conf) {
				$tourl = urldecode(qa_post_text('to'));
				if(!empty($tourl)) {
					qa_redirect_raw(qa_opt('site_url').$tourl);
				} else {
					qa_redirect($tolink ? 'logins' : '');
				}
			}
			
			// update the arrays
			$otherlogins = qa_db_user_login_find_other__open($userid, $findemail);
			$mylogins = qa_db_user_login_find_mine__open($userid);
		}
		
		// remove the current user id
		unset($otherlogins[$userid]);
		
		return $otherlogins;
	}
	
	function check_unlink(&$useraccount) {
		$userid = $useraccount['userid'];
		
		//	Get more information on user, including accounts already linked 
		$mylogins = qa_db_user_login_find_mine__open($userid);

		if (qa_clicked('dosplit') && !empty($mylogins)) {
			// a request to split (un-link) some accounts was made
			$unlink = $_POST['dosplit'];
			foreach($mylogins as $login) {
				// see which account was selected, if any
				$key = "{$login['source']}_" . md5($login['identifier']);
				if($key == $unlink) {
					// account found, but don't unlink if currently in use
					if($useraccount['sessionsource'] != qa_open_login_get_new_source($login['source'], $login['identifier'])) {
						// ok, we need to delete this one
						qa_db_user_login_sync(true);
						qa_db_user_login_delete__open($login['source'], $login['identifier'], $userid);
						qa_db_user_login_sync(false);
					}
				}
			}
			
			// update the array
			$mylogins = qa_db_user_login_find_mine__open($userid);
		}
		
		return $mylogins;
	}
	
	function check_associate($useraccount) {
		$userid = $useraccount['userid'];
		$action = null;
		$key = null;
		
		if( !empty($_REQUEST['hauth_start']) ) {
			$key = trim(strip_tags($_REQUEST['hauth_start']));
			$action = 'process';
			
		} else if( !empty($_REQUEST['hauth_done']) ) {
			$key = trim(strip_tags($_REQUEST['hauth_done']));
			$action = 'process';
			
		} else if( !empty($_GET['link']) ) {
			$key = trim(strip_tags($_GET['link']));
			$action = 'login';
		}
		
		if($key == null) {
			return false;
		}
		
		$provider = $this->get_ha_provider($key);
		$source = strtolower($provider);
		
		if($action == 'login') {
			// handle the login

			// after login come back to the same page
			$loginCallback = qa_path('', array(), qa_opt('site_url'));
			
			require_once( $this->directory . 'Hybrid/Auth.php' );
			require_once( $this->directory . 'qa-open-utils.php' );
			
			// prepare the configuration of HybridAuth
			$config = $this->get_ha_config($provider, $loginCallback);
			
			try {
				// try to login
				$hybridauth = new Hybrid_Auth( $config );
				$adapter = $hybridauth->authenticate( $provider );
				
				// if ok, create/refresh the user account
				$user = $adapter->getUserProfile();
				
				$duplicates = 0;
				if (!empty($user))
					// prepare some data
					$ohandle = null;
					$oemail = null;
					
					if(empty($user->displayName)) {
						$ohandle = $provider;
					} else {
						$ohandle = preg_replace('/[\\@\\+\\/]/', ' ', $user->displayName);
					}
					if (strlen(@$user->email) && $user->emailVerified) { // only if email is confirmed
						$oemail = $user->email;
					}
					
					$duplicate = qa_db_user_login_find_duplicate__open($source, $user->identifier);
					if( $duplicate == null ) {
						// simply create a new login
						qa_db_user_login_sync(true);
						qa_db_user_login_add($userid, $source, $user->identifier);
						if($oemail) qa_db_user_login_set__open($source, $user->identifier, 'oemail', $oemail);
						qa_db_user_login_set__open($source, $user->identifier, 'ohandle', $ohandle);
						qa_db_user_login_sync(false);
					
						// now that everything was added, log out to allow for multiple accounts
						$adapter->logout();
						
						// redirect to get rid of parameters
						qa_redirect('logins');
						
					} else if($duplicate['userid'] == $userid) {
						// trying to add the same account, just update the email/handle
						qa_db_user_login_sync(true);
						if($oemail) qa_db_user_login_set__open($source, $user->identifier, 'oemail', $oemail);
						qa_db_user_login_set__open($source, $user->identifier, 'ohandle', $ohandle);
						qa_db_user_login_sync(false);
						
						// log out to allow for multiple accounts
						$adapter->logout();
						
						// redirect to get rid of parameters
						qa_redirect('logins');
						
					} else {
						if(qa_get('confirm') == 2) {
							return $duplicate;
						} else {
							qa_redirect('logins', array('link' => qa_get('link'), 'confirm' => 2));
						}
					}
					
			} catch(Exception $e) {
				qa_redirect('logins', array('provider' => $provider, 'code' => $e->getCode()));
			}
		}
		
		if($action == 'process') {
			require_once( "Hybrid/Auth.php" );
			require_once( "Hybrid/Endpoint.php" ); 
			Hybrid_Endpoint::process();
		}
		
		return false;
	}
	
	/* *** Display functions *** */
	
	function display_summary(&$qa_content, $useraccount) {
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
			),
			
			'hidden' => array(
				'dosaveprofile' => '0'
			),

		);
		
		if (qa_get_state()=='profile-saved') {
			$qa_content['form_profile']['ok']=qa_lang_html('users/profile_saved');
		}
	}
	
	function display_logins(&$qa_content, $useraccount, $mylogins) {
		if(!empty($mylogins)) {
			require_once $this->directory . 'qa-open-login.php';
			
			// display the logins already linked to this user account
			$qa_content['custom_mylogins']='<h2>' . qa_lang_html('plugin_open/associated_logins') . '</h2><p>' . qa_lang_html('plugin_open/split_accounts_note') . '</p>';
			$qa_content['form_mylogins']=array(
				'tags' => 'ENCTYPE="multipart/form-data" METHOD="POST" ACTION="'.qa_self_html().'" CLASS="open-login-accounts"',
				'style' => 'wide',
				'hidden' => array(
					'dosplit' => '1',
				),
			);
			
			$data = array();
			foreach($mylogins as $i => $login) {
				$del_html = '';
				
				$s = qa_open_login_get_new_source($login['source'], $login['identifier']);
				if($useraccount['sessionsource'] != $s) {
					$del_html = '<a href="javascript://" onclick="OP_unlink(\'' . $login['source'] . '_' . md5($login['identifier']) . '\')" class="opacxdel qa-form-light-button-reject" title="'. qa_lang_html('plugin_open/unlink_this_account') .'">&nbsp;</a>';
				}
				
				$data["f$i"] = array(
					'label' => qa_open_login::printCode(ucfirst($login['source']), empty($login['ohandle']) ? ucfirst($login['source']) : $login['ohandle'], 'menu', 'view', false) . $del_html,
					'type' => 'static',
					'style' => 'tall'
				);
			}
			$qa_content['form_mylogins']['fields'] = $data;
			$qa_content['customscriptu'] = '<script type="text/javascript">
				function OP_unlink(id) {
					$(".qa-main form.open-login-accounts>input[name=dosplit]").attr("value", id);
					$(".qa-main form.open-login-accounts").submit();
				}
			</script>';
		}
	}

	function display_duplicates(&$qa_content, $useraccount, $otherlogins) {
		$userid = $useraccount['userid'];
		$disp_conf = qa_get('confirm');
		
		if(!empty($otherlogins)) {
			// display other logins which could be linked to this user account
			if($disp_conf) {
				$title = '';
				$p = '<br />' . ($disp_conf == 1 ? qa_lang_html('plugin_open/other_logins_conf_text') : qa_lang_html_sub('plugin_open/link_exists', '<strong>' . ucfirst(qa_get('link')) . '</strong>') );
			} else {
				$title = '<h2>' . qa_lang_html('plugin_open/other_logins') . '</h2>';
				$p = qa_lang_html('plugin_open/other_logins_conf_text');
			}
			
			$qa_content['custom_merge']="$title <p>$p</p>";
			$qa_content['form_merge']=array(
				'tags' => 'ENCTYPE="multipart/form-data" METHOD="POST" ACTION="'.qa_self_html().'" CLASS="open-login-others"',
				'style' => 'wide',
				'buttons' => array(
					'save' => array(
						'tags' => 'id="open_login_submit" onClick="qa_show_waiting_after(this, false);" style="display: none"',
						'note' => qa_lang_html('plugin_open/action_info_1'),
						'label' => qa_lang_html('plugin_open/continue'),
					),
				),
				'hidden' => array(
					'domerge' => '1',
					'confirm' => $disp_conf,
					'to' => qa_get('to'),
				),
			);
			
			$data = array(); 
			$select = '<option value="0">' . qa_lang_html('plugin_open/select_base') . '</option>' .
				'<option value="'. $userid . '" title="' . qa_html($useraccount['handle']) .'">' . qa_html($useraccount['handle']) . ' (' . $useraccount['points'] . ' ' . qa_lang_html('admin/points') . ' - ' . qa_lang_html('plugin_open/current_account') . ')</option>';
			foreach($otherlogins as $i => $login) {
				$type = 'login';
				$name = qa_html($login['details']['handle']);
				$points = $login['details']['points'];
				
				if(count($login['logins'])==0) { // this is a regular site login, not an openid login
					$type = 'user';
				}
				$login_providers = ($type == 'user' ? strtolower(qa_lang_html('plugin_open/password')) : '<strong>' . implode(', ', $login['logins']) . '</strong>' );
				
				$data["f$i"] = array(
					'label' => '<strong>' . $name . '</strong> (' . $points . ' ' . qa_lang_html('admin/points') . ', ' . 
						strtolower(qa_lang_html_sub('plugin_open/login_using', '')) . $login_providers . ')',
					'tags' => 'name="user_' . $login['details']['userid'] . '" value="' . $login['details']['userid'] . '" style="visibility: hidden" checked="checked" rel="' . $i . '" onchange="OP_checkClicked(this)"',
					'type' => 'checkbox',
					'style' => 'tall'
				);
				$select .= '<option value="' . $login['details']['userid'] . '" title="' . $name .'">' . $name . ' (' . $points . ' ' . qa_lang_html('admin/points') . ')</option>';
			}
			$data['space'] = array(
				'label' => '<br>' . qa_lang_html('plugin_open/choose_action'),
				'type' => 'static',
				'style' => 'tall'
			);
			
			$ac1html = '<div class="opacxhtml qa-form-tall-buttons" id="ac1html" style="display: none;">' . 
						qa_lang_html('plugin_open/merge_all_first') . ' ' . qa_lang_html('plugin_open/merge_note') . '<br /><br />' .
						qa_lang_html('plugin_open/select_base_note') . '<br /><select name="base1" onchange="OP_baseSelected(this)">' . $select . '</select></div>';
			$ac2html = '<div class="opacxhtml qa-form-tall-buttons" id="ac2html" style="display: none;">' .
						qa_lang_html('plugin_open/select_merge_first') . ' ' . qa_lang_html('plugin_open/merge_note') . '<br /><br />' .
						qa_lang_html('plugin_open/select_base_note') . '<br /><select name="base2" onchange="OP_baseSelected(this)">' . $select . '</select></div>';
			$ac3html = '<div class="opacxhtml qa-form-tall-buttons" id="ac3html" style="display: none;">' . qa_lang_html('plugin_open/cancel_merge_note') . '</div>';
			
			if($disp_conf == null || $disp_conf == 1) {
				$data['actions1'] = array(
					'label' => ' &nbsp; &nbsp; <strong><a href="javascript:;" onclick="OP_actionSelected(1, \'#ac1html\')">&raquo; ' . qa_lang_html('plugin_open/merge_all') . '</a></strong>' . $ac1html,
					'type' => 'static',
					'style' => 'tall'
				);
				$data['actions2'] = array(
					'label' => ' &nbsp; &nbsp; <strong><a href="javascript:;" onclick="OP_actionSelected(2, \'#ac2html\')">&raquo; ' . qa_lang_html('plugin_open/select_merge') . '</a></strong>' . $ac2html,
					'type' => 'static',
					'style' => 'tall'
				);
				if($disp_conf == 1) {
					$data['actions3'] = array(
						'label' => ' &nbsp; &nbsp; <strong><a href="javascript:;" onclick="OP_actionSelected(0, \'#ac3html\')">&raquo; ' . qa_lang_html('plugin_open/cancel_merge') . '</a></strong>' . $ac3html,
						'type' => 'static',
						'style' => 'tall'
					);
				}
			} else if($disp_conf == 2) {
				$data['actions1'] = array(
					'label' => ' &nbsp; &nbsp; <strong><a href="javascript:;" onclick="OP_actionSelected(1, \'#ac1html\')">&raquo; ' . qa_lang_html('plugin_open/link_all') . '</a></strong>' . $ac1html,
					'type' => 'static',
					'style' => 'tall'
				);
				$data['actions3'] = array(
					'label' => ' &nbsp; &nbsp; <strong><a href="javascript:;" onclick="OP_actionSelected(0, \'#ac3html\')">&raquo; ' . qa_lang_html('plugin_open/cancel_link') . '</a></strong>' . $ac3html,
					'type' => 'static',
					'style' => 'tall'
				);
			}
			$qa_content['form_merge']['fields'] = $data;
			$qa_content['customscript'] = '<script type="text/javascript">
				function OP_actionSelected(i, divid) {
					$(".opacxhtml").slideUp();
					if(i != 2 || op_last_action == 2) {
						$(".qa-main form.open-login-others input[type=checkbox]").css("visibility", "hidden");
					}
					
					if(op_last_action == i) {
						OP_baseSelected();
						op_last_action = -1;
						return;
					}
					op_last_action = i;
					$(divid).slideDown();
					$(".qa-main form.open-login-others>input[name=domerge]").attr("value", i);
					
					if(i > 0) {
						$(".qa-main form.open-login-others input[type=checkbox]").attr("checked", "checked");
						if(i == 2) {
							$(".qa-main form.open-login-others input[type=checkbox]").css("visibility", "visible");
							$(".qa-main form.open-login-others select[name=base2]").html( $(".qa-main form.open-login-others select[name=base1]").html() );
						}
						sel = $(".qa-main form.open-login-others select[name=base" + i +"]").get(0);
						sel.selectedIndex = 0;
						OP_baseSelected(sel);
					} else {
						$(".qa-main form.open-login-others span.qa-form-wide-note").html("' . qa_lang_html('plugin_open/action_info_2') . '"); 
						$("#open_login_submit").show(); 
						$("#open_login_submit").attr("disabled", false);
					}
				}
				
				function OP_baseSelected(sel) {
					if(!sel || sel.selectedIndex == 0) {
						if(sel) {
							$(".qa-main form.open-login-others span.qa-form-wide-note").html("' . qa_lang_html('plugin_open/action_info_3') . '")
						} else {
							$(".qa-main form.open-login-others span.qa-form-wide-note").html("' . qa_lang_html('plugin_open/action_info_1') . '")
						}
						$("#open_login_submit").hide()
						$("#open_login_submit").attr("disabled", "disabled")
					} else {
						if(OP_accValid()) {
							nam = $("option:selected", sel).attr("title")
							$(".qa-main form.open-login-others span.qa-form-wide-note").html("<strong>" + nam + "</strong> '  . qa_lang_html('plugin_open/action_info_4') . '")
							$("#open_login_submit").show()
							$("#open_login_submit").attr("disabled", false)
						}
					}
				}
				
				function OP_checkClicked(check) {
					' . ($disp_conf == 2 ? '/* empty */' : '
					var rel = $(check).attr("rel")
					var id = $(check).attr("value")
					var chk = $(check).attr("checked")
					$(".qa-main form.open-login-others select[name=base2]").get(0).selectedIndex = 0
					if(chk) {
						$(".qa-main form.open-login-others select[name=base2] option[value=" + id + "]").show()
					} else {
						$(".qa-main form.open-login-others select[name=base2] option[value=" + id + "]").hide()
					}
					OP_accValid() ') . '
				}
				
				function OP_accValid() {
					if(op_last_action != 2) {
						$(".qa-main form.open-login-others input[type=checkbox]").attr("checked", "checked")
						return true
					}
					
					someSel = false 
					$(".qa-main form.open-login-others input[type=checkbox]").each(function(i, o) { 
						someSel = someSel || $(o).attr("checked") == "checked"
					});
					
					$("#open_login_submit").hide();
					$("#open_login_submit").attr("disabled", "disabled");
					if(!someSel) { // nothing selected
						$(".qa-main form.open-login-others span.qa-form-wide-note").html("' . qa_lang_html('plugin_open/action_info_5') . '");
						return false;
					} else {
						$(".qa-main form.open-login-others span.qa-form-wide-note").html("' . qa_lang_html('plugin_open/action_info_3') . '");
						return true;
					}
				}
			</script>';
			$qa_content['script_onloads'][]='$(function(){ OP_baseSelected() });';
			$qa_content['script_var']['op_last_action'] = -1;
			return true;
		}
		return false;
	}
	
	function display_services(&$qa_content, $has_content) {
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
		} else {
			$qa_content['custom'] = '<h2>' . qa_lang_html('plugin_open/link_with_account') . '</h2><p>' . qa_lang_html('plugin_open/no_logins_text') . '</p>';
		}
	
		// output login providers
		$loginmodules=qa_load_modules_with('login', 'printCode');
		
		foreach ($loginmodules as $module) {
			ob_start();
			qa_open_login::printCode($module->provider, null, 'associate', 'link');
			$html=ob_get_clean();
			
			if (strlen($html))
				@$qa_content['custom'].= $html.' ';
		}
	}
	
	/* *** Utility functions *** */
	
	function get_ha_config($provider, $url) {
		$key = strtolower($provider);
		return array(
			'base_url' => $url, 
			'providers' => array ( 
				$provider => array (
					'enabled' => true,
					'keys' => array(
						'id' => qa_opt("{$key}_app_id"), 
						'key' => qa_opt("{$key}_app_id"), 
						'secret' => qa_opt("{$key}_app_secret")
					),
					'scope' => $provider == 'Facebook' ? 'email,user_about_me,user_location,user_website' : null,
				)
			),
			'debug_mode' => false,
			'debug_file' => ''
		);
	}
	
	function get_ha_provider($key) {
		$providers = @include dirname(__FILE__) . DIRECTORY_SEPARATOR . 'providers.php';
		if ($providers) {
			// loop through all active providers and register them
			$providerList = explode(',', $providers);
			foreach($providerList as $provider) {
				if(strcasecmp($key, $provider) == 0) {
					return $provider;
				}
			}
		}
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
			
			$nologin = qa_post_text('open_login_hideform');
			qa_opt('open_login_hideform', empty($nologin) ? 0 : 1);
			
			$remember = qa_post_text('open_login_remember');
			qa_opt('open_login_remember', empty($remember) ? 0 : 1);
			
			$showbuttons = qa_post_text('open_login_hide_buttons');
			qa_opt('open_login_hide_buttons', empty($showbuttons) ? 0 : 1);
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
					'label' => 'Use <a href="http://zocial.smcllns.com/">Zocial buttons</a> (works out-of-the-box with inlined CSS; if "Don\'t inline CSS" checkbox is selected, the custom theme must be manually modified to import <i>zocial.css</i> file)',
					'value' => qa_opt('open_login_zocial') ? true : false,
					'tags' => 'NAME="open_login_zocial"',
				),
				array(
					'type' => 'checkbox',
					'label' => 'Hide regular login/register forms and keep only external login buttons (might require theme changes)',
					'value' => qa_opt('open_login_hideform') ? true : false,
					'tags' => 'NAME="open_login_hideform"',
				),
				array(
					'type' => 'checkbox',
					'label' => 'Keep users logged in when they connect through external login providers (this will log users in automatically when they return to the site, even if they close their browsers)',
					'value' => qa_opt('open_login_remember') ? true : false,
					'tags' => 'NAME="open_login_remember"',
				),
				array(
					'type' => 'checkbox',
					'label' => qa_lang_html('plugin_open/show_login_button'),
					'value' => qa_opt('open_login_hide_buttons') ? true : false,
					'tags' => 'NAME="open_login_hide_buttons"',
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
			if($provider == 'Yahoo') {
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
				'label' => 'Callback URL/Redirect URL (to use when registering your application with ' . $provider . '): <br /><strong>' . 
							qa_opt('site_url') . '?hauth.done=' . $provider . '</strong>',
			);
			
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
