<?php

require_once __DIR__.'/../../vendor/autoload.php';

use MonCompte\Logger;
use MonCompte\StopWatch;
use MonCompte\DB\Queries;
use MonCompte\LdapSync;
use MonCompte\Format;
use Valitron\Validator;

$stopWatch = new StopWatch();
$stopWatch->start();

$logger = Logger::getLogger('services/updateMembreProfile');

header("Content-type: application/json; charset=utf-8'");

$errors = [];
$response = [];
$message = null;

$formValues = $_GET;
$numeroMembreStr = @$formValues['numero_membre'];
$numeroMembre = @intval($numeroMembreStr);

$v = new Validator($formValues);

$v->rule('required',[
	'adresse1',
	'code_postal',
	'ville',
	'pays',
])->message('{field} doit être renseigné.');

$v->rule('email','email')->message('{field} n\'est pas une adresse email valide.');
$v->rule('in','statut',[null,'','single','couple','deceased'])->message('{field} n\'est pas valide.');
$v->rule('integer','enfants')->message('{field} n\'est pas un nombre entier.');

$MAX_LENGTHS = [
	'adresse1' => 35,
	'adresse2' => 35,
	'adresse3' => 35,
	'code_postal' => 20,
	'ville' => 50,
	'pays' => 50,
	'phone' => 20,
	'email' => 127,
];

if (!$numeroMembre) {
	$errors[] = "Invalid value for numero_membre: {$numeroMembreStr}";
} else if ($v->validate()) {
	foreach ($MAX_LENGTHS as $key => $value)
		$formValues[$key] = Format::limitLength($formValues[$key], $MAX_LENGTHS[$key]);

	Queries::saveMembreData($numeroMembre, $formValues);

	$membreData = Queries::findMembreBaseData($numeroMembre);

	$ldapResult = LdapSync::updateOrCreateProfile($numeroMembre, $membreData);

	if ($ldapResult) {
		// Then it's an error.
		$errors[] = "Ldap error updating status for #{$numeroMembre}: {$ldapResult}";
	}

	$message = 'Completed';
} else {
	foreach ($v->errors() as $fieldName => $fieldErrors) {
		foreach ($fieldErrors as $message) {
			$errors[] = $message;
		}
	}

	$logger->info('Found validation errors: '.print_r($errors,true));
}

if (count($errors) > 0)
	$response['errors'] = $errors;

echo json_encode($response);

$logger->debug('updateMembreProfile duration: '.$stopWatch->getElapsedTime().'s');
