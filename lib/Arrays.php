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
}
