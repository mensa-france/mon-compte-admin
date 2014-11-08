<?php

require_once __DIR__.'/../../vendor/autoload.php';

use MonCompte\Logger;
use MonCompte\StopWatch;
use MonCompte\DB\Queries;

define('NUMERO_MEMBRE_PARM', 'numero_membre');
define('COTISATION_ID_PARM', 'cotisation_id');

$stopWatch = new StopWatch();
$stopWatch->start();

$logger = Logger::getLogger('services/deleteCotisation');

$errors = [];
$response = [];
$message = null;

$numeroMembreStr = @$_GET[NUMERO_MEMBRE_PARM];
$numeroMembre = @intval($numeroMembreStr);

$cotisationIdStr = @$_GET[COTISATION_ID_PARM];
$cotisationId = @intval($cotisationIdStr);

$logger->debug(sprintf("Received %s: %u",COTISATION_ID_PARM,$cotisationId));

if (!$numeroMembre)
	$errors[] = sprintf("Invalid value for %s: %s",NUMERO_MEMBRE_PARM,$numeroMembreStr);

if (!$cotisationId)
	$errors[] = sprintf("Invalid value for %s: %s",COTISATION_ID_PARM,$cotisationIdStr);

if ($numeroMembre && $cotisationId) {
	$logger->debug(sprintf("Deleting cotisation: %s (#%s)",$cotisationId,$numeroMembre));

	Queries::deleteCotisation($numeroMembre,$cotisationId);
}

if (count($errors) > 0)
	$response['errors'] = $errors;

echo json_encode($response);

$logger->debug('deleteCotisation duration: '.$stopWatch->getElapsedTime().'s');
