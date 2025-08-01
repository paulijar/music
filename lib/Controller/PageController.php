<?php declare(strict_types=1);

/**
 * ownCloud - Music app
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Pauli Järvinen <pauli.jarvinen@gmail.com>
 * @copyright Morris Jobke 2013, 2014
 * @copyright Pauli Järvinen 2019 - 2025
 */

namespace OCA\Music\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IL10N;
use OCP\IRequest;

class PageController extends Controller {
	private IL10N $l10n;

	public function __construct(string $appName,
								IRequest $request,
								IL10N $l10n) {
		parent::__construct($appName, $request);
		$this->l10n = $l10n;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index() : TemplateResponse {
		$userLang = $this->l10n->getLanguageCode();
		return new TemplateResponse($this->appName, 'main', ['lang' => $userLang]);
	}
}
