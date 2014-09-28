<?php

require_once __DIR__.'/../../vendor/autoload.php';

use MonCompte\Logger;
use MonCompte\Format;
use MonCompte\StopWatch;
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
				#$logger->debug('Processing member: '.$numeroMembre);

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
						foreach ($nonMatchingDates as $startDate => $cotisationData) {
							if (isset($newCotisationsByDate[$startDate])) {
								// then it's a new cotisation.

								$logger->info("Creating cotisation for membre {$numeroMembre} starting {$startDate}.");

								unset($cotisationData['numero_membre']);
								$cotisationData['montant'] = preg_replace('/,/', '.', $cotisationData['montant']);

								Queries::createCotisation($memberId, $cotisationData);
							} else {
								// do nothing for now.
								// should not really happen anyway.
								$logger->warn("Found deleted cotisation for membre: {$numeroMembre} starting {$startDate}.");
							}
						}
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
