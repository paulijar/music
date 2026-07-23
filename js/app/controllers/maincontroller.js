/**
 * Nextcloud Music app
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Pauli Järvinen <pauli.jarvinen@gmail.com>
 * @copyright Morris Jobke 2013, 2014
 * @copyright Pauli Järvinen 2017 - 2026
 */

angular.module('Music').controller('MainController', [
'$rootScope', '$scope', '$document', '$timeout', '$window', 'libraryFactory',
'playQueueService', 'libraryService', 'inViewService', 'gettextCatalog', 'Restangular',
function ($rootScope, $scope, $document, $timeout, $window, libraryFactory,
		playQueueService, libraryService, inViewService, gettextCatalog, Restangular) {

	gettextCatalog.currentLanguage = OC.getLanguage();

	// Create a global rule to use themed icons for folders everywhere, the default icon-folder is not themed on NC 25 and later.
	// It happens sometimes (at least on Chrome), that OC.MimeType is not yet present when we come here (see 
	// https://github.com/nc-music/oc-music/issues/1137). In those cases, we need to postpone registering the folder style.
	OCA.Music.Utils.executeOnceRefAvailable(() => OC.MimeType, (ocMimeType) => {
		const folderStyle = document.createElement('style');
		folderStyle.innerHTML = `#app-view .icon-folder { background-image: url(${ocMimeType.getIconUrl('dir')}) }`;
		document.head.appendChild(folderStyle);
	});

	$rootScope.playing = false;
	$rootScope.playingView = null;
	$scope.currentTrack = null;
	playQueueService.subscribe('trackChanged', function(listEntry) {
		$scope.currentTrack = listEntry.track;
		$scope.currentTrackIndex = playQueueService.getCurrentIndex();
	});

	playQueueService.subscribe('play', function(playingView) {
		// assume that the play started from current view if no other view given
		$rootScope.playingView = playingView || $scope.getCurrentViewId();
	});

	playQueueService.subscribe('playlistEnded', function() {
		$rootScope.playingView = null;
		$scope.currentTrack = null;
		$scope.currentTrackIndex = -1;
	});

	// close all pop-up menus when clicking outside of them
	$document.on('click', function(_event) {
		$timeout(() => $rootScope.$emit('popup-menu:close'));
	});

	$scope.getCurrentViewId = function() {
		return window.location.hash.split('?')[0];
	};

	$scope.loadIndicatorVisible = function() {
		let contentNotReady = ($rootScope.loadingCollection || $rootScope.searchInProgress || $scope.checkingUnscanned);
		return $rootScope.loading
			|| (contentNotReady && $scope.viewingLibrary());
	};

	$scope.viewingLibrary = function() {
		return !['#/settings', '#/radio', '#/podcasts'].includes($scope.getCurrentViewId());
	};

	$scope.updateCollection = function() {
		$scope.updateAvailable = false;
		$rootScope.loadingCollection = true;

		libraryFactory.resetCollection();
		$rootScope.$emit('collectionUpdating');

		// Playlists, genres, and folders can be loaded in parallel with the collection
		libraryFactory.getPlaylists();
		libraryFactory.getGenres();
		libraryFactory.getRootFolder();

		// load the music collection
		libraryFactory.getCollection().then((_collection) => {
			// Load also the smart playlist once the collection is ready
			$scope.reloadSmartList();

			// The "no content"/"click to scan"/"scanning" banner uses "collapsed" layout
			// if there are any tracks already visible
			let collapsiblePopups = $('#app-content .emptycontent:not(.no-collapse)');
			if (libraryService.getTrackCount() > 0) {
				collapsiblePopups.addClass('collapsed');
			} else {
				collapsiblePopups.removeClass('collapsed');
			}

			$rootScope.loadingCollection = false;
		},
		function(response) { // error handling
			$rootScope.loadingCollection = false;

			let reason = null;
			switch (response.status) {
			case 500:
				reason = gettextCatalog.getString('Internal server error');
				break;
			case 504:
				reason = gettextCatalog.getString('Timeout');
				break;
			default:
				reason = response.status;
				break;
			}
			const errMsg = gettextCatalog.getString('Failed to load the collection:');
			OCA.Music.Dialogs.showNotification(errMsg + ' ' + reason);
		});

	};

	const FILES_TO_SCAN_PER_STEP = 20;
	let filesToScan = null;
	$scope.unscannedFiles = null;
	$scope.dirtyFiles = null;
	$scope.obsoleteFiles = null;

	$scope.updateFilesToScan = function() {
		$scope.checkingUnscanned = true;
		Restangular.one('scanstate').get().then(function(state) {
			$scope.checkingUnscanned = false;
			$scope.unscannedFiles = state.unscannedFiles;
			$scope.dirtyFiles = state.dirtyFiles;
			$scope.obsoleteFiles = state.obsoleteFiles;
			$scope.noMusicAvailable = (state.scannedCount + state.unscannedFiles.length === 0);
		},
		function(error) {
			$scope.checkingUnscanned = false;
			OCA.Music.Dialogs.showNotification(
					gettextCatalog.getString('Failed to check for new audio files (error {{ code }}); check the server logs for details', {code: error.status})
			);
		});
	};

	function processNextScanStep() {
		let sliceEnd = $scope.scanningScanned + FILES_TO_SCAN_PER_STEP;
		let filesForStep = filesToScan.slice($scope.scanningScanned, sliceEnd);
		let params = {
				files: filesForStep.join(','),
				finalize: sliceEnd >= filesToScan.length
		};
		Restangular.all('scan').post(params).then(function(result) {
			// Ignore the results if scanning has been cancelled while we
			// were waiting for the result.
			if ($scope.scanning) {
				$scope.scanningScanned = sliceEnd;

				if (result.filesScanned || result.albumCoversUpdated) {
					$scope.updateAvailable = true;
				}

				if ($scope.scanningScanned < filesToScan.length) {
					processNextScanStep();
				} else {
					$scope.scanning = false;
					// Update the collection automatically once the scanning is completed. During the scanning, the user can also click the "update" button to update the collection.
					$scope.updateCollection();
				}
			}
		});
	}

	$scope.startScanning = function(fileIds) {
		filesToScan = fileIds;
		$scope.scanningScanned = 0;
		$scope.scanningTotal = filesToScan.length;

		if (fileIds === $scope.unscannedFiles) {
			$scope.unscannedFiles = null;
		} else if (fileIds === $scope.dirtyFiles) {
			$scope.dirtyFiles = null;
		}
		$scope.scanning = true;
		processNextScanStep();
	};

	$scope.stopScanning = function() {
		$scope.scanning = false;
	};

	$scope.removeObsolete = function() {
		const count = $scope.obsoleteFiles.length;
		OC.dialogs.confirm(
			gettextCatalog.getPlural(count,
				'{{ count }} previously scanned file is no longer available. Remove it from the collection?',
				'{{ count }} previously scanned files are no longer available. Remove them from the collection?',
				{ count: count }),
			gettextCatalog.getString('Remove unavailable files'),
			function(confirmed) {
				if (confirmed) {
					Restangular.all('removescanned').post({files: $scope.obsoleteFiles.join(',')}).then(_result => {
						$scope.obsoleteFiles = null;
						$scope.updateCollection();
					});
				}
			},
			true
		);
	};

	$scope.resetScanned = function() {
		$scope.unscannedFiles = null;
		$scope.dirtyFiles = null;
		$scope.obsoleteFiles = null;
		filesToScan = null;
		// Genre and artist IDs have got invalidated while resetting the library, drop any related filters
		if ($scope.smartListParams !== null) {
			$scope.smartListParams.genres = [];
			$scope.smartListParams.artists = [];
			$scope.smartListParams.composers = [];
		}
	};

	$scope.smartListParams = null; // fetched from the server on the first list load
	$scope.reloadSmartList = function() {
		libraryService.setSmartList(null);

		const genArgs = $scope.smartListParams ?? { useLatestParams: true };

		Restangular.one('playlists/generate').get(genArgs).then((list) => {
			libraryService.setSmartList(list);
			$scope.smartListParams = list.params;
			$rootScope.$emit('smartListLoaded');
		});
	};

	function showDetails(entityType, id) {
		const capType = OCA.Music.Utils.capitalize(entityType);
		const showDetailsEvent = 'show' + capType + 'Details';
		$rootScope.$emit(showDetailsEvent, id);

		if (id) {
			$timeout(function() {
				const scrollEvent = 'scrollTo' + capType;
				const elemId = _.kebabCase(entityType) + '-' + id;
				let elem = document.getElementById(elemId);
				if (elem !== null && !isElementInViewPort(elem)) {
					$rootScope.$emit(scrollEvent, id, 0);
				}
			}, 300);
		}
	}

	$scope.showTrackDetails = function(trackOrId) {
		showDetails('track', trackOrId.id ?? trackOrId);
	};

	$scope.showArtistDetails = function(artistOrId) {
		showDetails('artist', artistOrId.id ?? artistOrId);
	};

	$scope.showAlbumDetails = function(albumOrId) {
		showDetails('album', albumOrId.id ?? albumOrId);
	};

	$scope.showPlaylistDetails = function(playlistOrId) {
		showDetails('playlist', playlistOrId.id ?? playlistOrId);
	};

	$scope.showSmartListFilters = function() {
		$rootScope.$emit('showSmartListFilters');
		$scope.collapseNavigationPaneOnMobile();
	};

	$scope.showRadioStationDetails = function(stationOrId) {
		showDetails('radioStation', stationOrId?.id ?? stationOrId);
		$scope.collapseNavigationPaneOnMobile();
	};

	$scope.showRadioHint = function() {
		$rootScope.$emit('showRadioHint');
		$scope.collapseNavigationPaneOnMobile();
	};

	$scope.showPodcastChannelDetails = function(channelOrId) {
		showDetails('podcastChannel', channelOrId.id ?? channelOrId);
	};

	$scope.showPodcastEpisodeDetails = function(episodeOrId) {
		showDetails('podcastEpisode', episodeOrId.id ?? episodeOrId);
	};

	$scope.hideSidebar = function() {
		$rootScope.$emit('hideDetails');
	};

	$scope.hideScanBar = function(event) {
		event.stopPropagation();
		// Acknowledge the scanning needs without taking any action. The page needs to be reloaded
		// to check them again.
		$scope.unscannedFiles = null;
		$scope.dirtyFiles = null;
		$scope.obsoleteFiles = null;
	};

	function scrollOffset() {
		let controls = document.getElementById('controls');
		let offset = controls?.offsetHeight ?? 0;
		if (OCA.Music.Utils.getScrollContainer()[0] !== document.getElementById('app-content')) {
			let header = document.getElementById('header');
			offset += header?.offsetHeight;
		}
		return offset;
	}

	$scope.scrollToItem = function(itemId, animationTime = 500) {
		if (itemId) {
			let container = OCA.Music.Utils.getScrollContainer();
			let element = $('#' + itemId);
			if (container && element) {
				container.scrollToElement(element, scrollOffset(), animationTime);
			}
		}
	};

	$scope.scrollToTop = function() {
		OCA.Music.Utils.getScrollContainer().scrollTo(0, 0);
	};

	// Navigate to a view selected from the navigation bar
	let navigationDestination = null;
	let afterNavigationCallback = null;
	$scope.navigateTo = function(destination, callback = null) {
		let curView = $scope.getCurrentViewId();
		if (curView != destination) {
			navigationDestination = destination;
			afterNavigationCallback = callback;
			$rootScope.loading = true;
			// Deactivate the current view. The view emits 'viewDeactivated' once that is done.
			// In the abnormal special case of no active view, activate the new view immediately.
			if (_.isString(curView)) {
				$rootScope.$emit('deactivateView');
			} else {
				window.location.hash = navigationDestination;
			}
		}

		$scope.collapseNavigationPaneOnMobile();
	};

	// Compact/normal layout of the Albums view
	$scope.albumsCompactLayout = (OCA.Music.Storage.get('albums_compact') === 'true');
	$scope.toggleAlbumsCompactLayout = function(useCompact = !$scope.albumsCompactLayout) {
		$scope.albumsCompactLayout = useCompact;
		$('#albums').toggleClass('compact', useCompact);
		$rootScope.$emit('albumsLayoutChanged');

		OCA.Music.Storage.set('albums_compact', useCompact.toString());

		// also navigate to the Albums view if not already open
		$scope.navigateTo('#/');
	};

	// Flat/tree layout of the Folders view
	$scope.foldersFlatLayout = (OCA.Music.Storage.get('folders_flat') === 'true');
	$scope.toggleFoldersFlatLayout = function(useFlat = !$scope.foldersFlatLayout) {
		$scope.foldersFlatLayout = useFlat;
		$rootScope.$emit('foldersLayoutChanged');

		OCA.Music.Storage.set('folders_flat', useFlat.toString());

		// also navigate to the Folders view if not already open
		$scope.navigateTo('#/folders');
	};

	$scope.collapseNavigationPaneOnMobile = function() {
		$timeout(() => $rootScope.$emit('closeSnapper'));
	};

	$rootScope.$on('viewDeactivated', function() {
		// carry on with the navigation once the previous view is deactivated
		window.location.hash = navigationDestination;
	});

	$rootScope.$on('viewActivated', function() {
		// execute the callback after view activation if any
		if (afterNavigationCallback !== null) {
			$timeout(afterNavigationCallback);
			afterNavigationCallback = null;
		}
	});

	// Test if element is at least partially within the view-port
	function isElementInViewPort(el) {
		return inViewService.isElementInViewPort(el, -scrollOffset());
	}

	function setMasterLayout(classes) {
		let missingClasses = _.difference(['tablet', 'mobile', 'portrait', 'extra-narrow', 'min-width'], classes);
		let appContent = $('#app-content');

		_.each(classes, function(cls) {
			appContent.addClass(cls);
		});
		_.each(missingClasses, function(cls) {
			appContent.removeClass(cls);
		});
	}

	$rootScope.$on('resize', function(_event, appView) {
		const appViewWidth = appView.outerWidth();

		// Adjust controls bar width to not overlap with the scroll bar.
		// Subtract one pixel from the width because outerWidth() seems to
		// return rounded integer value which may sometimes be slightly larger
		// than the actual width of the #app-view.
		const controlsWidth = appViewWidth - 1;
		$('#controls').css('width', controlsWidth);
		$('#controls').css('min-width', controlsWidth);

		// the "no content"/"click to scan"/"scanning" banner has the same width as controls,
		// subtracting the alphabet-navigation width
		const alphaNaviWidth = 50;
		$('#app-content .emptycontent').css('width', controlsWidth - alphaNaviWidth);
		$('#app-content .emptycontent').css('min-width', controlsWidth - alphaNaviWidth);

		// Set the app-content class according to window and view width. This has
		// impact on the overall layout of the app. See music-mobile.css and music-tablet.css.
		if (appViewWidth <= 280) {
			setMasterLayout(['mobile', 'portrait', 'extra-narrow', 'min-width']);
		}
		else if (appViewWidth <= 360) {
			setMasterLayout(['mobile', 'portrait', 'extra-narrow']);
		}
		else if (appViewWidth <= 400) {
			setMasterLayout(['mobile', 'portrait']);
		}
		else if (appViewWidth <= 500 && $window.innerWidth < 1024) {
			setMasterLayout(['mobile']);
		}
		else if (appViewWidth < 1024) {
			setMasterLayout(['tablet']);
		}
		else {
			setMasterLayout([]);
		}

		if (appViewWidth <= 768) {
			$('#controls').addClass('two-line');
		} else {
			$('#controls').removeClass('two-line');
		}

		// Work-around for NC14+: The sidebar width has been limited to 500px (normally 27%),
		// but it's not possible to make corresponding "max margin" definition for #app-content
		// in css. Hence, the margin width is limited here.
		const appContent = $('#app-content');
		if (appContent.hasClass('with-app-sidebar')) {
			let sidebarWidth = $('#app-sidebar').outerWidth();
			let viewWidth = $('#header').outerWidth();

			if (sidebarWidth < 0.27 * viewWidth) {
				appContent.css('margin-inline-end', sidebarWidth);
			} else {
				appContent.css('margin-inline-end', '');
			}
		}
		else {
			appContent.css('margin-inline-end', '');
		}
	});

	$scope.scanning = false;
	$scope.scanningScanned = 0;
	$scope.scanningTotal = 0;

	// initial loading of the library data
	$scope.updateCollection();
	libraryFactory.getRadioStations();
	libraryFactory.getPodcastChannels();
	libraryFactory.updateFavorites();
	$scope.updateFilesToScan();

	$('#app').addClass('loaded');
}]);
