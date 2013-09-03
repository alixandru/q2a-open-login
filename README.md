# Question2Answer Open Login #

## About ##

This is a plugin for **Question2Answer** that allows users to log in via Facebook, Google, Yahoo, Github and other OAuth/OpenID providers. 


## Description ##
This is an extension of the Facebook Login plugin, to which it adds additional login providers. It is based on [HybridAuth](http://hybridauth.sourceforge.net/) library which acts as a middleware between the plugin and a wide range of OAuth and OpenID service providers. For this reason, it is possible to add any identity provider supported by HybridAuth to your Q2A installation with virtually no effort.

The plugin also offers the ability to link multiple OpenID/OAuth-powered logins to a Q2A user account, allowing users to log in to the same account via multiple providers. For example, an user might link his or her Facebook and Google accounts to the Q2A user account and then log in to the Q2A site through any of the 3 methods (Q2A login page, Facebook OAuth or Google OpenID).


## Installation ##

* Install [Question2Answer][]. This plugin requires at least version 1.6 (see the change log for details)
* Get the source code for this plugin from [Github][], either using [Git][], or downloading directly:

   - To download using git, install git and then type 
     `git clone git://github.com/alixandru/q2a-open-login.git open-login`
   - To download directly, go to the [project page][Github] and click **Download ZIP**

* Go to **Admin -> Plugins** on your Q2A installation and enable the providers which you would like to use. For all OAuth-based providers (all, except Google and Yahoo, which use OpenID) you need to provide some keys after you register your application with them. See [HybridAuth documentation](http://hybridauth.sourceforge.net/userguide.html) for information about what is needed for each provider.
* Optionally add the contents of the *qa-open-login.css* file to your theme's CSS file and select the option **Don't inline CSS** from the **Open Login Configuration** section on the **Admin -> Plugins** page. Please note that, according to the URL of your Q2A instance, you might need to adjust the paths in the CSS file.

Note: this plugin requires some database changes: a column called `oemail` (original email) will be added to the tables `qa_users` and `qa_user_logins`. These columns will store the email associated with the OpenID/OAuth accounts when the users log in through any OpenID/OAuth provider. These emails will then be used to determine if there are accounts which can be linked together. The database changes will be performed when the administration page is accessed for the first time after the plugin is installed or upgraded. This is a one-time-only operation and it should not affect your existing data in any way.

  [Question2Answer]: http://www.question2answer.org/install.php
  [Git]: http://git-scm.com/
  [Github]: https://github.com/alixandru/q2a-open-login



## Adding new login providers ##

Since this plugin is based on [HybridAuth](http://hybridauth.sourceforge.net/), you can easily add new login providers to your Q2A site. All you need to do is to add the provider PHP file to the `Hybrid/Providers` folder and configure it from the Administration page. That's it! 



## Handling login errors ##

Whenever a login attempt fails, the user will be redirected to the original page but no error message will be displayed. This is to save end-users from technical error messages which would not help them much anyway. If you instead would like to show an error message, you can do that through a layer or a custom theme. 

If something happens with the login process and authentication cannot be done, the user will be redirected to a page whose URL follows the following pattern: `yoursite.com/?provider=X&code=0`. The custom layer or theme could check if the two parameters are present in the URL and display an error message based on the code number. The descriptions of the error codes are below.

    0 : Unspecified error.
    1 : Hybriauth configuration error.
    2 : Provider not properly configured.
    3 : Unknown or disabled provider.
    4 : Missing provider application credentials.
    5 : Authentification failed. The user has canceled the authentication or the provider refused the connection.
    6 : User profile request failed. Most likely the user is not connected to the provider and he should authenticate again.
    7 : User not connected to the provider.
    8 : Provider does not support this feature.



## Translation ##

The translation file is **qa-open-lang-default.php**.  Copy this file to the same directory and change the **"default"** part of the filename to your language code. Edit the right-hand side strings in this file, for example, changing:

**`'my_logins_title'=>'My logins',`**

to

**`'my_logins_title'=>'Mes comptes',`**

Don't edit the string on the left-hand side. Once you've completed the translation, don't forget to set the site language in the admin control panel. Translations for Romanian are also included.  



## Change log ##

**v2.0.2**

* Add Russian translation, contributed by [Dmitry Mikhirev](https://github.com/mikhirev)
* Updated this file with information on error handling
* Other small fixes


**v2.0.1**

* Add links to the HybridAuth documentation pages from the plugin admin section
* Correct layout issue with Windows Live provider
* Translation changes and other small fixes


**v2.0.0**

* Rewrite the plugin to use HybridAuth 2.1.2
* Add the ability to specify what login providers to appear in the page header
* Add the ability to specify whether to use a CSS3 icon pack or plain links
* The plugin requires Q2A 1.6. Older versions of Q2A should use version 1.1.0 of the plugin


**v1.1.0**

* Add the ability to keep users connected when they log in through an external provider
* Fix issues with logging users out


**v1.0.0**

* Initial release which supports logging in through Facebook, Google, Yahoo and Github.



## Disclaimer ##
This code has not been extensively tested on high-traffic installations of Q2A. You should perform your own tests before using this plugin on a live (production) environment. 


## License ##
This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.


## About Q2A ##
Question2Answer is a free and open source platform for Q&A sites. For more information, visit [http://www.question2answer.org/](http://www.question2answer.org/)
