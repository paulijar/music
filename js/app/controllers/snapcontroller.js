/**
 * Nextcloud Music app
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Pauli Järvinen <pauli.jarvinen@gmail.com>
 * @copyright Pauli Järvinen 2026
 */

import * as SnapJs from 'vendor/snapjs/snap';

angular.module('Music').controller('SnapController', [
function () {
	var snapper = new SnapJs.Snap({
		element: document.getElementById('app-content'),
		disable: 'right',
		maxPosition: 300,
		minDragDistance: 100
	});
	$('#app-navigation-toggle').click(function () {
		if (snapper.state().state == 'left') {
			snapper.close();
		} else {
			snapper.open('left');
		}
	});
	// close sidebar when switching navigation entry
	var $appNavigation = $('#app-navigation');
	$appNavigation.delegate('a, :button', 'click', function (event) {
		var $target = $(event.target);
		// don't hide navigation when changing settings or adding things
		if ($target.is('.app-navigation-noclose') ||
			$target.closest('.app-navigation-noclose').length) {
			return;
		}
		if ($target.is('.add-new') ||
			$target.closest('.add-new').length) {
			return;
		}
		if ($target.is('#app-settings') ||
			$target.closest('#app-settings').length) {
			return;
		}
		snapper.close();
	});

	var toggleSnapperOnSize = function () {
		if ($(window).width() >= 1024) {
			snapper.close();
			snapper.disable();
		} else {
			snapper.enable();
		}
	};

	$(window).resize(_.debounce(toggleSnapperOnSize, 250));

	// initial call
	toggleSnapperOnSize();
}]);
