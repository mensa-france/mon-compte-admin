<?php

require_once __DIR__.'/../../vendor/autoload.php';

use MonCompte\Logger;
use MonCompte\Format;
use MonCompte\StopWatch;
use MonCompte\DB\Queries;
use MonCompte\LdapSync;

$stopWatch = new StopWatch();
$stopWatch->start();

define('FILE_INPUT_NAME','csv-import');
define('CSV_SEPARATOR',"\t");
define('CSV_DELIMITER','"');
define('BATCH_SIZE',100);

$EXPECTED_FIELDS = [
	'numero_membre',
	'region',
	'tarif',
	'montant',
	'date_debut',
	'date_fin',
];

$logger = Logger::getLogger('services/importCotisations');

$errors = [];
$message = null;

if (isset($_FILES[FILE_INPUT_NAME]) && $_FILES[FILE_INPUT_NAME]['tmp_name']) {
	$logger->info('Received file:'.$_FILES[FILE_INPUT_NAME]['tmp_name']);

	if (($handle = fopen($_FILES[FILE_INPUT_NAME]['tmp_name'], "r")) !== FALSE) {
		$labels = fgetcsv($handle,0,CSV_SEPARATOR,CSV_DELIMITER);

		if ($EXPECTED_FIELDS == $labels) {
			$mappedCotisations = [];

			while (($data = fgetcsv($handle,0,CSV_SEPARATOR,CSV_DELIMITER)) !== FALSE) {
				$namedData = [];
				foreach ($labels as $index => $label) {
					if (!isset($data[$index]))
						$data[$index] = '';

					$namedData[$label] = utf8_encode($data[$index]);

					if (preg_match('/^date_/', $label))
						$namedData[$label] = Format::filterStringDate($namedData[$label]);
				}

				$namedData['numero_membre'] = preg_replace('/^0+/','',$namedData['numero_membre']); // Remove leading 0s.

				if (!isset($mappedCotisations[$namedData['numero_membre']]))
					$mappedCotisations[$namedData['numero_membre']] = [];

				$mappedCotisations[$namedData['numero_membre']][] = $namedData;
			}

			$existingMembres = Queries::mapNumerosMembresWithIds();

			foreach ($mappedCotisations as $numeroMembre => $cotisationData) {
				if (isset($existingMembres[$numeroMembre])) {
					$memberId = $existingMembres[$numeroMembre];

					$existingCotisations = Queries::listCotisations($memberId);

					$existingCotisationsByDate = [];
					$newCotisationsByDate = [];

					foreach ($existingCotisations as $cotisation) {
						foreach ($cotisation as $key => $value) {
							// We need to remove the time from the date values.
							if (preg_match('/^date_/', $key))
								$cotisation[$key] = preg_replace('/ 00:00:00$/', '', $value);
						}

						$existingCotisationsByDate[$cotisation['date_debut']] = $cotisation;
					}

					foreach ($cotisationData as $cotisation)
						$newCotisationsByDate[$cotisation['date_debut']] = $cotisation;

					$nonMatchingDates = array_diff_key($newCotisationsByDate,$existingCotisationsByDate);

					if ($nonMatchingDates) {
						Queries::startTransaction();
						foreach ($nonMatchingDates as $startDate => $cotisation) {
							if (isset($newCotisationsByDate[$startDate])) {
								// then it's a new cotisation.

								$logger->info("Creating cotisation for membre {$numeroMembre} starting {$startDate}.");

								unset($cotisation['numero_membre']);
								$cotisation['montant'] = preg_replace('/,/', '.', $cotisation['montant']);

								Queries::createCotisation($memberId, $cotisation);
							} else {
								// do nothing for now.
								// should not really happen anyway.
								$logger->warn("Found deleted cotisation for membre: {$numeroMembre} starting {$startDate}.");
							}
						}
						Queries::commit();
					}

					$currentCotisationExpirationDate = '0000-00-00'; // Take no chance with default value.

					foreach ($existingCotisations as $cotisation)
						if ($cotisation['date_fin'] > $currentCotisationExpirationDate)
							$currentCotisationExpirationDate = $cotisation['date_fin'];

					foreach ($cotisationData as $cotisation) {
						if ($cotisation['date_fin'] > $currentCotisationExpirationDate)
							$currentCotisationExpirationDate = $cotisation['date_fin'];
					}

					$ldapResult = LdapSync::maj_statut_cotisant($numeroMembre, strtotime($currentCotisationExpirationDate));

					if ($ldapResult) {
						// Then it's an error.
						$errors[] = "Ldap error updating status for #{$numeroMembre}: {$ldapResult}";
						break; // Exit loop.
					}
				} else {
					$logger->error("Member not found: {$numeroMembre}");
					$error[] = "Membre non trouvé: {$numeroMembre}";
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

$logger->debug('Import cotisations duration: '.$stopWatch->getElapsedTime().'s');
