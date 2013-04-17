<?php

/*
	Question2Answer (c) Gideon Greenspan
	Open Login Plugin (c) Alex Lixandru

	http://www.question2answer.org/

	
	File: qa-plugin/open-login/qa-plugin.php
	Version: 1.0.0
	Description: Initiates Open Login plugin


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

/*
	Plugin Name: Open Login
	Plugin URI: 
	Plugin Description: Allows users to log in via Facebook, Google and other Open Auth providers
	Plugin Version: 1.0.0
	Plugin Date: 2013-01-31
	Plugin Author: Alex Lixandru
	Plugin Author URI: http://alex.punctsivirgula.ro/
	Plugin License: GPLv2
	Plugin Minimum Question2Answer Version: 1.5.4
	Plugin Minimum PHP Version: 5
	Plugin Update Check URI:
*/

/*
	Based on Facebook Login plugin
*/

if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}


if (!QA_FINAL_EXTERNAL_USERS) { // login modules don't work with external user integration

	qa_register_plugin_phrases('qa-open-lang-*.php', 'plugin_open');
	qa_register_plugin_overrides('qa-open-overrides.php');
	qa_register_plugin_layer('qa-open-layer.php', 'OAuth/OpenID Layer');
	
	qa_register_plugin_module('page', 'qa-open-page-logins.php', 'qa_open_logins_page', 'OAuth/OpenID');
	qa_register_plugin_module('login', 'qa-open-login-facebook.php', 'qa_facebook_open', 'Facebook Login');
	qa_register_plugin_module('login', 'qa-open-login-google.php', 'qa_google_open', 'Google Login');
	qa_register_plugin_module('login', 'qa-open-login-yahoo.php', 'qa_yahoo_open', 'Yahoo Login');
	qa_register_plugin_module('login', 'qa-open-login-github.php', 'qa_github_open', 'Github Login');
	
}


/*
	Omit PHP closing tag to help avoid accidental output
*/