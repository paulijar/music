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
		<div class="actions" ng-init="subMenuShown = false" title="" ng-show="playlist && $parent.showEditForm == null">
			<span class="icon-more" ng-show="!playlist.busy"
				ng-click="$parent.onNaviItemMoreButton(destination); subMenuShown = false; $event.stopPropagation()"></span>
			<span class="icon-loading-small" ng-show="playlist.busy"></span>
			<div class="popovermenu bubble" ng-show="$parent.popupShownForNaviItem == destination">
				<ul>
					<popup-menu-item ng-click="$parent.showDetails(playlist)" platform-icon="'details'" text="'Details' | translate"></popup-menu-item>
					<popup-menu-item ng-click="$parent.startEdit(playlist)" platform-icon="'rename'" text="'Rename' | translate"></popup-menu-item>
					<popup-menu-item ng-click="$parent.importFromFile(playlist)" icon="'from-file'" text="'Import from file' | translate"></popup-menu-item>
					<popup-menu-item ng-click="$parent.exportToFile(playlist)" icon="'to-file'" text="'Export to file' | translate"></popup-menu-item>
					<li ng-click="subMenuShown = !subMenuShown; $event.stopPropagation()">
						<a><span class="icon-sort-by-alpha icon svg"></span><span translate>Sort …</span></a>
						<div class="popovermenu bubble submenu" ng-show="subMenuShown">
							<ul>
								<popup-menu-item ng-click="$parent.sortPlaylist(playlist, 'track')" text="'by title' | translate"></popup-menu-item>
								<popup-menu-item ng-click="$parent.sortPlaylist(playlist, 'artist')" text="'by artist' | translate"></popup-menu-item>
								<popup-menu-item ng-click="$parent.sortPlaylist(playlist, 'album')" text="'by album' | translate"></popup-menu-item>
							</ul>
						</div>
					</li>
					<popup-menu-item ng-click="$parent.removeDuplicates(playlist)" platform-icon="'close'" text="'Remove duplicates' | translate"></popup-menu-item>
					<popup-menu-item ng-click="$parent.remove(playlist)" platform-icon="'delete'" text="'Delete' | translate"></popup-menu-item>
				</ul>
			</div>
		</div>
		<div class="actions" title="" ng-show="destination == '#/radio'">
			<span class="icon-more" ng-show="!$parent.radioBusy"
				ng-click="$parent.onNaviItemMoreButton(destination); $event.stopPropagation()"></span>
			<span class="icon-loading-small" ng-show="$parent.radioBusy"></span>
			<div class="popovermenu bubble" ng-show="$parent.popupShownForNaviItem == destination">
				<ul>
					<popup-menu-item ng-click="$parent.showRadioHint()" platform-icon="'details'" text="'Getting started' | translate"></popup-menu-item>
					<popup-menu-item ng-click="$parent.importFromFileToRadio()" icon="'from-file'" text="'Import from file' | translate"></popup-menu-item>
					<popup-menu-item ng-click="$parent.exportRadioToFile()" icon="'to-file'" text="'Export to file' | translate"></popup-menu-item>
					<popup-menu-item ng-click="$parent.addRadio()" platform-icon="'add'" text="'Add manually' | translate"></popup-menu-item>
				</ul>
			</div>
		</div>
		<div class="actions" title="" ng-show="destination == '#/podcasts'">
			<span class="icon-more" ng-show="!$parent.podcastsBusy"
				ng-click="$parent.onNaviItemMoreButton(destination); $event.stopPropagation()"></span>
			<span class="icon-loading-small" ng-show="$parent.podcastsBusy"></span>
			<div class="popovermenu bubble" ng-show="$parent.popupShownForNaviItem == destination">
				<ul>
					<popup-menu-item ng-click="$parent.addPodcast()" platform-icon="'add'" text="'Add from RSS feed' | translate"></popup-menu-item>
					<popup-menu-item ng-click="$parent.importPodcastsFromFile()" icon="'from-file'" text="'Import from file' | translate"></popup-menu-item>
					<popup-menu-item ng-click="$parent.exportPodcastsToFile($event)" icon="'to-file'" text="'Export to file' | translate" ng-class="{ disabled: !$parent.anyPodcastChannels() }"></popup-menu-item>
					<popup-menu-item ng-click="$parent.reloadPodcasts($event)" icon="'reload'" text="'Reload channels' | translate" ng-class="{ disabled: !$parent.anyPodcastChannels() }"></popup-menu-item>
				</ul>
			</div>
		</div>
		<div class="actions" title="" ng-show="destination == '#'">
			<span class="icon-more"
				ng-click="$parent.onNaviItemMoreButton(destination); $event.stopPropagation()"></span>
			<div class="popovermenu bubble" ng-show="$parent.popupShownForNaviItem == destination">
				<ul>
					<popup-menu-item ng-click="$parent.toggleAlbumsCompactLayout(false)" platform-icon="$parent.albumsCompactLayout ? 'radio-button' : 'radio-button-checked'" text="'Normal layout' | translate"></popup-menu-item>
					<popup-menu-item ng-click="$parent.toggleAlbumsCompactLayout(true)" platform-icon="$parent.albumsCompactLayout ? 'radio-button-checked' : 'radio-button'" text="'Compact layout' | translate"></popup-menu-item>
				</ul>
			</div>
		</div>
		<div class="actions" title="" ng-show="destination == '#/folders'">
			<span class="icon-more"
				ng-click="$parent.onNaviItemMoreButton(destination); $event.stopPropagation()"></span>
			<div class="popovermenu bubble" ng-show="$parent.popupShownForNaviItem == destination">
				<ul>
					<popup-menu-item ng-click="$parent.toggleFoldersFlatLayout(false)" platform-icon="$parent.foldersFlatLayout ? 'radio-button' : 'radio-button-checked'" text="'Tree layout' | translate"></popup-menu-item>
					<popup-menu-item ng-click="$parent.toggleFoldersFlatLayout(true)" platform-icon="$parent.foldersFlatLayout ? 'radio-button-checked' : 'radio-button'" text="'Flat layout' | translate"></popup-menu-item>
				</ul>
			</div>
		</div>
		<div class="actions" title="" ng-show="destination == '#/smartlist'">
			<span class="icon-more"
				ng-click="$parent.onNaviItemMoreButton(destination); $event.stopPropagation()"></span>
			<div class="popovermenu bubble" ng-show="$parent.popupShownForNaviItem == destination">
				<ul>
					<popup-menu-item ng-click="$parent.reloadSmartListView()" icon="'reload'" text="'Reload' | translate"></popup-menu-item>
					<popup-menu-item ng-click="$parent.showSmartListFilters()" icon="'filter'" text="'Filters' | translate"></popup-menu-item>
					<popup-menu-item ng-click="$parent.saveSmartList()" icon="'playlist'" text="'Save playlist' | translate"></popup-menu-item>
				</ul>
			</div>
		</div>
	</div>
</li>