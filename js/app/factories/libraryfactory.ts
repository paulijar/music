/**
 * Nextcloud Music app
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Pauli Järvinen <pauli.jarvinen@gmail.com>
 * @copyright 2026 Pauli Järvinen
 *
 */

import * as angular from 'angular';
import { MusicRootScope } from 'app/config/musicrootscope';
import { IService } from 'restangular';
import { Artist, Folder, Genre, LibraryService, Playlist, PlaylistEntry, PodcastChannel, RadioStation } from 'app/services/libraryservice';

angular.module('Music').factory('libraryFactory', ['Restangular', '$rootScope', 'libraryService', function (Restangular : IService, $rootScope : MusicRootScope, libraryService : LibraryService) {

	let collectionPromise : angular.IPromise<Artist[]> | null = null;
	let rootFolderPromise : angular.IPromise<Folder> | null = null;
	let playlistsPromise : angular.IPromise<Playlist[]> | null = null;
	let genresPromise : angular.IPromise<Genre[]> | null = null;
	let radioStationsPromise : angular.IPromise<PlaylistEntry<RadioStation>[]> | null = null;
	let podcastChannelsPromise : angular.IPromise<PodcastChannel[]> | null = null;

	return {
		getCollection() : angular.IPromise<Artist[]> {
			if (!collectionPromise) {
				collectionPromise = Restangular.all('prepare_collection').post({}).then(async (reply) => {
					$rootScope.$emit('newCoverArtToken', reply.cover_token);
					libraryService.setIgnoredArticles(reply.ignored_articles);
					const artists = await Restangular.all('collection').getList({ hash: reply.hash });
					libraryService.setCollection(artists);
					$rootScope.$emit('collectionLoaded');
					return libraryService.getCollection();
				});
			}
			return collectionPromise;
		},

		getRootFolder() : angular.IPromise<Folder> {
			if (!rootFolderPromise) {
				const fetchFoldersPromise = Restangular.all('folders').getList();

				// the collection has to be set on the libraryService before the folders can be set
				rootFolderPromise = Promise.all([this.getCollection(), fetchFoldersPromise]).then(([_collection, folders]) => {
					libraryService.setFolders(folders);
					return libraryService.getRootFolder();
				});
			}
			return rootFolderPromise;
		},

		getGenres() : angular.IPromise<Genre[]> {
			if (!genresPromise) {
				const fetchGenresPromise = Restangular.all('genres').getList();

				// the collection has to be set on the libraryService before the genres can be set
				genresPromise = Promise.all([this.getCollection(), fetchGenresPromise]).then(([_collection, genres]) => {
					libraryService.setGenres(genres);
					$rootScope.$emit('genresLoaded');
					return libraryService.getAllGenres();
				});
			}
			return genresPromise;
		},

		getPlaylists() : angular.IPromise<Playlist[]> {
			if (!playlistsPromise) {
				const fetchPlaylistsPromise = Restangular.all('playlists').getList({type: 'music-app'});

				// the collection has to be set on the libraryService before the playlists can be set
				playlistsPromise = Promise.all([this.getCollection(), fetchPlaylistsPromise]).then(([_collection, playlists]) => {
					libraryService.setPlaylists(playlists);
					return libraryService.getAllPlaylists();
				});
			}
			return playlistsPromise;
		},

		getRadioStations() : angular.IPromise<PlaylistEntry<RadioStation>[]> {
			if (!radioStationsPromise) {
				radioStationsPromise = Restangular.all('radio').getList().then((radioStations) => {
					libraryService.setRadioStations(radioStations);
					$rootScope.$emit('radioStationsLoaded');
					return libraryService.getAllRadioStations();
				});
			}
			return radioStationsPromise;
		},

		getPodcastChannels() : angular.IPromise<PodcastChannel[]> {
			if (!podcastChannelsPromise) {
				podcastChannelsPromise = Restangular.all('podcasts').getList().then((channels) => {
					libraryService.setPodcasts(channels);
					$rootScope.$emit('podcastsLoaded');
					return libraryService.getAllPodcastChannels();
				});
			}
			return podcastChannelsPromise;
		},

		updateFavorites() : angular.IPromise<void> {
			const fetchFavoritesPromise = Restangular.one('favorites').get();

			// collection, playlists, and podcasts have to be loaded before favorites can be set, because favorites can contain items from all of those
			return Promise.all([this.getCollection(), this.getPlaylists(), this.getPodcastChannels(), fetchFavoritesPromise]).then(
				([_collection, _playlists, _podcastChannels, favorites]) => libraryService.setFavorites(favorites)
			);
		},

		resetCollection() : void {
			collectionPromise = null;
			rootFolderPromise = null;
			playlistsPromise = null;
			genresPromise = null;
			libraryService.setCollection([]);
			libraryService.setFolders(null);
			libraryService.setPlaylists([]);
			libraryService.setGenres(null);
		},

		resetRadioStations() : void {
			radioStationsPromise = null;
			libraryService.setRadioStations([]);
		},

		resetPodcasts() : void {
			podcastChannelsPromise = null;
			libraryService.setPodcasts([]);
		}
	};
}]);
