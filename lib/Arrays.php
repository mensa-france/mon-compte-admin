<?php

namespace MonCompte;

class Arrays {
	public static function translateKeys($source, $mapping) {
		$result = [];

		foreach ($mapping as $originalKey => $targetKey)
			if (isset($source[$originalKey])) {
				$value = $source[$originalKey];
				$result[$targetKey] = $value;
			}

		return $result;
	}

	public static function filterKeys($source, $validKeys) {
		$result = [];

		foreach ($validKeys as $key) {
			if (isset($source[$key]))
				$result[$key] = $source[$key];
			else
				$result[$key] = null;
		}

		return $result;
	}
}
