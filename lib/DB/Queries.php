<?php

namespace MonCompte\DB;

use \DB as DB;
use \MonCompte\Format;

define('COORDONNEE_TYPE_PHONE','phone');
define('COORDONNEE_TYPE_EMAIL','email');
define('COORDONNEE_TYPE_ADDRESS','address');

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

		$result = DB::queryFirstRow('SELECT id_ancien_si FROM Membres WHERE id_ancien_si = %i', $numeroMembre);

		if ($result)
			return true;

		return false;
	}

	public static function findMembreId($numeroMembre) {
		self::initialize();
		return DB::queryOneField('id_membre', 'SELECT * FROM Membres WHERE id_ancien_si = %i', $numeroMembre);
	}

	public static function listNumerosMembresWithIds() {
		self::initialize();
		return DB::query('SELECT id_ancien_si, id_membre FROM Membres ORDER BY id_ancien_si');
	}

	public static function mapNumerosMembresWithIds() {
		$list = self::listNumerosMembresWithIds();
		$result = [];

		foreach ($list as $row)
			$result[$row['id_ancien_si']] = $row['id_membre'];

		return $result;
	}

	public static function mapNumerosMembresWithCotisationExpirationTimestamps() {
		self::initialize();
		$list = DB::query('SELECT id_ancien_si, IFNULL(UNIX_TIMESTAMP(MAX(Cotisations.date_fin)),0) AS cotisation FROM Cotisations, Membres WHERE Membres.id_membre = Cotisations.id_membre GROUP BY id_ancien_si ORDER BY id_ancien_si ASC');
		$result = [];

		foreach ($list as $row)
			$result[$row['id_ancien_si']] = $row['cotisation'];

		return $result;
	}

	public static function listMembres() {
		self::initialize();
		return DB::query('SELECT id_membre, id_ancien_si as numero_membre, nom, prenom, region, date_naissance, date_inscription, civilite FROM Membres WHERE prenom != "Betâ" AND prenom != "--" AND prenom != "-" AND prenom != "" AND prenom NOT LIKE "- %" ORDER BY id_ancien_si');
	}


	public static function listMembresWithAddress() {
		self::initialize();
		return DB::query('SELECT m.id_membre, id_ancien_si AS numero_membre, coordonnee AS adresse FROM Membres m, Coordonnees c WHERE m.id_membre = c.id_membre AND c.type_coordonnee = "address" ORDER BY id_ancien_si');
	}

	public static function listMembresForLdap() {
		self::initialize();
		return DB::query(<<<EOT
			SELECT
				id_ancien_si AS numero_membre,
				prenom,
				nom,
				Coordonnees.coordonnee AS email
			FROM
				Membres
			LEFT JOIN Coordonnees ON Membres.id_membre = Coordonnees.id_membre
			WHERE
				(Coordonnees.type_coordonnee IS NULL OR Coordonnees.type_coordonnee = 'email') AND
				prenom != '--' AND
				prenom != '-' AND
				prenom != 'X' AND
				prenom != '' AND
				prenom NOT LIKE '- %'
			ORDER BY
				id_ancien_si
EOT
		);
	}

	public static function listMembresCotisants() {
		self::initialize();
		return DB::query(<<<EOT
			SELECT
				prenom,
				nom,
				id_ancien_si AS numero_membre,
				DATE_FORMAT(MAX(c.date_fin), '%d/%m/%Y') AS fin_cotisation
			FROM
				Membres m,
				Cotisations c
			WHERE
				m.id_membre = c.id_membre AND
				c.date_fin >= NOW() AND
				m.region <> 'ETR' AND
				m.region <> 'INT' AND
				m.region <> 'INC'
			GROUP BY
				m.id_membre
			ORDER BY
				numero_membre;
EOT
		);
	}

	public static function findMembre($numeroMembre) {
		self::initialize();
		return DB::queryFirstRow('SELECT * FROM Membres WHERE id_ancien_si = %i', $numeroMembre);
	}

	public static function findMembreBaseData($numeroMembre) {
		self::initialize();
		$result = DB::queryFirstRow('SELECT id_membre, id_ancien_si AS numero_membre, prenom, nom, civilite, statut, enfants, DATE(date_naissance) AS date_naissance, DATE(date_inscription) AS date_inscription, region, devise, "" AS email, "" AS phone, "" AS adresse1, "" AS adresse2, "" AS adresse3, "" AS ville, "" AS code_postal, "" AS pays FROM Membres WHERE id_ancien_si = %i', $numeroMembre);

		if ($result) {
			$idMembre = $result['id_membre'];
			$coordonnees = self::listCoordonnees($idMembre);

			foreach ($coordonnees as $coordonnee) {
				$value = $coordonnee['coordonnee'];

				switch ($coordonnee['type_coordonnee']) {
					case COORDONNEE_TYPE_EMAIL:
						$result['email'] = $value;
						break;

					case COORDONNEE_TYPE_PHONE:
						$result['phone'] = $value;
						break;

					case COORDONNEE_TYPE_ADDRESS:
						$result = self::parseAddress($value, $result);
						break;
				}
			}
		}

		return $result;
	}

	public static function parseAddress($jsonAddress, $result=[]) {
		$address = json_decode($jsonAddress, true); // give true as second argument to get an array.

		$lines = explode("\n", trim($address['address']));

		while (count($lines) < 3)
			$lines[] = '';

		$result['adresse1'] = $lines[0];
		$result['adresse2'] = $lines[1];
		$result['adresse3'] = $lines[2];
		$result['ville'] = trim($address['city']);
		$result['code_postal'] = trim($address['code']);
		$result['pays'] = trim($address['country']);

		return $result;
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
		DB::update('Membres', $data, 'id_ancien_si = %i', $numeroMembre);
	}

	public static function createMembre($numeroMembre, $data) {
		self::initialize();
		$data['id_ancien_si'] = $numeroMembre;
		$data['pseudonyme'] = $numeroMembre;

		$data = self::filterMembreData($data);

		DB::insert('Membres', $data);
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
		return DB::query('SELECT * FROM Cotisations WHERE id_membre = %i', $membreSystemId);
	}

	public static function listCotisationsDataOnly($membreSystemId) {
		self::initialize();
		$result = DB::query('SELECT tarif, montant, DATE(date_debut) AS date_debut, DATE(date_fin) AS date_fin, region FROM Cotisations WHERE id_membre = %i ORDER BY date_fin DESC', $membreSystemId);

		if (!$result)
			$result = [];

		return $result;
	}

	public static function createCotisation($membreSystemId, $data) {
		self::initialize();
		$data['id_membre'] = $membreSystemId;
		$data['region'] = strtoupper($data['region']);
		$data['tarif'] = strtoupper($data['tarif']);
		DB::insert('Cotisations', $data);
	}

	public static function listCoordonnees($membreSystemId) {
		self::initialize();
		return DB::query('SELECT * FROM Coordonnees WHERE id_membre = %i ORDER BY id_coordonnee DESC', $membreSystemId);
	}

	public static function mapCoordonnees() {
		self::initialize();
		$foundCoordonnees = DB::query('SELECT * FROM Coordonnees ORDER BY id_coordonnee DESC');

		$result = [];

		foreach ($foundCoordonnees as $coordonnee)
			$result[$coordonnee['id_membre']] []= $coordonnee;

		return $result;
	}

	public static function deleteCoordonnees($coordonneeId) {
		self::initialize();
		DB::delete('Coordonnees', 'id_coordonnee = %i', $coordonneeId);
	}

	public static function deleteCoordonneesMembre($membreId) {
		self::initialize();
		DB::delete('Coordonnees', 'id_membre = %i', $membreId);
	}

	public static function createCoordonees($membreId, $type, $value, $isConfidential=true) {
		self::initialize();

		$data = [
			'id_membre' => $membreId,
			'type_coordonnee' => $type,
			'coordonnee' => $value,
			'usage_coordonnee' => 'home',
			'reservee_gestion_asso' => $isConfidential,
		];

		DB::insert('Coordonnees', $data);
	}


	public static function updateCoordonnee($membreId, $type, $value) {
		self::initialize();

		$data = [
			'coordonnee' => $value,
		];

		DB::update('Coordonnees', $data, 'id_membre = %s AND type_coordonnee = %s', $membreId, $type);
	}

	public static function createEmail($membreId, $email, $isConfidential=true) {
		self::createCoordonees($membreId, COORDONNEE_TYPE_EMAIL, $email, $isConfidential);
	}

	public static function createPhone($membreId, $phone, $isConfidential=true) {
		self::createCoordonees($membreId, COORDONNEE_TYPE_PHONE, $phone, $isConfidential);
	}

	public static function createAddress($membreId, $adresse1='', $adresse2='', $adresse3='', $ville='', $codePostal='', $pays='', $isConfidential=true) {
		$value = json_encode([
			'address' => trim("{$adresse1}\n{$adresse2}\n{$adresse3}"),
			'city' => $ville,
			'code' => $codePostal,
			'country' => $pays,
		]);

		self::createCoordonees($membreId, COORDONNEE_TYPE_ADDRESS, $value, $isConfidential);
	}
}
