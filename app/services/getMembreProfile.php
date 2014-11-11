<?php

require_once __DIR__.'/../../vendor/autoload.php';

use MonCompte\Logger;
use MonCompte\StopWatch;
use MonCompte\DB\Queries;

$stopWatch = new StopWatch();
$stopWatch->start();

$logger = Logger::getLogger('services/getMembreProfile');

header("Content-type: application/json; charset=utf-8'");

$errors = [];
$response = [];
$message = null;

$numeroMembreStr = @$_GET['numero_membre'];
$numeroMembre = @intval($numeroMembreStr);


if (!$numeroMembre) {
	$errors[] = "Invalid value for numero_membre: {$numeroMembreStr}";
} else {
	$logger->debug("Profile requested: #{$numeroMembre}");

	$membreData = Queries::findMembreBaseData($numeroMembre);

	if (!$membreData) {
		$errors[] = "Member not found: {$numeroMembre}";
	} else {
		$idMembre = $membreData['id_membre'];
		unset($membreData['id_membre']);


		$cotisations = Queries::listCotisationsDataOnly($idMembre);
		$response['cotisations'] = $cotisations;

		if (count($cotisations) > 0)
			$membreData['date_expiration'] = $cotisations[0]['date_fin']; # first cotisation is the most recent one.
		else
			$membreData['date_expiration'] = '1970-01-01';

		$response['profile'] = $membreData;
	}
}

if (count($errors) > 0)
	$response['errors'] = $errors;

echo json_encode($response);

$logger->debug('getMembreProfile duration: '.$stopWatch->getElapsedTime().'s');
