<?php

/*
	Question2Answer (c) Gideon Greenspan
	Open Login Plugin (c) Alex Lixandru

	http://www.question2answer.org/


	File: live.php
	Version: 3.0.0
    Description: Specific endpoint for MS Live.

    YOU NEED TO COPY THIS FILE TO THE ROOT FOLDER OF YOUR Q2A INSTALL


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

// ------------------------------------------------------------------------
//	HybridAuth End Point
// ------------------------------------------------------------------------
$_REQUEST['hauth_done'] = 'Live';
require_once( "qa-plugin/q2a-open-login/Hybrid/Auth.php" );
require_once( "qa-plugin/q2a-open-login/Hybrid/Endpoint.php" );
Hybrid_Endpoint::process();
