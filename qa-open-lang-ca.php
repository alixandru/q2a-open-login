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
	'my_logins_title' => 'Les meves sessions',
	'my_logins_nav' => 'Les meves sessions',
	'my_current_user' => 'El meu compte actual',
	'associated_logins' => 'Comptes connectats',
	'other_logins' => "Altres comptes amb aquesta adreça d'e-mail",
	'other_logins_conf_title' => 'Confirma els comptes connectats',
	'other_logins_conf_text' => "Hem detectat altres comptes que fan servir la mateixa adreça d'e-mail. Si són teus, els pots vincular al perfil actual per evitar duplicats.",
	'split_accounts_note' => 'Aquests comptes són externs, els pots fer servir per entrar a aquesta web. Si us plau, tingues present que es fan servir estrictament per autenticar-te i no per enviar missatges a xarxes socials en nom teu.',
	'no_logins_title' => 'No hi ha altres comptes externs per al teu perfil.',
	'no_logins_text' => 'Entra fent servir un proveïdor extern per connectar el teu perfil actual a aquests altres comptes.',
	'login_title' => "No cal que et registris. Pots entrar fent servir un proveïdor extern.\n",
	'login_description' => "Tria un proveïdor extern d'aquesta llista per entrar sense haver de registrar-te en aquesta web.",
	'login_using' => 'Entra usant ^',
	'password' => 'contrasenya',
	'continue' => 'Continua',
	'choose_action' => 'Tria que voldries fer:',
	'merge_all' => 'Vincula tots els comptes en un.',
	'merge_all_first' => 'Tots els comptes de sobre, junt amb el compte actual, seran vinculades en un.',
	'select_merge' => 'Deixem triar quins comptes vincular.',
	'select_merge_first' => 'Només els comptes seleccionats seran vinculats en un.',
	'merge_note' => "
Tens la possibilitat de triar el compte que serà el principal després de la vinculació i que adquirirà la informació dels altres comptes. Es important tenir present que els altres comptes seran associades amb el que seleccionis i que el altres comptes d'aquesta web seran esborrats. Els punts i reputació d'aquests perfils no seran migrats i tota l'activitat prèvia serà marcada com anònima. Només l'activitat i els punts del compte principal que triïs romandrà intacte. Aquesta acció no és reversible.",
	'cancel_merge' => "No m'importen els duplicats, deixa-ho com està.",
	'cancel_merge_note' => "Si tries aquesta opció, és possible que els teus punts de reputació siguin repartits entre varis comptes. Ara mateix, no hi ha manera de migrar els punts d'un compte a l'altre, per això es recomana vincular tots els comptes en un de sol.",
	'select_base' => 'Tria un compte',
	'select_base_note' => 'Tria quin compte faràs servir després de vincular-los:',
	'current_account' => 'Compte actual',
	'action_info_1' => 'Tria una acció per continuar.',
	'action_info_2' => 'No es farà cap canvi. El teu compte romandrà com està ara.',
	'action_info_3' => 'Tria un compte per continuar.',
	'action_info_4' => 'es mantindrà i la resta de comptes seran vinculats a aquest.',
	'action_info_5' => "No s'ha triat cap compte duplicat. Tria com a mínim un compte per continuar.",
	'unlink_this_account' => 'Desvincula aquest compte.',
	'link_with_account' => 'Connecta el compte actual amb altres comptes externes',
	'link_all' => 'Vincula tots els comptes en un de sol.',
	'cancel_link' => 'Cancel·la la vinculació i deixa-ho tot com està.',
	'link_exists' => 'El compte extern que estàs intentant fer servir per entrar (a través de ^) ja està vinculat a un altre compte en aquesta web. Si aquests comptes et pertanyen, els pots vincular per evitar duplicats.',
);
