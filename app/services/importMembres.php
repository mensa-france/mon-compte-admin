<?php

require_once __DIR__.'/../../vendor/autoload.php';

use MonCompte\Logger;
use MonCompte\Arrays;
use MonCompte\StopWatch;
use MonCompte\Format;
use MonCompte\OldLdapSync;
use MonCompte\DB\Queries;

$stopWatch = new StopWatch();
$stopWatch->start();

define('FILE_INPUT_NAME','csv-import');
define('CSV_SEPARATOR',"\t");
define('CSV_DELIMITER','"');
define('BATCH_SIZE',100);

$EXPECTED_FIELDS = [
	'numero_membre',
	'region',
	'civilite',
	'prenom',
	'nom',
	'date_naissance',
	'date_inscription',
	'email',
	'telephone',
	'adresse1',
	'adresse2',
	'adresse3',
	'ville',
	'code_postal',
	'pays',
];

$MEMBRES_FIELDS = [
	'region',
	'civilite',
	'prenom',
	'nom',
	'date_naissance',
	'date_inscription',
];

$logger = Logger::getLogger('services/importMembres');

$errors = [];
$message = null;

if (isset($_FILES[FILE_INPUT_NAME]) && $_FILES[FILE_INPUT_NAME]['tmp_name']) {
	$logger->info('Received file:'.$_FILES[FILE_INPUT_NAME]['tmp_name']);

	if (($handle = fopen($_FILES[FILE_INPUT_NAME]['tmp_name'], "r")) !== FALSE) {
		$labels = fgetcsv($handle,0,CSV_SEPARATOR,CSV_DELIMITER);

		if ($EXPECTED_FIELDS == $labels) {
			$mappedImportRows = [];

			while (($data = fgetcsv($handle,0,CSV_SEPARATOR,CSV_DELIMITER)) !== FALSE) {
				$namedData = [];
				foreach ($labels as $index => $label) {
					if (!isset($data[$index]))
						$data[$index] = '';

					$namedData[$label] = utf8_encode($data[$index]);
				}

				$namedData['numero_membre'] = preg_replace('/^0+/','',$namedData['numero_membre']); // Remove leading 0s.

				if (!isset($mappedImportRows[$namedData['numero_membre']]))
					$mappedImportRows[$namedData['numero_membre']] = [];

				$mappedImportRows[$namedData['numero_membre']] = $namedData;
			}

			$existingMembres = Queries::mapNumerosMembresWithIds();

			foreach ($mappedImportRows as $numeroMembre => $importData) {
				#$logger->debug('Processing member: '.$numeroMembre);

				if (isset($existingMembres[$numeroMembre])) {
					Queries::updateMembre($numeroMembre, Arrays::filterKeys($importData, $MEMBRES_FIELDS));
				} else {
					$logger->debug("Creating new member: {$numeroMembre}");

					Queries::startTransaction();
					Queries::createMembre($numeroMembre, Arrays::filterKeys($importData, $MEMBRES_FIELDS));

					$membreId = Queries::findMembreSystemId($numeroMembre);

					if ($importData['email'])
						Queries::createEmail($membreId, $importData['email']);

					if ($importData['telephone'])
						Queries::createPhone($membreId, $importData['telephone']);

					if ($importData['ville'])
						Queries::createAddress($membreId, $importData['adresse1'], $importData['adresse2'], $importData['adresse3'], $importData['ville'], $importData['code_postal'], $importData['pays']);

					Queries::commit();

					$ldapResult = OldLdapSync::migrer_vers_LDAP($importData);

					if ($ldapResult) {
						// Then it's an error.
						$errors[] = "Ldap error creating member #{$numeroMembre}: {$ldapResult}";
						break; // Exit loop.
					}
				}
			}

			$message = 'Completed';
		} else {
			$logger->error('File does not match expected fields.');
			$errors[] = 'Le fichier ne respecte pas la nomenclature attendue.';
		}
	} else {
		$logger->error('Unable to read file:'.$_FILES[FILE_INPUT_NAME]['tmp_name']);
		$errors[] = 'Erreur lors de la lecture du fichier.';
	}
} else {
	$logger->error('No file received.');
	$errors[] = 'Fichier non reçu.';
}

$response = [
	'message' => $message
];

if (count($errors) > 0)
	$response['errors'] = $errors;

echo json_encode($response);

$logger->debug('Import membres duration: '.$stopWatch->getElapsedTime().'s');
