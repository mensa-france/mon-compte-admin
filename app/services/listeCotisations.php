<?php

require_once __DIR__.'/../../vendor/autoload.php';

use MonCompte\Logger;
use MonCompte\StopWatch;
use MonCompte\DB\Queries;

define('NUMERO_MEMBRE_PARM', 'numero_membre');

header("Content-type: application/json; charset=utf-8'");

$stopWatch = new StopWatch();
$stopWatch->start();

$logger = Logger::getLogger('services/listeCotisation');

$errors = [];
$response = [];
$message = null;

$numeroMembreStr = @$_GET[NUMERO_MEMBRE_PARM];
$numeroMembre = @intval($numeroMembreStr);

if (!$numeroMembre)
	$errors[] = sprintf("Invalid value for %s: %s",NUMERO_MEMBRE_PARM,$numeroMembreStr);
else {
	$membreId = Queries::findMembreId($numeroMembre);

	if (!$membreId)
		$errors[] = sprintf('Member not found with %s: %u',NUMERO_MEMBRE_PARM,$numeroMembre);
	else
		$response['cotisations'] = Queries::listCotisationsDataOnly($membreId);
}

if (count($errors) > 0)
	$response['errors'] = $errors;

echo json_encode($response);

$logger->debug('deleteCotisation duration: '.$stopWatch->getElapsedTime().'s');
