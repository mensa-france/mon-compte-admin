<?php

namespace MonCompte;

use MonCompte\DB\Queries;
use MonCompte\Arrays;

class Sync {
	private static $COORDONNEE_TYPE_MAPPING = [
		'/^T(é)|(&eacute;)l\..*/' => 'phone',
		'/^Courriel$/' => 'email',
		'/^address$/' => 'address',
	];

	private static $MEMBER_KEY_MAPPING = [
		'civilite' => [
			'/^(mister)|(M[,\.;\?]?(de)?)$/' => 'mister',
			'/^Mll?e$/' => 'ms',
			'/^Mme$/' => 'mrs',
		],
	];

	private static $COORDONNEE_MAPPING = [
		'type' => 'type_coordonnee',
		'value' => 'coordonnee',
		'private' => 'reservee_gestion_asso',
		'usage' => 'usage_coordonnee',
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

	private static function htmlDecodeArray($source) {
		$result = [];

		foreach ($source as $key => $value)
			$result[$key] = html_entity_decode($value);

		return $result;
	}

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

		$membreSystemId = Queries::findMembreSystemId($numeroMembre);

		if (isset($data['cotisations']))
			self::updateCotisations($membreSystemId, $data['cotisations']);
	}

	public static function createMembre($numeroMembre, $data) {
		$createValues = Arrays::translateKeys($data['infos'], self::$CREATE_MAPPING);
		$createValues = self::normalizeMembre($createValues,true);
		$createValues['id_ancien_si'] = $numeroMembre;
		//echo "\n>>>>".print_r($createValues)."\n".print_r($def,true)."\n";
		Queries::createMembre($createValues);

		$membreSystemId = Queries::findMembreSystemId($numeroMembre);

		if (isset($data['cotisations']))
			self::updateCotisations($membreSystemId, $data['cotisations']);

		if (isset($data['contacts']))
			self::createCoordonnees($membreSystemId, $data['contacts']);
	}

	private static function updateCotisations($membreSystemId, $dataCotisations) {
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

	private static function createCoordonnees($membreSystemId, $dataCoordonnees) {
		$coordonneesList = [];

		foreach ($dataCoordonnees as $key => $value) {
			$createData = [
				'usage' => 'home',
			];

			foreach (self::$COORDONNEE_TYPE_MAPPING as $regexp => $typeName) {
				if (preg_match($regexp, $key)) {
					$createData['type'] = $typeName;
					break;
				}
			}

			if (isset($createData['type'])) {
				// if no type then we don't care about this value.

				if (isset($value['public'])) {
					$createData['private'] = false;
					$createData['value'] = $value['public'][0];
				} else {
					$createData['private'] = true;
					$createData['value'] = $value['prive'][0];
				}

				if ($createData['type'] == 'address') {
					$createData['value'] = self::htmlDecodeArray($createData['value']);
					$createData['value'] = json_encode($createData['value']);
				} else {
					$createData['value'] = html_entity_decode($createData['value']);
				}

				$createData = Arrays::translateKeys($createData, self::$COORDONNEE_MAPPING);
				$createData['id_membre'] = $membreSystemId;

				array_push($coordonneesList, $createData);
			}
		}


		Queries::createCoordonees($coordonneesList);
	}
}
