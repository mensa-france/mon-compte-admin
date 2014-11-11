<?php

require_once __DIR__.'/../../vendor/autoload.php';

use MonCompte\Logger;
use MonCompte\StopWatch;
use MonCompte\DB\Queries;

$stopWatch = new StopWatch();
$stopWatch->start();

$logger = Logger::getLogger('services/updateMembreProfile');

header("Content-type: application/json; charset=utf-8'");

$errors = [];
$response = [];
$message = null;

$numeroMembreStr = @$_GET['numero_membre'];
$numeroMembre = @intval($numeroMembreStr);

if (!$numeroMembre) {
	$errors[] = "Invalid value for numero_membre: {$numeroMembreStr}";
} else {
	$membreData = Queries::setMembreBaseData($numeroMembre, $_GET);
	$message = 'Completed';
}

if (count($errors) > 0)
	$response['errors'] = $errors;

echo json_encode($response);

$logger->debug('updateMembreProfile duration: '.$stopWatch->getElapsedTime().'s');
