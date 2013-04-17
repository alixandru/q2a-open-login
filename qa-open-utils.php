<?php

/*
	Question2Answer (c) Gideon Greenspan
	Open Login Plugin (c) Alex Lixandru

	http://www.question2answer.org/

	
	File: qa-plugin/open-login/qa-open-utils.php
	Version: 1.0.0
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


function qa_db_user_login_find_other__open($userid, $email) {
	// return all logins with the same email which are not associated with this user
	// super admins will not be included
	
	if(empty($email)) {
		return array();
	}
	return qa_db_read_all_assoc(qa_db_query_sub(
		'SELECT us.*, ul.identifier, ul.source, ul.oemail as uloemail FROM ^users us 
			LEFT JOIN ^userlogins ul ON us.userid = ul.userid 
			WHERE (us.oemail=$ OR us.email=$) AND us.userid!=$ AND us.level<$',
		$email, $email, $userid, 100
	));
}

function qa_db_user_login_find_mine__open($userid, $srcexcl) {
	// return all logins associated with this user, with a different session source than the one specified
	// if no source is specified, simply return all logins associated with this user
	
	if(empty($srcexcl)) {
		return qa_db_read_all_assoc(qa_db_query_sub(
			'SELECT * FROM ^userlogins WHERE userid=$',
			$userid
		));
		
	} else {
	
		// the source is in the format [provider]-[id]
		$parts = explode('-', $srcexcl, 2);
		$source = $parts[0]; $id = $parts[1] . '%';
		return qa_db_read_all_assoc(qa_db_query_sub(
			'SELECT * FROM ^userlogins WHERE userid=$ AND ((source!=$) OR (source=$ AND MD5(identifier) NOT LIKE $))',
			$userid, $source, $source, $id
		));
	}
}

function qa_db_user_login_set__open($source, $identifier, $field, $value) {
	// update an arbitrary field on userlogins table
	qa_db_query_sub(
		'UPDATE ^userlogins SET '.qa_db_escape_string($field).'=$ WHERE source=$ and identifier=$',
		$value, $source, $identifier
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
		'SELECT * FROM ^users WHERE userid=$',
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

/*
	Omit PHP closing tag to help avoid accidental output
*/