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
	const snapper = new SnapJs.Snap({
		element: document.getElementById('app-content'),
		disable: 'right',
		maxPosition: 300,
		minDragDistance: 100
	});
	$('#app-navigation-toggle').on('click', () => {
		if (snapper.state().state == 'left') {
			snapper.close();
		} else {
			snapper.open('left');
		}
	});
	// close sidebar when switching navigation entry
	const $appNavigation = $('#app-navigation');
	$appNavigation.on('click', 'a, :button', (event) => {
		const $target = $(event.target);
		// don't hide navigation if the clicked element or its ancestors have the .app-navigation-noclose class
		if (!$target.is('.app-navigation-noclose') && !$target.closest('.app-navigation-noclose').length) {
			snapper.close();
		}
	});

	const toggleSnapperOnSize = () => {
		if ($(window).width() >= 1024) {
			snapper.close();
			snapper.disable();
		} else {
			snapper.enable();
		}
	};

	$(window).on('resize', _.debounce(toggleSnapperOnSize, 250));

	// initial call
	toggleSnapperOnSize();
}]);
