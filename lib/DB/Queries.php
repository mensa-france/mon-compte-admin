<?php

namespace MonCompte\DB;

use \DB as DB;
use \MonCompte\Format;

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

	public static function startTransaction() {
		self::initialize();
		return DB::startTransaction();
	}

	public static function commit() {
		self::initialize();
		return DB::commit();
	}

	public static function rollback() {
		self::initialize();
		return DB::rollback();
	}

	public static function hasMembre($numeroMembre) {
		self::initialize();

		$result = DB::queryFirstRow('SELECT id_ancien_si FROM membres WHERE id_ancien_si = %i', $numeroMembre);

		if ($result)
			return true;

		return false;
	}

	public static function findMembreId($numeroMembre) {
		self::initialize();
		return DB::queryOneField('id_membre', 'SELECT * FROM membres WHERE id_ancien_si = %i', $numeroMembre);
	}

	public static function listNumerosMembresWithIds() {
		self::initialize();
		return DB::query('SELECT id_ancien_si, id_membre FROM membres ORDER BY id_ancien_si');
	}

	public static function mapNumerosMembresWithIds() {
		$list = self::listNumerosMembresWithIds();
		$result = [];

		foreach ($list as $row)
			$result[$row['id_ancien_si']] = $row['id_membre'];

		return $result;
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
		$data = self::filterMembreData($data);
		DB::update('membres', $data, 'id_ancien_si = %i', $numeroMembre);
	}

	public static function createMembre($numeroMembre, $data) {
		self::initialize();
		$data['id_ancien_si'] = $numeroMembre;
		$data['pseudonyme'] = $numeroMembre;

		$data = self::filterMembreData($data);

		DB::insert('membres', $data);
	}

	private static function filterMembreData($data) {
		if (isset($data['civilite'])) {
			$civilite = $data['civilite'];

			switch (strtolower($civilite)) {
				case 'm.':
				case 'mr':
				case 'mister':
					$civilite = 'mister';
					$data['genre'] = 0;
					break;
				case 'mlle':
				case 'ms':
					$civilite = 'ms';
					$data['genre'] = 1;
					break;
				case 'mme':
				case 'mrs':
					$civilite = 'mrs';
					$data['genre'] = 1;
					break;
				default:
					$civilite = null;
					break;
			}

			$data['civilite'] = $civilite;
		}

		foreach ($data as $key => $value) {
			if (preg_match('/^date_/', $key))
				$data[$key] = Format::filterStringDate($data[$key]);
		}

		return $data;
	}

	public static function listCotisations($membreSystemId) {
		self::initialize();
		return DB::query('SELECT * FROM cotisations WHERE id_membre = %i', $membreSystemId);
	}

	public static function createCotisation($data) {
		self::initialize();
		DB::insert('cotisations', $data);
	}

	public static function createCoordonees($membreId, $type, $value) {
		self::initialize();

		$data = [
			'id_membre' => $membreId,
			'type_coordonnee' => $type,
			'coordonnee' => $value,
			'usage_coordonnee' => 'home',
			'reservee_gestion_asso' => true,
		];

		DB::insert('coordonnees', $data);
	}

	public static function createEmail($membreId, $email) {
		self::createCoordonees($membreId, 'email', $email);
	}

	public static function createPhone($membreId, $phone) {
		self::createCoordonees($membreId, 'phone', $phone);
	}

	public static function createAddress($membreId, $adresse1='', $adresse2='', $adresse3='', $ville='', $codePostal='', $pays='') {
		$value = json_encode([
			'address' => trim("{$adresse1}\n{$adresse2}\n{$adresse3}"),
			'city' => $ville,
			'code' => $codePostal,
			'country' => $pays,
		]);

		self::createCoordonees($membreId, 'address', $value);
	}
}
