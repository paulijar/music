/**
 * Nextcloud Music app
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Pauli Järvinen <pauli.jarvinen@gmail.com>
 * @copyright 2026 Pauli Järvinen
 */

angular.module('Music').directive('popupSubMenu', function() {
	return {
		restrict: 'E',
		transclude: true,
		scope: {
			icon: '<', // mutually exclusive with platformIcon
			platformIcon: '<', // mutually exclusive with icon
			text: '<',
		},
		link: function(scope) {
			scope.expanded = false;
		},
		template: `
			<li ng-click="expanded = !expanded; $event.stopPropagation()" >
				<a>
					<span ng-if="icon || platformIcon" class="icon-{{icon || platformIcon}}" icon" ng-class="{svg: !platformIcon}"></span>
					<span>{{text}}</span>
				</a>
				<div class="popovermenu bubble submenu" ng-show="expanded">
					<ul>
						<ng-transclude></ng-transclude>
					</ul>
				</div>
			</li>`,
		replace: true
	};
});
