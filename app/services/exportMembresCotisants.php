<?php

require_once __DIR__.'/../../vendor/autoload.php';

use MonCompte\DB\Queries;
use MonCompte\Logger;
use MonCompte\StopWatch;

$stopWatch = new StopWatch();
$stopWatch->start();

define('CSV_SEPARATOR',",");
define('CSV_DELIMITER','"');

$RESULT_KEYS = [
	'prenom',
	'nom',
	'numero_membre',
	'fin_cotisation',
];

$timestamp = date('Ymd-Hms');

header("Content-type: text/csv; charset=iso-8859-1'");
header("Content-Disposition: attachment; filename={$timestamp}-export_membres_cotisants_mensa.csv");

$logger = Logger::getLogger('services/exportMembresCotisants');

$membres = Queries::listMembresCotisants();
$logger->info('Found membres count: '.count($membres));

if (count($membres) > 0) {
	$outstream = fopen("php://output", 'w');

	function __outputCSV($membre, $key, $filehandler) {
		global $RESULT_KEYS;

		$values = [];

		foreach ($RESULT_KEYS as $key) {
			$value = isset($membre[$key])?utf8_decode($membre[$key]):'';
			$values[] = $value;
		}

		fputcsv($filehandler, $values, CSV_SEPARATOR, CSV_DELIMITER);
	}

	//fputcsv($outstream, $RESULT_KEYS, CSV_SEPARATOR, CSV_DELIMITER);
	array_walk($membres, '__outputCSV', $outstream);
	fclose($outstream);
} else {
	echo 'AUCUN_MEMBRE_TROUVE';
}

$logger->debug('Export membres duration: '.$stopWatch->getElapsedTime().'s');
