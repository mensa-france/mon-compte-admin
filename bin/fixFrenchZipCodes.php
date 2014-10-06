<?php

/***
 * This script is used to fix french zip codes that are missing leading 0s.
 */

require_once __DIR__.'/../vendor/autoload.php';

use MonCompte\Arrays;
use MonCompte\StopWatch;
use MonCompte\Format;
use MonCompte\LdapSync;
use MonCompte\DB\Queries;

$stopWatch = new StopWatch();
$stopWatch->start();

$membres = Queries::listMembresWithAddress();

echo "Found member count: ".count($membres)."\n";

foreach ($membres as $membre) {
	$address = json_decode($membre['adresse'], true); // set 2nd arg as true to get associative array.

	if ($address['country'] == 'FRANCE' AND strlen($address['code']) < 5) {
		echo "Fixing zipcode for #".$membre['numero_membre']."\n";

		while (strlen($address['code']) < 5)
			$address['code'] = '0'.$address['code'];

		Queries::updateCoordonnee($membre['id_membre'],'address',json_encode($address));
	}
}

echo 'Address fix successfully in: '.$stopWatch->getElapsedTime()."s\n";
