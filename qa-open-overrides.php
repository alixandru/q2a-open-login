<?php

/*
	Question2Answer (c) Gideon Greenspan
	Open Login Plugin (c) Alex Lixandru

	http://www.question2answer.org/

	
	File: qa-plugin/open-login/qa-open-overrides.php
	Version: 3.0.0
	Description: Overrides the core login functionality


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



/**
 * Extends the logout functionality by performing some extra clean up for
 * the external login modules.
 *
 * Currently only the login modules using OAuth (like Facebook and Github)
 * need additional logout processing (defined in their do_logout methods). 
 * The OpenID-based login modules will simply use the default functionality.
 *
 * Only logout functionality is affected by this function. Login mechanism
 * is handled by #qa_log_in_external_user function.
 */
function qa_set_logged_in_user($userid, $handle='', $remember=false, $source=null)
{
	// if a logout was requested, do extra stuff
	if (!isset($userid)) {
		// get all modules which have a custom logout logic
		$loginmodules=qa_load_modules_with('login', 'do_logout');

		// do the work
		foreach ($loginmodules as $module) {
			$module->do_logout();
		}
	}
	
	// then just perform the default tasks
	qa_set_logged_in_user_base($userid, $handle, $remember, $source);
}



/**
 * Overrides the default mechanism of logging in from external sources.
 *
 * Adds a different way of tracking the sessions and performs some 
 * additional tasks when creating an user account (setting new fields,
 * extra checks, etc).
 */
function qa_log_in_external_user($source, $identifier, $fields)
{
	require_once QA_INCLUDE_DIR.'qa-db-users.php';
	$remember = qa_opt('open_login_remember') ? true : false;
	
	$users=qa_db_user_login_find($source, $identifier);
	$countusers=count($users);
	if ($countusers>1)
		qa_fatal_error('External login mapped to more than one user'); // should never happen
	
	/*
	 * To allow for more than one account from the same openid/openauth provider to be 
	 * linked to an Q2A user, we need to override the way session source is stored
	 * Supposing userid 01 is linked to 2 yahoo accounts, the session source will be
	 * something like 'yahoo-xyz' when logging in with the first yahoo account and
	 * 'yahoo-xyt' when logging in with the other.
	 */
	
	$aggsource = qa_open_login_get_new_source($source, $identifier);
	
	// prepare some data
	if(empty($fields['handle'])) {
		$ohandle = ucfirst($source);
	} else {
		$ohandle = preg_replace('/[\\@\\+\\/]/', ' ', $fields['handle']);
	}
	$oemail = null;
	if (strlen(@$fields['email']) && $fields['confirmed']) { // only if email is confirmed
		$oemail = $fields['email'];
	}
	
	if ($countusers) { // user exists so log them in
		//always update email and handle
		if($oemail) qa_db_user_login_set__open($source, $identifier, 'oemail', $oemail);
		qa_db_user_login_set__open($source, $identifier, 'ohandle', $ohandle);
		
		qa_set_logged_in_user($users[0]['userid'], $users[0]['handle'], $remember, $aggsource);
	
	} else { // create and log in user
		require_once QA_INCLUDE_DIR.'qa-app-users-edit.php';
		
		qa_db_user_login_sync(true);
		
		$users=qa_db_user_login_find($source, $identifier); // check again after table is locked
		
		if (count($users)==1) {
			//always update email and handle
			if($oemail) qa_db_user_login_set__open($source, $identifier, 'oemail', $oemail);
			qa_db_user_login_set__open($source, $identifier, 'ohandle', $ohandle);
			
			qa_db_user_login_sync(false);
			qa_set_logged_in_user($users[0]['userid'], $users[0]['handle'], $remember, $aggsource);
			
		} else {
			$handle=qa_handle_make_valid(@$fields['handle']);

			// check if email address already exists
			$emailusers = array();
			if (strlen(@$fields['email']) && $fields['confirmed']) { // only if email is confirmed
				$emailusers=qa_db_user_find_by_email_or_oemail__open($fields['email']);
				
				if (count($emailusers)) {
					// unset regular email to prevent duplicates
					unset($fields['email']); 
				}
			}
			
			$userid=qa_create_new_user((string)@$fields['email'], null /* no password */, $handle,
				isset($fields['level']) ? $fields['level'] : QA_USER_LEVEL_BASIC, @$fields['confirmed']);
			
			qa_db_user_set($userid, 'oemail', $oemail);
			qa_db_user_login_add($userid, $source, $identifier);
			qa_db_user_login_set__open($source, $identifier, 'oemail', $oemail);
			qa_db_user_login_set__open($source, $identifier, 'ohandle', $ohandle);
			qa_db_user_login_sync(false);
			
			$profilefields=array('name', 'location', 'website', 'about');
			
			foreach ($profilefields as $fieldname)
				if (strlen(@$fields[$fieldname]))
					qa_db_user_profile_set($userid, $fieldname, $fields[$fieldname]);
					
			if (strlen(@$fields['avatar']))
				qa_set_user_avatar($userid, $fields['avatar']);
					
			qa_set_logged_in_user($userid, $handle, $remember, $aggsource);
			
			return count($emailusers);
		}
	}
	
	return 0;
}


/*
	Omit PHP closing tag to help avoid accidental output
*/