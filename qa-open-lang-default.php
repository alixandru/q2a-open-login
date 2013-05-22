<?php

/*
	Question2Answer (c) Gideon Greenspan
	Open Login Plugin (c) Alex Lixandru

	http://www.question2answer.org/

	
	File: qa-plugin/open-login/qa-open-lang-default.php
	Version: 1.0.0
	Description: Default English translation of all plugin texts


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


return array(
	'my_logins_title' => 'My logins',
	'my_logins_nav' => 'My logins',
	'my_current_user' => 'My current account',
	'associated_logins' => 'Connected accounts',
	'other_logins' => 'Other accounts matching this email address',
	'other_logins_conf_title' => 'Confirm the connected accounts',
	'other_logins_conf_text' => 'We have detected other accounts are using the same email address. If these accounts belong to you, you can link them to your current profile to avoid duplicates.',
	'merge_accounts' => 'Connect selected accounts',
	'merge_accounts_note' => 'Important! The selected logins will be associated with your current profile and their initial profiles will be permanently deleted. Reputation points belonging to these profiles will not be migrated, and previous activity will be marked as annonymous. If you want instead to keep one of the other accounts and remove the one you\'re currently using, log in with that other account and visit this page again.',
	'split_accounts' => 'Disconnect selected accounts',
	'split_accounts_note' => 'Note: once disconnected, your profile will not be associated with those accounts anymore. If you sign in again with those login IDs after disconnecting them, new user accounts will be created for them. You can, however, merge them again with this profile by visiting this page.',
	'no_logins_title' => 'No other connected accounts for your profile',
	'no_logins_text' => 'Log in using an OpenID provider to connect your current profile with other accounts.',
	'local_user' => 'Local user account',
	'login_title' => 'Login through OpenID or OAuth',
	'login_description' => 'Choose an OpenID or OAuth provider from the list to login without creating an account on this site.',
	'remember_me' => 'Keep me signed in when I log in using any of the linked accounts.',
	
	'google_login' => 'Login using Google',
	'facebook_login' => 'Login using Facebook',
	'github_login' => 'Login using Github',
	'yahoo_login' => 'Login using Yahoo!',
);
