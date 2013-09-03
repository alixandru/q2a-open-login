<?php

/*
	Question2Answer (c) Gideon Greenspan
	Open Login Plugin (c) Alex Lixandru

	http://www.question2answer.org/

	
	File: qa-plugin/open-login/qa-open-layer.php
	Version: 2.0.0
	Description: Extends current theme with additional functionalities


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


class qa_html_theme_layer extends qa_html_theme_base
{
	function doctype() {
		parent::doctype();

		// first see if the account pages are accessed
		$tmpl = array( 'account', 'favorites' );
		$logins_page = qa_request() == 'logins';
		
		if ( in_array($this->template, $tmpl) || $logins_page ) {
			// add a navigation item
			$this->content['navigation']['sub']['logins'] = array(
				'label' => qa_lang_html('plugin_open/my_logins_nav'),
				'url' => qa_path_html('logins'),
				'selected' => $logins_page
			);
			return;
		}
		
		// then check if login/register pages are accessed
		$tmpl = array( 'register', 'login' );
		if ( !in_array($this->template, $tmpl) ) {
			return;
		}

		// add some custom text
		if(!empty($this->content['custom'])) {
			$title = qa_lang_html('plugin_open/login_title');
			$descr = qa_lang_html('plugin_open/login_description');
			$content = str_replace('<BR>', '', $this->content['custom']);
			
			$this->content['custom'] = "<br /><br /><div><h1>$title</h1><p>$descr</p>$content</div>";
		}
	}

	function head_css() {
		parent::head_css();
		
		$hidecss = qa_opt('open_login_css') == '1';
		$zocial = qa_opt('open_login_zocial') == '1';
		
		if (!$hidecss) {
			// display CSS inline
			$path = QA_HTML_THEME_LAYER_URLTOROOT;
			
			$this->output('<style type="text/css"><!--');
			$this->output(@file_get_contents( QA_HTML_THEME_LAYER_URLTOROOT . 'qa-open-login.css'));
			$this->output('//--></style>');
			
			if($zocial) {
				$this->output('<style type="text/css"><!--');
				$this->output("@import url('{$path}css/zocial.css');");
				$this->output('//--></style>');
			}
		}
	}

}
