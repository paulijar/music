<?php declare(strict_types=1);

/**
 * ownCloud - Music app
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Pauli Järvinen <pauli.jarvinen@gmail.com>
 * @copyright Pauli Järvinen 2022 - 2026
 */

namespace OCA\Music\Utility;

class AppInfo {

	public const APP_ID = 'music';

	public static function getVersion() : string {
		$appManager = \OC::$server->getAppManager();
		return $appManager->getAppVersion(self::APP_ID); // NC14+
	}

	public static function getVendor() : string {
		return 'nextcloud';
	}

	public static function getFullName() : string {
		return self::getVendor() . ' ' . self::APP_ID;
	}
}
