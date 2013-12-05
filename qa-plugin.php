<?php

/*
	Question2Answer (c) Gideon Greenspan
	Open Login Plugin (c) Alex Lixandru

	http://www.question2answer.org/

	
	File: qa-plugin/open-login/qa-plugin.php
	Version: 3.0.0
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
	Plugin URI: https://github.com/alixandru/q2a-open-login
	Plugin Description: Allows users to log in via Facebook, Google and other Open Auth providers
	Plugin Version: 3.0.0
	Plugin Date: 2013-12-06
	Plugin Author: Alex Lixandru
	Plugin Author URI: http://alex.punctsivirgula.ro/
	Plugin License: GPLv2
	Plugin Minimum Question2Answer Version: 1.6
	Plugin Minimum PHP Version: 5
	Plugin Update Check URI: https://raw.github.com/alixandru/q2a-open-login/master/qa-plugin.php
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
	qa_register_plugin_module('page', 'qa-open-page-logins.php', 'qa_open_logins_page', 'Open Login Configuration');
	
	// sice we're not allowed to access the database at this step, take the information from a local file
	// note: the file providers.php will be automatically generated when the configuration of the plugin
	// is updated on the Administration page
	$providers = @include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'providers.php';
	if ($providers) {
		// loop through all active providers and register them
		$providerList = explode(',', $providers);
		foreach($providerList as $provider) {
			qa_register_plugin_module('login', 'qa-open-login.php', 'qa_open_login', $provider);
		}
	}
	
}

/*
	Omit PHP closing tag to help avoid accidental output
*/
