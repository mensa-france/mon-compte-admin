<?php

require_once __DIR__.'/../../vendor/autoload.php';

use MonCompte\DB\Queries;
use MonCompte\Logger;
use MonCompte\StopWatch;

$stopWatch = new StopWatch();
$stopWatch->start();

define('DEFAULT_PAGE_SIZE', 10);
header("Content-type: application/json; charset=utf-8'");

$logger = Logger::getLogger('services/listeMembre');

$allMembres = Queries::listMembres();

$pageIndex = max(1, @intval($_GET['pageIndex']));
$pageSize = max(DEFAULT_PAGE_SIZE, @intval($_GET['pageSize']));
$totalCount = count($allMembres);
$pageCount = ceil($totalCount/$pageSize);

$logger->info("Found membres count: ($totalCount}");

$membres = array_slice($allMembres, ($pageIndex-1)*$pageSize, $pageSize);

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

echo json_encode([
	'totalCount' => $totalCount,
	'pageCount' => $pageCount,
	'pageSize' => $pageSize,
	'currentPage' => $pageIndex,
	'results' => $membres,
]);

$logger->debug('Liste membres duration: '.$stopWatch->getElapsedTime().'s');
