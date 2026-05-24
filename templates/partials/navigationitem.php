<li class="music-navigation-item"
	ng-class="{	'active': $parent.currentView == destination,
				'menu-open': $parent.popupShownForNaviItem == destination,
				'item-with-actions': playlist || destination=='#/radio' || destination=='#/podcasts' || destination=='#' || destination=='#/folders' || destination=='#/smartlist' }"
>
	<div class="music-navigation-item-content" ng-click="$parent.navigateTo(destination)"
		ng-class="{current: $parent.playingView == destination, playing: $parent.playing}"
	>
		<div class="play-pause-button svg" ng-hide="playlist && $parent.showEditForm == playlist.id"
			ng-class="icon ? 'icon-' + icon : ''"
			ng-click="$parent.togglePlay(destination, playlist); $event.stopPropagation()"
			title="{{ (($parent.playingView == destination && $parent.playing) ? 'Pause' : 'Play') | translate }}"
		>
			<div class="play-pause"></div>
		</div>
		<span ng-hide="playlist && $parent.showEditForm == playlist.id">{{ text }}</span>
		<div ng-show="playlist && $parent.showEditForm == playlist.id">
			<div class="input-container with-buttons">
				<input type="text" class="edit-list" maxlength="256"
					on-enter="$parent.commitEdit(playlist)" ng-model="playlist.name"/>
			</div>
			<button class="action icon-checkmark"
				ng-class="{ disabled: playlist.name.length == 0 }"
				ng-click="$parent.commitEdit(playlist); $event.stopPropagation()"></button>
		</div>
		<popup-menu ng-init="subMenuShown = false" ng-show="playlist && $parent.showEditForm == null" busy="playlist.busy">
			<popup-menu-item ng-click="$parent.$parent.$parent.showDetails(playlist)" platform-icon="'details'" text="'Details' | translate"></popup-menu-item>
			<popup-menu-item ng-click="$parent.$parent.$parent.startEdit(playlist)" platform-icon="'rename'" text="'Rename' | translate"></popup-menu-item>
			<popup-menu-item ng-click="$parent.$parent.$parent.importFromFile(playlist)" icon="'from-file'" text="'Import from file' | translate"></popup-menu-item>
			<popup-menu-item ng-click="$parent.$parent.$parent.exportToFile(playlist)" icon="'to-file'" text="'Export to file' | translate"></popup-menu-item>
			<popup-menu-item ng-click="subMenuShown = !subMenuShown; $event.stopPropagation()" icon="'sort-by-alpha'" text="'Sort …' | translate">
				<div class="popovermenu bubble submenu" ng-show="subMenuShown">
					<ul>
						<popup-menu-item ng-click="$parent.$parent.$parent.$parent.$parent.sortPlaylist(playlist, 'track')" text="'by title' | translate"></popup-menu-item>
						<popup-menu-item ng-click="$parent.$parent.$parent.$parent.$parent.sortPlaylist(playlist, 'artist')" text="'by artist' | translate"></popup-menu-item>
						<popup-menu-item ng-click="$parent.$parent.$parent.$parent.$parent.sortPlaylist(playlist, 'album')" text="'by album' | translate"></popup-menu-item>
					</ul>
				</div>
			</popup-menu-item>
			<popup-menu-item ng-click="$parent.$parent.$parent.removeDuplicates(playlist)" platform-icon="'close'" text="'Remove duplicates' | translate"></popup-menu-item>
			<popup-menu-item ng-click="$parent.$parent.$parent.remove(playlist)" platform-icon="'delete'" text="'Delete' | translate"></popup-menu-item>
		</popup-menu>
		<popup-menu ng-show="destination == '#/radio'" busy="$parent.radioBusy">
			<popup-menu-item ng-click="$parent.$parent.$parent.showRadioHint()" platform-icon="'details'" text="'Getting started' | translate"></popup-menu-item>
			<popup-menu-item ng-click="$parent.$parent.$parent.importFromFileToRadio()" icon="'from-file'" text="'Import from file' | translate"></popup-menu-item>
			<popup-menu-item ng-click="$parent.$parent.$parent.exportRadioToFile()" icon="'to-file'" text="'Export to file' | translate"></popup-menu-item>
			<popup-menu-item ng-click="$parent.$parent.$parent.addRadio()" platform-icon="'add'" text="'Add manually' | translate"></popup-menu-item>
		</popup-menu>
		<popup-menu ng-show="destination == '#/podcasts'" busy="$parent.podcastsBusy">
			<popup-menu-item ng-click="$parent.$parent.$parent.addPodcast()" platform-icon="'add'" text="'Add from RSS feed' | translate"></popup-menu-item>
			<popup-menu-item ng-click="$parent.$parent.$parent.importPodcastsFromFile()" icon="'from-file'" text="'Import from file' | translate"></popup-menu-item>
			<popup-menu-item ng-click="$parent.$parent.$parent.exportPodcastsToFile($event)" icon="'to-file'" text="'Export to file' | translate" ng-class="{ disabled: !$parent.$parent.$parent.anyPodcastChannels() }"></popup-menu-item>
			<popup-menu-item ng-click="$parent.$parent.$parent.reloadPodcasts($event)" icon="'reload'" text="'Reload channels' | translate" ng-class="{ disabled: !$parent.$parent.$parent.anyPodcastChannels() }"></popup-menu-item>
		</popup-menu>
		<popup-menu ng-show="destination == '#'">
			<popup-menu-item ng-click="$parent.$parent.$parent.toggleAlbumsCompactLayout(false)" platform-icon="$parent.albumsCompactLayout ? 'radio-button' : 'radio-button-checked'" text="'Normal layout' | translate"></popup-menu-item>
			<popup-menu-item ng-click="$parent.$parent.$parent.toggleAlbumsCompactLayout(true)" platform-icon="$parent.albumsCompactLayout ? 'radio-button-checked' : 'radio-button'" text="'Compact layout' | translate"></popup-menu-item>
		</popup-menu>
		<popup-menu ng-show="destination == '#/folders'">
			<popup-menu-item ng-click="$parent.$parent.$parent.toggleFoldersFlatLayout(false)" platform-icon="$parent.foldersFlatLayout ? 'radio-button' : 'radio-button-checked'" text="'Tree layout' | translate"></popup-menu-item>
			<popup-menu-item ng-click="$parent.$parent.$parent.toggleFoldersFlatLayout(true)" platform-icon="$parent.foldersFlatLayout ? 'radio-button-checked' : 'radio-button'" text="'Flat layout' | translate"></popup-menu-item>
		</popup-menu>
		<popup-menu ng-show="destination == '#/smartlist'">
			<popup-menu-item ng-click="$parent.$parent.$parent.reloadSmartListView()" icon="'reload'" text="'Reload' | translate"></popup-menu-item>
			<popup-menu-item ng-click="$parent.$parent.$parent.showSmartListFilters()" icon="'filter'" text="'Filters' | translate"></popup-menu-item>
			<popup-menu-item ng-click="$parent.$parent.$parent.saveSmartList()" icon="'playlist'" text="'Save playlist' | translate"></popup-menu-item>
		</popup-menu>
	</div>
</li>