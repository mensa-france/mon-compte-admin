<?php

ini_set('memory_limit', '256M');

require_once __DIR__.'/../../vendor/autoload.php';

use MonCompte\Doctrine;
use MonCompte\Logger;
use MonCompte\StopWatch;

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

		if ($labels && !array_diff( $labels , $EXPECTED_FIELDS )) {
			$mappedCotisations = [];

			while (($data = fgetcsv($handle,0,CSV_SEPARATOR,CSV_DELIMITER)) !== FALSE) {
				$namedData = [];
				foreach ($labels as $index => $label) {
					if (!isset($data[$index]))
						$data[$index] = '';

					$namedData[$label] = utf8_encode($data[$index]);
				}

				$namedData['numero_membre'] = preg_replace('/^0+/','',$namedData['numero_membre']); // Remove leading 0s.

				if (!isset($mappedCotisations[$namedData['numero_membre']]))
					$mappedCotisations[$namedData['numero_membre']] = [];

				$mappedCotisations[$namedData['numero_membre']][] = $namedData;
			}

			$mappedMembres = [];

			foreach (Doctrine::listMembres() as $membre)
				$mappedMembres[$membre->getIdAncienSi()] = $membre;

			$membreBuffer = [];

			foreach ($mappedCotisations as $numeroMembre => $cotisationData) {
				#$logger->debug('Processing member: '.$numeroMembre);

				if (isset($mappedMembres[$numeroMembre])) {
					$currentMembre = $mappedMembres[$numeroMembre];
					$existingCotisations = $currentMembre->getCotisations();

					$existingCotisationsByDate = [];
					$newCotisationsByDate = [];

					foreach ($existingCotisations as $cotisation)
						$existingCotisationsByDate[$cotisation->getDateDebut()] = $cotisation;

					foreach ($cotisationData as $cotisation)
						$newCotisationsByDate[$cotisation['date_debut']] = $cotisation;

					$nonMatchingDates = array_diff_key($newCotisationsByDate,$existingCotisationsByDate);

					if ($nonMatchingDates) {
						$modified = false;

						foreach ($nonMatchingDates as $startDate => $cotisationData) {
							if (isset($newCotisationsByDate[$startDate])) {
								// then it's a new cotisation.

								$logger->info("Creating cotisation for membre {$numeroMembre} starting {$startDate}.");
								$currentMembre->addCotisation($cotisationData);
								$modified = true;
							} else {
								// do nothing for now.
								// should not really happen anyway.
								$logger->error("Found deleted cotisation for membre: {$numeroMembre} starting {$startDate}.");
							}
						}

						if ($modified) {
							$logger->debug("Persisting membre: {$numeroMembre}");
							Doctrine::persist($currentMembre);
							$membreBuffer[] = $currentMembre; // Used to free memory.
						}
					} else {
						// No modification to do, freeing entity.
						//$membreBuffer[] = $currentMembre;

						// Somehow detaching non modified entities generates an error.
					}

					if (count($membreBuffer) >= BATCH_SIZE) {
						Doctrine::flush();

						foreach ($membreBuffer as $entity)
							Doctrine::detach($entity);

						$membreBuffer = [];
					}
				} else {
					$logger->error("Member not found: {$numeroMembre}");
					$error[] = "Membre non trouvé: {$numeroMembre}";
				}
			}

			Doctrine::flush();

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
