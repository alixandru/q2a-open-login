<?php
/**
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2015, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
*/
// ------------------------------------------------------------------------
//	HybridAuth End Point
// ------------------------------------------------------------------------
$_REQUEST['hauth_done'] = 'Live';
require_once( "qa-plugin/q2a-open-login/Hybrid/Auth.php" );
require_once( "qa-plugin/q2a-open-login/Hybrid/Endpoint.php" );
Hybrid_Endpoint::process();
