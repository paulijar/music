/**
 * Nextcloud Music app
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Pauli Järvinen <pauli.jarvinen@gmail.com>
 * @copyright 2026 Pauli Järvinen
 */

angular.module('Music').directive('popupMenuItem', function() {
	return {
		restrict: 'E',
		transclude: true,
		scope: {
			icon: '<', // mutually exclusive with platformIcon
			platformIcon: '<', // mutually exclusive with icon
		},
		template: `
			<li>
				<a>
					<span ng-if="icon || platformIcon" class="icon-{{icon || platformIcon}}" icon" ng-class="{svg: !platformIcon}"></span>
					<span ng-transclude></span>
				</a>
			</li>`,
		replace: true
	};
});
