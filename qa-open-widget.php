<?php

/*
	Question2Answer (c) Gideon Greenspan
	Open Login Plugin (c) Alex Lixandru

	http://www.question2answer.org/

	
	File: qa-plugin/open-login/qa-open-widget.php
	Version: 3.0.0
	Description: Widget module class open login plugin


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

	class qa_open_logins_widget {
		
		function allow_template($template) {
			
			// not allowed when logged in
			$userid = qa_get_logged_in_userid();
			if (stristr(qa_request(), 'admin/layoutwidgets') === false && isset($userid)) {
				return false;
			}

			if($template == 'login' || $template == 'register') {
				return false;
			}
			
			return true;
		}

		
		function allow_region($region) {
			return ($region == 'side');
		}
		

		function output_widget($region, $place, $themeobject, $template, $request, $qa_content) {
			$loginmodules=qa_load_modules_with('login', 'login_html');
			
			if(empty($loginmodules)) {
				return;
			}
			
			$themeobject->output(
				'<div class="open-login-sidebar">',
				qa_lang_html('plugin_open/login_title'),
				'</div>',
				'<p class="open-login-sidebar-buttons">'
			);
			
			foreach ($loginmodules as $tryname => $module) {
				ob_start();
				$module->login_html(isset($topath) ? (qa_opt('site_url').$topath) : qa_path($request, $_GET, qa_opt('site_url')), 'sidebar');
				$label=ob_get_clean();

				if (strlen($label))
					$themeobject->output($label);
			}
			
			$themeobject->output('</p>');

		}
		
	}
/*
	Omit PHP closing tag to help avoid accidental output
*/