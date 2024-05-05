/**
 * ownCloud - Music app
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Pauli Järvinen <pauli.jarvinen@gmail.com>
 * @copyright Morris Jobke 2013
 * @copyright Pauli Järvinen 2017 - 2024
 */


angular.module('Music').controller('PlaylistViewController', [
	'$rootScope', '$scope', '$routeParams', 'playlistService', 'libraryService',
	'gettextCatalog', 'Restangular', '$timeout',
	function ($rootScope, $scope, $routeParams, playlistService, libraryService,
			gettextCatalog, Restangular, $timeout) {

		$scope.tracks = null;
		$rootScope.currentView = $scope.getViewIdFromUrl();

		// $rootScope listeneres must be unsubscribed manually when the control is destroyed
		let unsubFuncs = [];

		function subscribe(event, handler) {
			unsubFuncs.push( $rootScope.$on(event, handler) );
		}

		$scope.$on('$destroy', function() {
			_.each(unsubFuncs, function(func) { func(); });
		});

		$scope.getCurrentTrackIndex = function() {
			return listIsPlaying() ? $scope.$parent.currentTrackIndex : null;
		};

		// Remove chosen track from the list
		$scope.removeTrack = function(entry) {
			let listId = $scope.playlist.id;

			// Remove the element first from our internal array, without recreating the whole array.
			// Doing this before the HTTP request improves the perceived performance.
			libraryService.removeFromPlaylist(listId, entry.index);

			if (listIsPlaying()) {
				let playingIndex = $scope.getCurrentTrackIndex();
				if (entry.index <= playingIndex) {
					--playingIndex;
				}
				playlistService.onPlaylistModified($scope.tracks, playingIndex);
			}

			Restangular.one('playlists', listId).all('remove').post({indices: trackIndex}).then(function (result) {
				$scope.playlist.updated = result.updated;
			});
		};

		function play(startIndex = null) {
			let id = 'playlist-' + $scope.playlist.id;
			playlistService.setPlaylist(id, $scope.tracks, startIndex);
			playlistService.publish('play');
		}

		// Call playlistService to play all songs in the current playlist from the beginning
		$scope.onHeaderClick = function() {
			play();
		};

		// Play the list, starting from a specific track
		$scope.onTrackClick = function(track) {
			// play/pause if currently playing list item clicked
			if ($scope.getCurrentTrackIndex() === track.index) {
				playlistService.publish('togglePlayback');
			}
			// on any other list item, start playing the list from this item
			else {
				play(track.index);
			}
		};

		$scope.getDraggable = function(index) {
			$scope.draggedIndex = index;
			let track = $scope.tracks[index].track;
			return {
				track: track ? track.id : null,
				srcIndex: index
			};
		};

		$scope.reorderDrop = function(draggable, dstIndex) {
			let listId = $scope.playlist.id;
			let srcIndex = draggable.srcIndex;

			libraryService.reorderPlaylist($scope.playlist.id, srcIndex, dstIndex);

			if (listIsPlaying()) {
				let playingIndex = $scope.getCurrentTrackIndex();
				if (playingIndex === srcIndex) {
					playingIndex = dstIndex;
				}
				else {
					if (playingIndex > srcIndex) {
						--playingIndex;
					}
					if (playingIndex >= dstIndex) {
						++playingIndex;
					}
				}
				playlistService.onPlaylistModified($scope.tracks, playingIndex);
			}

			Restangular.one('playlists', listId).all('reorder').post({fromIndex: srcIndex, toIndex: dstIndex}).then(function (result) {
				$scope.playlist.updated = result.updated;
			});
		};

		$scope.allowDrop = function(draggable, dstIndex) {
			return ('srcIndex' in draggable) && (draggable.srcIndex != dstIndex);
		};

		$scope.updateHoverStyle = function(dstIndex) {
			let element = $('.playlist-area .track-list');
			if ($scope.draggedIndex > dstIndex) {
				element.removeClass('insert-below');
				element.addClass('insert-above');
			} else if ($scope.draggedIndex < dstIndex) {
				element.removeClass('insert-above');
				element.addClass('insert-below');
			} else {
				element.removeClass('insert-above');
				element.removeClass('insert-below');
			}
		};

		subscribe('scrollToTrack', function(event, trackId) {
			if ($scope.$parent) {
				let currentIdx = $scope.getCurrentTrackIndex();
				let index;

				// There may be more than one playlist entry with the same track ID.
				// Prefer to scroll to the currently playing entry if the requested
				// track ID matches that. Otherwise scroll to the first match.
				if (currentIdx &&  $scope.tracks[currentIdx].track.id == trackId) {
					index = currentIdx;
				} else {
					index = _.findIndex($scope.tracks, function(entry) {
						return entry.track.id == trackId;
					});
				}
				$scope.$parent.scrollToItem('playlist-track-' + index);
			}
		});

		// Init happens either immediately (after making the loading animation visible)
		// or once both aritsts and playlists have been loaded
		$timeout(initViewFromRoute);
		subscribe('collectionLoaded', initViewFromRoute);
		subscribe('playlistsLoaded', initViewFromRoute);

		// Reload the view if the currently viewed playlist got updated (by import from file)
		subscribe('playlistUpdated', function(event, playlistId) {
			if ($scope.playlist.id == playlistId) {
				initViewFromRoute();
			}
		});

		function listIsPlaying() {
			return ($rootScope.playingView === $rootScope.currentView);
		}

		function initViewFromRoute() {
			if (libraryService.collectionLoaded() && libraryService.playlistsLoaded()) {
				if ($routeParams.playlistId) {
					let playlist = libraryService.getPlaylist($routeParams.playlistId);
					if (playlist) {
						$scope.playlist = playlist;
						$scope.tracks = playlist.tracks;
					}
					else {
						OC.Notification.showTemporary(gettextCatalog.getString('Requested entry was not found'));
						window.location.hash = '#/';
					}
				}
				$timeout(() => {
					$rootScope.loading = false;
					$rootScope.$emit('viewActivated');
				});
			}
		}

		$scope.$on('vsRepeatReinitialized', () => console.log('vs-repeat sent event'));

		subscribe('deactivateView', function() {
			$timeout(() => $rootScope.$emit('viewDeactivated'));
		});

	}
]);
