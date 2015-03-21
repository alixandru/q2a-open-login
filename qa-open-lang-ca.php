<?php

/*
	Question2Answer (c) Gideon Greenspan
	Open Login Plugin (c) Alex Lixandru

	http://www.question2answer.org/

	
	File: qa-plugin/open-login/qa-open-lang-default.php
	Version: 3.0.0
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
	'other_logins_conf_text' => 'We have detected other accounts which are using the same email address. If these accounts belong to you, you can link them to your current profile to avoid duplicates.',
	'split_accounts_note' => 'These are the external accounts which you can use to log in to this site. Please note that they are used specifically for authentication purposes and no messages are ever posted on your behalf on social networks.',
	'no_logins_title' => 'No other external accounts for your profile',
	'no_logins_text' => 'Log in using an external login provider to connect your current profile with other accounts.',
	'login_title' => 'Login through an external provider',
	'login_description' => 'Choose an external provider from the list below to login without creating an account on this site.',
	'login_using' => 'Login using ^',
	'password' => 'password',
	'continue' => 'Continue',
	'choose_action' => 'Choose what you would like to do:',
	'merge_all' => 'Merge all accounts into one.',
	'merge_all_first' => 'All the above user accounts, along with the account you\'re currently logged in with, will be merged into a single one.',
	'select_merge' => 'Let me select which accounts to merge.',
	'select_merge_first' => 'Only the selected accounts will be merged into a single one.',
	'merge_note' => 'You have the possibility to choose a main account which will be kept after the merge and which will acquire the login details from the other accounts. It is important to note that the other accounts will be associated with the main profile you select and their own profiles will be deleted. The points and reputation of these accounts will not be migrated and the previous activity will be marked as anonymous. Only the activity and the points of the main account you choose will remain intact. This action is not reversible.',
	'cancel_merge' => 'I don\'t care about duplicates. Leave everything as is.',
	'cancel_merge_note' => 'If you choose this option it is possible that your reputation points be split across several user accounts. At this moment, there is no way to migrate points from one account to another and this is why it is recommended to merge all your accounts into a single one.',
	'select_base' => 'select an account',
	'select_base_note' => 'Choose the account which you will be using after the merge:',
	'current_account' => 'current account',
	'action_info_1' => 'Select an action in order to continue.',
	'action_info_2' => 'No change will be performed. Your account will remain as it is.',
	'action_info_3' => 'Choose an account in order to continue.',
	'action_info_4' => 'will be kept, and the rest of accounts will be merged with it.',
	'action_info_5' => 'No duplicate account selected. Select at least one account in order to continue.',
	'unlink_this_account' => 'Unlink this account',
	'link_with_account' => 'Connect your current account with other external accounts',
	'link_all' => 'Merge accounts into a single one.',
	'cancel_link' => 'Cancel the merge and let everything unchanged.',
	'link_exists' => 'The external account your are trying to connect with (through ^) is already linked with another user account on this site. If these accounts belong to you, you can merge them together in order to avoid duplicates.',
);
