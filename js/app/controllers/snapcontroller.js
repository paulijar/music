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
import { isRTL } from '@nextcloud/l10n';

angular.module('Music').controller('SnapController', ['$rootScope', '$scope',
function ($rootScope, $scope) {
	const SNAPPER_OPEN = isRTL() ? 'right' : 'left';
	const SNAPPER_CLOSE = isRTL() ? 'left' : 'right';

	const snapper = new SnapJs.Snap({
		element: document.getElementById('app-content'),
		disable: SNAPPER_CLOSE,
		maxPosition: 300,
		minPosition: -300, // used for RTL
		minDragDistance: 100
	});

	let gestureEnabled = false;
	let gestureSuspended = false; // drag event handling temporarily suspended

	$scope.toggle = () => {
		if (snapper.state().state == SNAPPER_OPEN) {
			snapper.close();
		} else {
			snapper.open(SNAPPER_OPEN);
		}
	};

	$rootScope.$on('closeSnapper', () => snapper.close());

	const toggleSnapperOnSize = () => {
		if ($(window).width() >= 1024) {
			snapper.close();
			snapper.disable();
			gestureEnabled = false;
		} else {
			snapper.enable();
			gestureEnabled = true;
		}
	};

	$(window).on('resize', _.debounce(toggleSnapperOnSize, 250));

	// initial call
	toggleSnapperOnSize();

	// The swipe detection of the snapper is disabled while dragging drag-and-droppable UI elements
	$rootScope.$on('ANGULAR_DRAG_START', () => {
		if (gestureEnabled) {
			gestureSuspended = true;
			snapper.disable();
		}
	});

	$rootScope.$on('ANGULAR_DRAG_END', () => {
		if (gestureSuspended) {
			gestureSuspended = false;
			if (gestureEnabled) {
				snapper.enable();
			}
		}
	});
}]);
