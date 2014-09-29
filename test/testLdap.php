<?php

require_once __DIR__.'/../vendor/autoload.php';

use MonCompte\OldLdapSync;


$membre = [
	'numero_membre' => '100000',
	'nom' => 'Joé',
	'prenom' => 'Blàck',
	'email' => 'joeblack@example.com',
];

$result = OldLdapSync::migrer_vers_LDAP($membre);

echo ">>>> ".$result."\n";
