<?php

require_once __DIR__.'/../../vendor/autoload.php';

use MonCompte\Doctrine;
use MonCompte\Logger;

define('CSV_SEPARATOR',"\t");
define('CSV_DELIMITER','"');

$timestamp = date('Ymd-Hms');

header("Content-type: text/csv; charset=iso-8859-1'");
header("Content-Disposition: attachment; filename={$timestamp}-export_coordonnees_membres_mensa.csv");

$logger = Logger::getLogger('services/exportCoordonnees');

$membres = Doctrine::listMembres();
$logger->info('Found membres count: '.count($membres));

if (count($membres) > 0) {
	$keys = [];

	foreach ($membres[0]->jsonSerialize() as $key => $value) {
		array_push($keys, $key);
	}

	$outstream = fopen("php://output", 'w');
	function __outputCSV(&$membre, $key, $filehandler) {
		$vals = $membre->jsonSerialize(); // Convert Membres instance to value array.

		foreach ($vals as $key => $value) // Convert all values to iso latin
			$vals[$key] = utf8_decode($value);

		fputcsv($filehandler, $vals, CSV_SEPARATOR, CSV_DELIMITER);
		Doctrine::detach($membre); // Free doctrine associated resources to avoid memory buildup.
	}
	fputcsv($outstream, $keys, CSV_SEPARATOR, CSV_DELIMITER);
	array_walk($membres, '__outputCSV', $outstream);
	fclose($outstream);
} else {
	echo 'AUCUN_MEMBRE_TROUVE';
}
