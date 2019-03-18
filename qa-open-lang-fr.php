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
	'my_logins_title' => 'Mes identifiants',
	'my_logins_nav' => 'Mes identifiants',
	'my_current_user' => 'Mon compte actuel',
	'associated_logins' => 'Comptes connectés',
	'other_logins' => "D'autres comptes utilisateurs sur le site utilisent cette adresse email",
	'other_logins_conf_title' => 'Confirmez les comptes connectés',
	'other_logins_conf_text' => "Nous avons détecté d'autres comptes sur le site qui utilisent la même adresse email. S'ils vous concernent, vous pouvez les relier à celui-ci pour éviter des doublons.",
	'split_accounts_note' => "Ce sont les services externes que vous pouvez utiliser pour vous connecter au site. Notez qu'ils sont utilisés spécifiquement pour vous connecter à ce site et aucune action ne sera faite en votre nom sur ces plateformes.",
	'no_logins_title' => "Aucun service de connexion externe n'est associé à votre compte",
	'no_logins_text' => "Se connecter en utilisant un service de connexion externe pour l'associer à ce compte.",
	'login_title' => "Se connecter à partir d'un service de connexion externe",
	'login_description' => 'Choisissez ci-dessous le service avec lequel vous souhaitez vous connecter.',
	'login_using' => 'Se connecter en utilisant ^',
	'password' => 'mot de passe',
	'continue' => 'Continuer',
	'choose_action' => 'Choisissez ce que vous voulez faire:',
	'merge_all' => 'Fusionner tous les comptes en un seul.',
	'merge_all_first' => "Tous les comptes utilisateurs ci-dessus, ainsi que le compte avec lequel vous êtes actuellement connecté, seront fusionnés en un seul.",
	'select_merge' => 'Laissez-moi sélectionner les comptes à fusionner.',
	'select_merge_first' => 'Seulement les comptes sélectionnés seront regroupés en un seul.',
	'merge_note' => "Vous avez la possibilité de choisir un compte principal qui sera conservé après la fusion et qui obtiendra les détails de connexion des autres comptes. Il est important de noter que les autres comptes seront associés au profil principal que vous sélectionnez et leurs propres profils seront supprimés. Les points et la réputation de ces comptes ne seront pas migrés et l'activité précédente sera marquée comme anonyme. Seules l'activité et les points du compte principal que vous choisissez resteront intacts. Cette action n'est pas réversible.",
	'cancel_merge' => "Peu importe les doublons. Laisser tel que c'est actuellement",
	'cancel_merge_note' => "Si vous choisissez cette option, il est possible que vos points de réputation soient répartis sur plusieurs comptes utilisateurs. Pour le moment, il n'y a aucun moyen de migrer les points d'un compte à l'autre et c'est pourquoi il est recommandé de fusionner tous vos comptes en un seul.",
	'select_base' => 'choisir un compte',
	'select_base_note' => 'Choisissez le compte que vous utiliserez après la fusion:',
	'current_account' => 'compte actuel',
	'action_info_1' => 'Choisir une action pour continuer.',
	'action_info_2' => 'Aucun changement effectué. Votre compte restera tel quel.',
	'action_info_3' => 'Choisir un compte afin de continuer.',
	'action_info_4' => 'sera conservé, et le reste des comptes sera fusionné avec lui.',
	'action_info_5' => 'Aucun compte en double sélectionné. Sélectionnez au moins un compte pour continuer.',
	'unlink_this_account' => 'Détacher ce compte',
	'link_with_account' => "Connectez votre compte à d'autres comptes externes",
	'link_all' => 'Fusionner les comptes en un seul.',
	'cancel_link' => 'Annuler la fusion et laisser les choses inchangées.',
	'link_exists' => "Le compte externe avec lequel vous essayez de vous connecter (via ^) est déjà lié à un autre compte utilisateur sur ce site. Si ces comptes vous appartiennent, vous pouvez les fusionner afin d'éviter les doublons.",
);
