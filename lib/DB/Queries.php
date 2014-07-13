<?php

namespace MonCompte\DB;

use \DB as DB;

class Queries {
	private static $initialized;

	private static function initialize() {
		require __DIR__."/../../config/local_db.php";

		if (!self::$initialized) {
			self::$initialized = true;

			DB::$user = $dbParams['user'];
			DB::$password = $dbParams['password'];
			DB::$dbName = $dbParams['dbname'];
			DB::$host = $dbParams['host']; //defaults to localhost if omitted
			DB::$port = $dbParams['port']; // defaults to 3306 if omitted
			DB::$encoding = $dbParams['charset']; // defaults to latin1 if omitted
		}
	}

	public static function hasMembre($numeroMembre) {
		self::initialize();

		$result = DB::queryFirstRow('SELECT id_ancien_si FROM membres WHERE id_ancien_si = %i', $numeroMembre);

		if ($result)
			return true;

		return false;
	}

	public static function listNumerosMembres() {
		self::initialize();
		return DB::queryOneColumn('id_ancien_si', 'SELECT id_ancien_si FROM membres ORDER BY id_ancien_si');
	}

	public static function findMembre($numeroMembre) {
		self::initialize();
		return DB::queryFirstRow('SELECT * FROM membres WHERE id_ancien_si = %i', $numeroMembre);
	}

	public static function findMembreSystemId($numeroMembre) {
		$membreData = self::findMembre($numeroMembre);

		if ($membreData)
			return $membreData['id_membre'];

		return false;
	}

	public static function updateMembre($numeroMembre, $data) {
		self::initialize();
		DB::update('membres', $data, 'id_ancien_si = %i', $numeroMembre);
	}

	public static function createMembre($data) {
		self::initialize();
		DB::insert('membres', $data);
	}

	public static function listCotisations($membreSystemId) {
		self::initialize();
		return DB::query('SELECT * FROM cotisations WHERE id_membre = %i', $membreSystemId);
	}

	public static function createCotisation($data) {
		self::initialize();
		DB::insert('cotisations', $data);
	}

	public static function createCoordonees($data) {
		self::initialize();
		DB::insert('coordonnees', $data);
	}
}
