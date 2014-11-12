<?php

namespace MonCompte;

use DateTime;

class Format {
	public static function limitLength($value, $maxLength) {
		if (is_string($value))
			$value = substr(trim($value), 0, $maxLength);

		return $value;
	}

	public static function date($value) {
		$result =  $value->format('Y-m-d');

		if ($result == '-0001-11-30') // Then date in db is 0000-00-00
			return null;

		return $result;
	}

	public static function filterDateTime($dateTime) {
		if (!$dateTime)
			$dateTime = null;
		else if (!($dateTime instanceof DateTime)) {
			if (is_string($dateTime))
				$dateTime = self::filterStringDate($dateTime);

			$dateTime = new DateTime($dateTime);
		}

		return $dateTime;
	}

	public static function filterStringDate($stringDate) {
		return preg_replace('/^([0-9]{2})\/([0-9]{2})\/([0-9]{4})$/', '$3-$2-$1', $stringDate);
	}

	public static function serializeAddressValue($adresse1='', $adresse2='', $adresse3='', $ville='', $codePostal='', $pays='') {
		return json_encode([
			'address' => trim("{$adresse1}\n{$adresse2}\n{$adresse3}"),
			'city' => $ville,
			'code' => $codePostal,
			'country' => $pays,
		]);
	}

	public static function serializeAddressValueFromArray($data) {
		return self::serializeAddressValue($data['adresse1'], $data['adresse2'], $data['adresse3'], $data['ville'], $data['code_postal'], $data['pays']);
	}

	public static function trimAll($array) {
		foreach ($array as $key => $value) {
			if (is_string($value))
				$array[$key] = trim($value);
		}

		return $array;
	}
}
