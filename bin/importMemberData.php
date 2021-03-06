<?php

require_once __DIR__.'/../vendor/autoload.php';

use MonCompte\Arrays;
use MonCompte\StopWatch;
use MonCompte\Format;
use MonCompte\LdapSync;
use MonCompte\DB\Queries;

$stopWatch = new StopWatch();
$stopWatch->start();

if (!isset($argv[1]))
	die("Usage:\n  {$argv[0]} <PATH_TO_CSV>\n\n* CSV file must use tabs as separator and be iso-8859-1 encoded.\n");

$filePath = $argv[1];

if (!file_exists($filePath))
	die("File not found: {$filePath}\n");

define('FILE_INPUT_NAME','csv-import');
define('CSV_SEPARATOR',"\t");
define('CSV_DELIMITER','"');
define('BATCH_SIZE',100);

$EXPECTED_FIELDS = [
	'numero_membre',
	'region',
	'civilite',
	'nom',
	'prenom',
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
	'email_conf',
	'tel_conf',
	'adresse_conf',
];

$MEMBRES_FIELDS = [
	'region',
	'civilite',
	'prenom',
	'nom',
	'date_naissance',
	'date_inscription',
];

function isTrue($value) {
	return $value == 'VRAI';
}

if (($handle = fopen($filePath, "r")) !== FALSE) {
	$labels = fgetcsv($handle,0,CSV_SEPARATOR,CSV_DELIMITER);

	if ($EXPECTED_FIELDS == $labels) {
		$mappedImportRows = [];

		while (($data = fgetcsv($handle,0,CSV_SEPARATOR,CSV_DELIMITER)) !== FALSE) {
			$namedData = [];
			foreach ($labels as $index => $label) {
				if (!isset($data[$index]))
					$data[$index] = '';

				$namedData[$label] = utf8_encode(trim($data[$index]));
			}

			$namedData['numero_membre'] = preg_replace('/^0+/','',$namedData['numero_membre']); // Remove leading 0s.

			if (!isset($mappedImportRows[$namedData['numero_membre']]))
				$mappedImportRows[$namedData['numero_membre']] = [];

			$mappedImportRows[$namedData['numero_membre']] = $namedData;
		}

		$existingMembres = Queries::mapNumerosMembresWithIds();
		echo 'Existing membres count: '.count($existingMembres)."\n";
		echo 'Membres to import: '.count($mappedImportRows)."\n";

		$processedCount = 0;

		foreach ($mappedImportRows as $numeroMembre => $importData) {
			if (($processedCount%100) == 0)
				echo '.';

			$isCreate = false;

			Queries::startTransaction();

			$membreId = null;

			if (!$importData['prenom'])
				$importData['prenom'] = '-';

			if (!$importData['nom'])
				$importData['nom'] = '-';

			if (isset($existingMembres[$numeroMembre])) {
				Queries::updateMembre($numeroMembre, Arrays::filterKeys($importData, $MEMBRES_FIELDS));
				$membreId = $existingMembres[$numeroMembre];
			} else {
				echo "!";
				Queries::createMembre($numeroMembre, Arrays::filterKeys($importData, $MEMBRES_FIELDS));
				$membreId = Queries::findMembreSystemId($numeroMembre);
				$isCreate = true;
			}

			Queries::deleteCoordonneesMembre($membreId); // Delete all of them to remove multiple values for same type.

			if ($importData['email'])
				Queries::createEmail($membreId, $importData['email'], isTrue($importData['email_conf']));

			if ($importData['telephone'])
				Queries::createPhone($membreId, $importData['telephone'], isTrue($importData['tel_conf']));

			if ($importData['ville']) {
				if (!$importData['pays'])
					$importData['pays'] = 'FRANCE';

				Queries::createAddress($membreId, $importData['adresse1'], $importData['adresse2'], $importData['adresse3'], $importData['ville'], $importData['code_postal'], $importData['pays'], isTrue($importData['adresse_conf']));
			}

			Queries::commit();

			if ($isCreate) {
				$ldapResult = LdapSync::migrer_vers_LDAP($importData);

				if ($ldapResult)
					// Then it's an error.
					die("\nLdap error creating member #{$numeroMembre}: {$ldapResult}\n");
			}

			$processedCount++;
		}

		echo "\n";
	} else {
		die("File does not match expected fields.\n");
	}
} else {
	die('Unable to read file: '.$filePath."\n");
}

echo 'Import completed successfully in: '.$stopWatch->getElapsedTime()."s\n";
