<?php

namespace MonCompte;

use MonCompte\DB\Queries;
use MonCompte\Arrays;

class Sync {
	private static $MEMBER_KEY_MAPPING = [
		'civilite' => [
			'/^(mister)|(M[,\.;\?]?(de)?)$/' => 'mister',
			'/^Mll?e$/' => 'ms',
			'/^Mme$/' => 'mrs',
		],
	];

	private static $UPDATE_MAPPING = [
		'nom' => 'nom',
		'prenom' => 'prenom',
		'civilite' => 'civilite',
		'id_region' => 'region',
		'date_naissance' => 'date_naissance',
		'inscription' => 'date_inscription',
	];

	private static $CREATE_MAPPING = [
		'nom' => 'nom',
		'prenom' => 'prenom',
		'civilite' => 'civilite',
		'id_region' => 'region',
		'date_naissance' => 'date_naissance',
		'inscription' => 'date_inscription',
		'enfants' => 'enfants',
		'statut' => 'statut',
		'devise' => 'devise',
		'pass' => 'ancien_mot_de_passe', // Pas trop sur qu'on devrait importer ca...
	];

	private static $COTISATION_MAPPING = [
		'tarif' => 'tarif',
		'montant' => 'montant',
		'debut' => 'date_debut',
		'fin' => 'date_fin',
		'region' => 'region',
	];

	private static function normalizeMembre($source, $decodeHtmlEntities) {
		$result = [];

		foreach ($source as $key => $value) {
			if ($decodeHtmlEntities)
				$value = html_entity_decode($value);

			if (isset(self::$MEMBER_KEY_MAPPING[$key]))
				$value = self::normalizeValue($value,self::$MEMBER_KEY_MAPPING[$key]);

			$result[$key] = $value;
		}

		return $result;
	}

	private static function normalizeValue($value, $mapping) {
		foreach ($mapping as $regexp => $normalizedValue) {
			if (preg_match($regexp, $value))
				return $normalizedValue;
		}

		return $value;
	}

	public static function updateMembre($numeroMembre, $data) {
		$updateValues = Arrays::translateKeys($data['infos'], self::$UPDATE_MAPPING);
		$updateValues = self::normalizeMembre($updateValues,true);
		Queries::updateMembre($numeroMembre, $updateValues);

		if (isset($data['cotisations']))
			self::updateCotisations($numeroMembre, $data['cotisations']);
	}

	public static function createMembre($numeroMembre, $data) {
		$createValues = Arrays::translateKeys($def['infos'], self::$CREATE_MAPPING);
		$createValues = self::normalizeMembre($createValues,true);
		$createValues['id_ancien_si'] = $numeroMembre;
		//echo "\n>>>>".print_r($createValues)."\n".print_r($def,true)."\n";
		Queries::createMembre($createValues);

		if (isset($data['cotisations']))
			self::updateCotisations($numeroMembre, $data['cotisations']);
	}

	private static function updateCotisations($numeroMembre, $dataCotisations) {
		//echo ">>>>>>".$numeroMembre."  ".print_r($dataCotisations)."\n";

		$membreSystemId = Queries::findMembreSystemId($numeroMembre);

		$existingCotisations = Queries::listCotisations($membreSystemId);

		// Enleve les cotisations existantes des informations à insérer.
		foreach ($existingCotisations as $cotisation) {
			$debut = preg_replace('/ 00:00:00$/', '', $cotisation['date_debut']);
			if (isset($dataCotisations[$debut]))
				unset($dataCotisations[$debut]);
		}

		foreach ($dataCotisations as $cotisationData) {
			$cotisationData = Arrays::translateKeys($cotisationData, self::$COTISATION_MAPPING);
			$cotisationData['id_membre'] = $membreSystemId;

			Queries::createCotisation($cotisationData);
		}
	}
}
