<?php

// On utilise MeekroDB pour eviter le cout en memoire imposé par doctrine.

require_once __DIR__.'/../vendor/autoload.php';

use MonCompte\DB\Queries;
use MonCompte\Sync;

Timer::start('sync');

echo "Syncing membres from file...\n";

if (count($argv) < 2)
	die("No file specified.\n");

$sourceFilePath = $argv[1];

if (!preg_match('/^\//', $sourceFilePath))
	$sourceFilePath = getcwd().'/'.$sourceFilePath;

if (!file_exists($sourceFilePath))
	die("Invalid file: ".$sourceFilePath."\n");

echo "Using source file: ".$sourceFilePath."\n";

Timer::start('read json');
$sourceData = json_decode(file_get_contents($sourceFilePath),true);
Timer::stop();


echo "Membre count in json: ".count($sourceData)."\n";

Timer::start('updates');
foreach (Queries::listNumerosMembres() as $numeroMembre) {
	if (isset($sourceData[$numeroMembre])) {
		$def = $sourceData[$numeroMembre];

		if (isset($def['infos'])) {
			Sync::updateMembre($numeroMembre, $def);
			echo '.';
		} else {
			echo '!';
		}
		unset($sourceData[$numeroMembre]);
	} else {
		echo 'x';
	}
}
Timer::stop();

Timer::start('creates');
foreach ($sourceData as $numeroMembreStr => $def) {
	sync::createMembre(intval($numeroMembreStr), $def);

	echo '+';
	unset($sourceData[$numeroMembreStr]);
}
Timer::stop();

echo "\n";
echo Timer::result();
echo "done.\n";

