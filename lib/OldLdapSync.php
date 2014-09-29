<?php

namespace MonCompte;

class OldLdapSync {
	private static $logger;

	private static $conf;
	private static $ldapHandle;

	private static function loadConfiguration() {
		$strCfg = file_get_contents(__DIR__.'/../config/local_ldap.json');
		self::$conf = json_decode($strCfg, true);

		return self::$conf;
	}

	/**
	 * Returns ldap handle if successful, false otherwise.
	 */
	private static function openLdapConnection($host, $port, $userdn, $password) {
		$handle_ldap = ldap_connect($host, $port);

		ldap_set_option($handle_ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
		ldap_set_option($handle_ldap, LDAP_OPT_REFERRALS, 0);

		if (!ldap_bind($handle_ldap, $userdn, $password))
			$handle_ldap = false;

		self::$ldapHandle = $handle_ldap;

		return $handle_ldap;
	}

	private static function initialize() {
		if (!self::$conf) {
			self::$logger = Logger::getLogger('OldLdapSync');
			self::loadConfiguration();
			self::openLdapConnection(self::$conf['host'],self::$conf['port'],self::$conf['userdn'],self::$conf['password']);
		}
	}

	public static function migrer_vers_LDAP($leMembre) {
		self::initialize();

		$handle_ldap = self::$ldapHandle;

		$membrePath = "cn=".$leMembre["numero_membre"].", ".self::$conf['basedn'];
		$membreExists = @ldap_search($handle_ldap, $membrePath, "objectclass=*", ["cn"]);

		if(!$membreExists) {
			self::$logger->info('Adding membre to ldap repo: '.$leMembre["numero_membre"]);

			$membre_ldap = [
				"cn"			=> $leMembre["numero_membre"],
				"description"	=> ["admissible"],
				"displayname"	=> $leMembre["numero_membre"],
				"sn"			=> $leMembre["nom"],
				"homedirectory"	=> "/home/users/".$leMembre["numero_membre"],
				"objectclass"	=> ["person", "posixAccount", "inetOrgPerson", "top", "organizationalPerson"],
				"gidNumber"		=> 10000,
				"givenName"		=> $leMembre["prenom"],
				"uid"			=> $leMembre["numero_membre"],
				"userPassword"	=> Guid::generate(),
				"uidNumber"		=> $leMembre["numero_membre"],
				"mail"			=> $leMembre["email"]
			];

			if(empty($leMembre["email"]))
				$email = self::$conf['defaultEmail'];
			else
				$email = $leMembre["email"];

			/*
			if(time() < (int)$leMembre["cotisation"])
				array_push($membre_ldap["description"], "membre");
			*/

			$reussi = @ldap_add($handle_ldap, $membrePath, $membre_ldap);

			if (!$reussi)
				return ldap_error($handle_ldap);

		} else {
			self::$logger->debug('Membre already exist in ldap repo: '.$leMembre["numero_membre"]);
		}

		return false;
	}
}
