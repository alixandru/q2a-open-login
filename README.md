# Question2Answer Open Login #

## About ##

This is a plugin for **Question2Answer** that allows users to log in via Facebook, Google, Yahoo and Github. 


## Description ##
This is an extension of the Facebook Login plugin, to which it adds 3 more providers (Google OpenID, Yahoo OpenID and Github OAuth). 

The plugin also offers the ability to link multiple OpenID/OAuth-powered logins to a Q2A user account, allowing users to log in to the same account via multiple providers. For example, an user might link his or her Facebook and Google accounts to the Q2A user account and then log in to the Q2A site through any of the 3 methods (Q2A login page, Facebook OAuth or Google OpenID).

The plugin is not compatible with Facebook Login plugin so only one of them can be used at a time.


## Installation ##

* Install [Question2Answer][]
* Remove the Facebook Login plugin which comes preinstalled (simply delete `facebook-login` folder from `qa-plugin`)
* Get the source code for this plugin from [Github][], either using [Git][], or downloading directly:

   - To download using git, install git and then type 
     `git clone git://github.com/alixandru/q2a-open-login.git open-login`
   - To download directly, go to the [project page][Github] and click **Download**

* Go to **Admin -> Plugins** on your Q2A installation and enable the providers which you would like to use. For Facebook and Github you need to provide some keys after you register your application with them.
* Optionally add the contents of the *qa-open-login.css* file to your theme's CSS file and select the option **Don't inline CSS** from the **OAuth/OpenID** section on the **Admin -> Plugins** page.

Note: this plugin requires some database changes: a column called `oemail` (original email) will be added to the tables `qa_users` and `qa_user_logins`. These columns will store the email associated with the OpenID/OAuth accounts when the users first logs in through any OpenID/OAuth provider. These emails will then be used to determine if there are accounts which can be linked together.

  [Question2Answer]: http://www.question2answer.org/install.php
  [Git]: http://git-scm.com/
  [Github]: https://github.com/alixandru/q2a-open-login


## Translation ##

The translation file is **qa-open-lang-default.php**.  Copy this file to the same directory and change the **"default"** part of the filename to your language code. Edit the right-hand side strings in this file, for example, changing:

**`'my_logins_title'=>'My logins',`**

to

**`'my_logins_title'=>'Mes comptes',`**

for French.  

Don't edit the string on the left-hand side. Once you've completed the translation, don't forget to set the site language in the admin control panel. Translations for Romanian are also included.  


## Disclaimer ##
This code has not been extensively tested on high-traffic installations of Q2A. You should perform your own tests before using this plugin on a live (production) environment. 


## License ##
This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.


## About Q2A ##
Question2Answer is a free and open source platform for Q&A sites. For more information, visit [http://www.question2answer.org/](http://www.question2answer.org/)
