<?php

require_once __DIR__.'/../../vendor/autoload.php';

use MonCompte\DB\Queries;
use MonCompte\Logger;
use MonCompte\StopWatch;

$stopWatch = new StopWatch();
$stopWatch->start();

define('CSV_SEPARATOR',"\t");
define('CSV_DELIMITER','"');

$RESULT_KEYS = [
	'numero_membre',
	'nom',
	'prenom',
	'region',
	'email',
	'telephone',
	'adresse1',
	'adresse2',
	'adresse3',
	'ville',
	'code_postal',
	'pays',
];

$timestamp = date('Ymd-Hms');

header("Content-type: text/csv; charset=iso-8859-1'");
header("Content-Disposition: attachment; filename={$timestamp}-export_coordonnees_membres_mensa.csv");

$logger = Logger::getLogger('services/exportCoordonnees');

$membres = Queries::listMembres();
$logger->info('Found membres count: '.count($membres));

if (count($membres) > 0) {
	$outstream = fopen("php://output", 'w');

	function __outputCSV($membre, $key, $filehandler) {
		global $RESULT_KEYS;

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
						$membre = Queries::parseAddress($value, $membre);
					}
					break;
			}
		}

		$values = [];

		foreach ($RESULT_KEYS as $key) {
			$value = isset($membre[$key])?utf8_decode($membre[$key]):'';
			$values[] = $value;
		}

		fputcsv($filehandler, $values, CSV_SEPARATOR, CSV_DELIMITER);
	}

	fputcsv($outstream, $RESULT_KEYS, CSV_SEPARATOR, CSV_DELIMITER);
	array_walk($membres, '__outputCSV', $outstream);
	fclose($outstream);
} else {
	echo 'AUCUN_MEMBRE_TROUVE';
}

$logger->debug('Export membres duration: '.$stopWatch->getElapsedTime().'s');
