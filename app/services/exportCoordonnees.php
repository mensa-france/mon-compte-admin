<?php

ini_set('memory_limit', '256M');

require_once __DIR__.'/../../vendor/autoload.php';

use MonCompte\Doctrine;
use MonCompte\Logger;

define('CSV_SEPARATOR',';');
define('CSV_DELIMITER','"');

$timestamp = date('Ymd-Hms');

header("Content-type: text/csv");
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
	function __outputCSV(&$vals, $key, $filehandler) {
		$vals = $vals->jsonSerialize(); // Convert Membres instance to value array.
		fputcsv($filehandler, $vals, CSV_SEPARATOR, CSV_DELIMITER);
	}
	fputcsv($outstream, $keys, CSV_SEPARATOR, CSV_DELIMITER);
	array_walk($membres, '__outputCSV', $outstream);
	fclose($outstream);
}
