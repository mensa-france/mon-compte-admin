<?php

ini_set('memory_limit', '256M');

require_once __DIR__.'/../../vendor/autoload.php';

use MonCompte\Doctrine;
use MonCompte\Logger;
use MonCompte\Doctrine\Entities\Membres;

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
];

$logger = Logger::getLogger('services/importMembres');

$errors = [];
$message = null;

if (isset($_FILES[FILE_INPUT_NAME]) && $_FILES[FILE_INPUT_NAME]['tmp_name']) {
	$logger->info('Received file:'.$_FILES[FILE_INPUT_NAME]['tmp_name']);

	if (($handle = fopen($_FILES[FILE_INPUT_NAME]['tmp_name'], "r")) !== FALSE) {
		$labels = fgetcsv($handle,0,CSV_SEPARATOR,CSV_DELIMITER);

		if ($labels && !array_diff( $labels , $EXPECTED_FIELDS )) {
			$mappedImportRows = [];

			while (($data = fgetcsv($handle,0,CSV_SEPARATOR,CSV_DELIMITER)) !== FALSE) {
				$namedData = [];
				foreach ($labels as $index => $label)
					$namedData[$label] = utf8_encode($data[$index]);

				if (!isset($mappedImportRows[$namedData['numero_membre']]))
					$mappedImportRows[$namedData['numero_membre']] = [];

				$mappedImportRows[$namedData['numero_membre']] = $namedData;
			}

			$mappedMembres = [];

			foreach (Doctrine::listMembres() as $membre)
				$mappedMembres[$membre->getIdAncienSi()] = $membre;

			$membreBuffer = [];

			foreach ($mappedImportRows as $numeroMembre => $importData) {
				#$logger->debug('Processing member: '.$numeroMembre);

				$modifiedMembre = null;

				if (isset($mappedMembres[$numeroMembre])) {
					$currentMembre = $mappedMembres[$numeroMembre];

					$currentMembre->setCivilite($importData['civilite']);
					$currentMembre->setPrenom($importData['prenom']);
					$currentMembre->setNom($importData['nom']);
					$currentMembre->setRegion($importData['region']);
					$currentMembre->setDateNaissance($importData['date_naissance']);
					$currentMembre->setDateInscription($importData['date_inscription']);

					$modifiedMembre = $currentMembre;
					$modified = true;
				} else {
					$logger->debug("Creating new member: {$numeroMembre}");

					$newMembre = new Membres();

					$newMembre->setIdAncienSi($numeroMembre);
					$newMembre->setCivilite($importData['civilite']);
					$newMembre->setPrenom($importData['prenom']);
					$newMembre->setNom($importData['nom']);
					$newMembre->setRegion($importData['region']);
					$newMembre->setDateNaissance($importData['date_naissance']);
					$newMembre->setDateInscription($importData['date_inscription']);

					$newMembre->setMotDePasse('*'); // Dummy value
					$newMembre->setAncienMotDePasse(''); // Dummy value
					$newMembre->setDevise(''); // Dummy value
					$newMembre->setNote(''); // Dummy value

					$modifiedMembre = $newMembre;
					$modified = true;
				}

				if ($modifiedMembre) {
					$logger->debug("Persisting membre: {$numeroMembre}");
					Doctrine::persist($modifiedMembre);
					$membreBuffer[] = $modifiedMembre; // Used to free memory.
				}

				if ($modifiedMembre && count($membreBuffer) >= BATCH_SIZE) {
					Doctrine::flush();

					foreach ($membreBuffer as $entity)
						Doctrine::detach($entity);

					$membreBuffer = [];
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
