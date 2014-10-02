<?php

require_once __DIR__.'/../../vendor/autoload.php';

use MonCompte\Logger;
use MonCompte\StopWatch;
use MonCompte\DB\Queries;
use MonCompte\LdapSync;

$stopWatch = new StopWatch();
$stopWatch->start();


$logger = Logger::getLogger('services/updateMembresStatus');

$errors = [];
$message = null;

$membreExpirationMap = Queries::mapNumerosMembresWithCotisationExpirationTimestamps();

foreach ($membreExpirationMap as $numeroMembre => $expirationTimestamp) {
	$ldapResult = LdapSync::maj_statut_cotisant($numeroMembre, $expirationTimestamp);

	if ($ldapResult) {
		// Then it's an error.
		$errors[] = "Ldap error updating status for #{$numeroMembre}: {$ldapResult}";
		break; // Exit loop.
	}
}

if (count($errors) == 0)
	$message = 'Completed';

$response = [
	'message' => $message
];

if (count($errors) > 0)
	$response['errors'] = $errors;

echo json_encode($response);

$logger->debug('Update Membres Status duration: '.$stopWatch->getElapsedTime().'s');
