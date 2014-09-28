<?php

require_once __DIR__.'/../vendor/autoload.php';

use MonCompte\DB\Queries;
use MonCompte\StopWatch;

$sw = new StopWatch();
$sw->start();

$data = [
	'id_ancien_si' => 10000000,
	'prenom' => 'Joe',
	'nom' => 'Black',
];

$result = Queries::createMembre($data);
$result2 = Queries::findMembreId(10000000);

echo ">>>>>> [{$result}]\n";
echo ">>>>>> [{$result2}]\n";

$time = $sw->getElapsedTime();

echo ">>>>>>>> {$time}\n";


