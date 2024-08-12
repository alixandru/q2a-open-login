<?php

/*
	Question2Answer (c) Gideon Greenspan
	Open Login Plugin (c) Alex Lixandru

	http://www.question2answer.org/


	File: qa-plugin/open-login/qa-open-utils.php
	Version: 3.0.0
	Description: Contains various utility functions used by the plugin


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


function qa_db_user_login_find_other__open($userid, $email, $additional = 0) {
	// return all logins with the same (verified) email OR which are associated with the specified user id
	// super admins will not be included

	/* create a hierarchical structure like this:
			[id] {
				details: [data from user table]
				logins: [multiple records from userlogins table]
			}
	*/
	if(!empty($email))
		$logins = qa_db_read_all_assoc(qa_db_query_sub(
			'SELECT us.*, up.points, ul.identifier, ul.source, ul.oemail as uloemail FROM ^users us
				LEFT JOIN ^userpoints up ON us.userid = up.userid
				LEFT JOIN ^userlogins ulf ON us.userid = ulf.userid
				LEFT JOIN ^userlogins ul ON us.userid = ul.userid
				WHERE (us.oemail=$ OR (us.email=$ AND us.flags & $) OR ulf.oemail=$) AND us.level<=$',
			$email, $email, QA_USER_FLAGS_EMAIL_CONFIRMED, $email, 100
		));
	else if(!empty($userid))
		$logins = qa_db_read_all_assoc(qa_db_query_sub(
			'SELECT us.*, up.points, ul.identifier, ul.source, ul.oemail as uloemail FROM ^users us
				LEFT JOIN ^userlogins ul ON us.userid = ul.userid
				LEFT JOIN ^userpoints up ON us.userid = up.userid
				WHERE (us.userid=$ OR us.userid=$) AND us.level<=$',
			$userid, $additional, 100
		));
	else
		return array();

	$ret = array();
	foreach($logins as $l) {
		$id = $l['userid'];

		if(isset($ret[$id])) {
			$structure = $ret[$id];
		} else {
			$structure = array();
			$structure['logins'] = array();
			$structure['details'] = array(
				'userid' => $id,
				'handle' => $l['handle'],
				'points' => $l['points'],
				'email' => $l['email'],
				'oemail' => $l['uloemail'],
			);
		}

		if(!empty($l['identifier'])) {
			$structure['logins'][] = ucfirst($l['source']); // push this new login
		}

		$ret[$id] = $structure;
	}

	return $ret;
}

function qa_db_user_login_find_duplicate__open($source, $id) {
	// return the login with the specified source and id
	$duplicates = qa_db_read_all_assoc(qa_db_query_sub(
		'SELECT * FROM ^userlogins WHERE source=$ and identifier=$',
		$source, $id
	));
	if(empty($duplicates)) {
		return null;
	} else {
		return $duplicates[0];
	}
}

function qa_db_user_login_find_mine__open($userid) {
	// return all logins associated with this user
	return qa_db_read_all_assoc(qa_db_query_sub(
		'SELECT * FROM ^userlogins WHERE userid=$',
		$userid
	));
}

function qa_db_user_login_set__open($source, $identifier, $field, $value) {
	// update an arbitrary field on userlogins table
	qa_db_query_sub(
		'UPDATE ^userlogins SET '.qa_db_escape_string($field).'=$ WHERE source=$ and identifier=$',
		$value, $source, $identifier
	);
}

function qa_db_user_login_replace_userid__open($olduserid, $newuserid) {
	// replace the userid in userlogins table
	qa_db_query_sub(
		'UPDATE ^userlogins SET userid=$ WHERE userid=$',
		$newuserid, $olduserid
	);
}

function qa_db_user_login_delete__open($source, $identifier, $userid) {
	// delete an user login
	qa_db_query_sub(
		'DELETE FROM ^userlogins WHERE source=$ and identifier=$ and userid=$',
		$source, $identifier, $userid
	);
}

function qa_db_user_find_by_email_or_oemail__open($email) {
	// Return the ids of all users in the database which match $email (should be one or none)
	// Note: we're not verifying the confirmed flag here - all emails should be considered
	if(empty($email)) {
		return array();
	}

	return qa_db_read_all_values(qa_db_query_sub(
		'SELECT userid FROM ^users WHERE email=$ or oemail=$',
		$email, $email
	));
}

function qa_db_user_find_by_id__open($userid) {
	// Return the user with the specified userid (should return one user or null)
	$users = qa_db_read_all_assoc(qa_db_query_sub(
		'SELECT us.*, up.points FROM ^users us
		LEFT JOIN ^userpoints up ON us.userid = up.userid
		WHERE us.userid=$',
		$userid
	));
	if(empty($users)) {
		return null;
	} else {
		return $users[0];
	}
}

function qa_open_login_get_new_source($source, $identifier) {
	// return a new session source containing the actual open id provider and the
	// user identifier. This string represents a unique combination of userid and
	// openid-provider, allowing for more than one account from the same openid
	// provider to be linked to an Q2A user (ie. a QA user can have 2 Facebook
	// accounts linked to it)
	return substr($source, 0, 9) . '-' . substr(md5($identifier), 0, 6);
}

function qa_open_login_get_provider_scope($provider) {
	// Returns the scope for each provider, so that we only keep the basic profile information, email and pictures.
	// In some case (Google, Live, ...), HybridAuth would also add the access to all contacts, which requires further
	// acceptation from the end user, and might scare some of them without any use for us.

	// Default scope is null (we keep the one defined in HybridAuth)

	switch ($provider)
	{
		case 'Facebook':
			// default scope for facebook is 'email', 'public_profile'
			return 'email, public_profile';

		case 'Google':
			// default scope for Google also includes https://www.google.com/m8/feeds/ (contacts)
			return 'https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email';

		case 'LinkedIn':
			// We actually keep the current default scope of HybridAuth, but in case it'd change, let's hardcode it:
			return 'r_basicprofile r_emailaddress';

		case 'Live':
			// default scope for MS Live is way too large: 'wl.basic wl.contacts_emails wl.emails wl.signin wl.share wl.birthday'
			// @see https://msdn.microsoft.com/en-us/windows/desktop/hh243646
			return 'wl.basic wl.emails wl.signin wl.birthday';

		default: return null;
	}
}


/*
	Omit PHP closing tag to help avoid accidental output
*/