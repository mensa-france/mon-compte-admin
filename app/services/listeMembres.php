<?php

require_once __DIR__.'/../../vendor/autoload.php';

use MonCompte\DB\Queries;
use MonCompte\Logger;
use MonCompte\StopWatch;

$stopWatch = new StopWatch();
$stopWatch->start();

header("Content-type: application/json; charset=utf-8'");

$logger = Logger::getLogger('services/exportCoordonnees');

$membres = Queries::listMembres();
$logger->info('Found membres count: '.count($membres));

if (count($membres) > 0) {
	foreach ($membres as $index => $membre) {
		foreach ($membre as $key => $value) {
			if (preg_match('/^date_/', $key)) {
				$value = preg_replace('/ 00:00:00$/', '', $value);
				$value = preg_replace('/^0000-00-00/', '', $value);
				$membre[$key] = $value;
			}
		}

		$coordonnees = Queries::listCoordonnees($membre['id_membre']);

		$foundEmail = false;
		$foundPhone = false;
		$foundAddress = false;

		foreach ($coordonnees as $coordonnee) {
			$value = $coordonnee['coordonnee'];

			switch ($coordonnee['type_coordonnee']) {
				case 'email':
					if (!$foundEmail) {
						$foundEmail = true;
						$membre['email'] = $value;
					}
					break;

				case 'phone':
					if (!$foundPhone) {
						$foundPhone = true;
						$membre['telephone'] = $value;
					}
					break;

				case 'address':
					if (!$foundAddress) {
						$foundAddress = true;

						$address = json_decode($value, true); // give true as second argument to get an array.

						$lines = explode("\n", trim($address['address']));

						while (count($lines) < 3)
							$lines[] = '';

						$membre['adresse1'] = $lines[0];
						$membre['adresse2'] = $lines[1];
						$membre['adresse3'] = $lines[2];
						$membre['ville'] = trim($address['city']);
						$membre['code_postal'] = trim($address['code']);
						$membre['pays'] = trim($address['country']);
					}
					break;
			}
		}

		$membres[$index] = $membre; // Need to do that since forech provides copies of data and not actual refs to arrays.
	}

	echo json_encode($membres);
} else {
	echo 'AUCUN_MEMBRE_TROUVE';
}

$logger->debug('Liste membres duration: '.$stopWatch->getElapsedTime().'s');
