<?php declare(strict_types=1);

/**
 * Nextcloud Music app
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Pauli Järvinen <pauli.jarvinen@gmail.com>
 * @copyright Pauli Järvinen 2026
 */

namespace OCA\Music\Service\Ampache;

use OCA\Music\BusinessLayer\PlaylistBusinessLayer;
use OCA\Music\Db\BaseMapper;
use OCA\Music\Middleware\AmpacheException;
use OCP\IL10N;

class AmpacheAdvSearch {

	public function __construct(
		private IL10N $l10n,
		private PlaylistBusinessLayer $playlistBusinessLayer
	) {
	}

	public function searchRules(string $entityType, string $userId) : array {
		$l10n = $this->l10n;

		$allRules = [
			'song' => [
				'' => [
					'anywhere' => $l10n->t('Any searchable text')
				],
				$l10n->t('Track metadata') => [
					'title'			=> $l10n->t('Name'),
					'album'			=> $l10n->t('Album name'),
					'artist'		=> $l10n->t('Artist name'),
					'album_artist'	=> $l10n->t('Album artist name'),
					'track'			=> $l10n->t('Track number'),
					'year'			=> $l10n->t('Year'),
					'time'			=> $l10n->t('Duration (seconds)'),
					'bitrate'		=> $l10n->t('Bit rate'),
					'song_genre'	=> $l10n->t('Track genre'),
					'album_genre'	=> $l10n->t('Album genre'),
					'artist_genre'	=> $l10n->t('Artist genre'),
					'no_genre'		=> $l10n->t('Has no genre'),
				],
				$l10n->t('File data') => [
					'file'				=> $l10n->t('File name'),
					'added'				=> $l10n->t('Add date'),
					'updated'			=> $l10n->t('Update date'),
					'recent_added'		=> $l10n->t('Recently added'),
					'recent_updated'	=> $l10n->t('Recently updated'),
				],
				$l10n->t('Rating') => [
					'favorite'			=> $l10n->t('Favorite'),
					'favorite_album'	=> $l10n->t('Favorite album'),
					'favorite_artist'	=> $l10n->t('Favorite artist'),
					'rating'			=> $l10n->t('Rating'),
					'albumrating'		=> $l10n->t('Album rating'),
					'artistrating'		=> $l10n->t('Artist rating'),
				],
				$l10n->t('Play history') => [
					'played_times'		=> $l10n->t('Played times'),
					'last_play'			=> $l10n->t('Last played'),
					'recent_played'		=> $l10n->t('Recently played'),
					'myplayed'			=> $l10n->t('Is played'),
					'myplayedalbum'		=> $l10n->t('Is played album'),
					'myplayedartist'	=> $l10n->t('Is played artist'),
				],
				$l10n->t('Playlist') => [
					'playlist' 		=> $l10n->t('Playlist'),
					'playlist_name'	=> $l10n->t('Playlist name'),
				]
			],
			'album' => [
				$l10n->t('Album metadata') => [
					'title'			=> $l10n->t('Name'),
					'artist'		=> $l10n->t('Album artist name'),
					'song_artist'	=> $l10n->t('Track artist name'),
					'song'			=> $l10n->t('Track name'),
					'year'			=> $l10n->t('Year'),
					'time'			=> $l10n->t('Duration (seconds)'),
					'song_count'	=> $l10n->t('Track count'),
					'disk_count'	=> $l10n->t('Disk count'),
					'album_genre'	=> $l10n->t('Album genre'),
					'song_genre'	=> $l10n->t('Track genre'),
					'no_genre'		=> $l10n->t('Has no genre'),
					'has_image'		=> $l10n->t('Has image'),
				],
				$l10n->t('File data') => [
					'file'			 	=> $l10n->t('File name'),
					'added'			 	=> $l10n->t('Add date'),
					'updated'		 	=> $l10n->t('Update date'),
					'recent_added'	 	=> $l10n->t('Recently added'),
					'recent_updated'	=> $l10n->t('Recently updated'),
				],
				$l10n->t('Rating') => [
					'favorite'		=> $l10n->t('Favorite'),
					'rating'		=> $l10n->t('Rating'),
					'songrating'	=> $l10n->t('Track rating'),
					'artistrating'	=> $l10n->t('Artist rating'),
				],
				$l10n->t('Play history') => [
					'played_times'		=> $l10n->t('Played times'),
					'last_play'			=> $l10n->t('Last played'),
					'recent_played'		=> $l10n->t('Recently played'),
					'myplayed'			=> $l10n->t('Is played'),
					'myplayedartist'	=> $l10n->t('Is played artist'),
				],
				$l10n->t('Playlist') => [
					'playlist'		=> $l10n->t('Playlist'),
					'playlist_name'	=> $l10n->t('Playlist name'),
				]
			],
			'artist' => [
				$l10n->t('Artist metadata') => [
					'title'			=> $l10n->t('Name'),
					'album'			=> $l10n->t('Album name'),
					'song'			=> $l10n->t('Track name'),
					'time'			=> $l10n->t('Duration (seconds)'),
					'album_count'	=> $l10n->t('Album count'),
					'song_count'	=> $l10n->t('Track count'),
					'genre'			=> $l10n->t('Artist genre'),
					'song_genre'	=> $l10n->t('Track genre'),
					'no_genre'		=> $l10n->t('Has no genre'),
					'has_image'		=> $l10n->t('Has image'),
				],
				$l10n->t('File data') => [
					'file'				=> $l10n->t('File name'),
					'added'				=> $l10n->t('Add date'),
					'updated'			=> $l10n->t('Update date'),
					'recent_added'		=> $l10n->t('Recently added'),
					'recent_updated'	=> $l10n->t('Recently updated'),
				],
				$l10n->t('Rating') => [
					'favorite'		=> $l10n->t('Favorite'),
					'rating'		=> $l10n->t('Rating'),
					'songrating'	=> $l10n->t('Track rating'),
					'albumrating'	=> $l10n->t('Album rating'),
				],
				$l10n->t('Play history') => [
					'played_times'	=> $l10n->t('Played times'),
					'last_play'		=> $l10n->t('Last played'),
					'recent_played'	=> $l10n->t('Recently played'),
					'myplayed'		=> $l10n->t('Is played'),
				],
				$l10n->t('Playlist') => [
					'playlist'		=> $l10n->t('Playlist'),
					'playlist_name'	=> $l10n->t('Playlist name'),
				]
			],
			'playlist' => [
				'' => [
					'title'				=> $l10n->t('Name'),
					'added'				=> $l10n->t('Add date'),
					'updated'			=> $l10n->t('Update date'),
					'recent_added'		=> $l10n->t('Recently added'),
					'recent_updated'	=> $l10n->t('Recently updated'),
					'favorite'			=> $l10n->t('Favorite'),
				]
			],
			'genre' => [
				'' => [
					'title'				=> $l10n->t('Name'),
					'album_count'		=> $l10n->t('Album count'),
					'song_count'		=> $l10n->t('Track count'),
					'added'				=> $l10n->t('Add date'),
					'updated'			=> $l10n->t('Update date'),
					'recent_added'		=> $l10n->t('Recently added'),
					'recent_updated'	=> $l10n->t('Recently updated'),
				]
			],
			'podcast_episode' => [
				$l10n->t('Podcast metadata') => [
					'title'		=> $l10n->t('Name'),
					'podcast'	=> $l10n->t('Podcast channel'),
					'time'		=> $l10n->t('Duration (seconds)'),
				],
				$l10n->t('History') => [
					'pubdate'			=> $l10n->t('Date published'),
					'added'				=> $l10n->t('Add date'),
					'updated'			=> $l10n->t('Update date'),
					'recent_added'		=> $l10n->t('Recently added'),
					'recent_updated'	=> $l10n->t('Recently updated'),
				],
				$l10n->t('Rating') => [
					'favorite'	=> $l10n->t('Favorite'),
					'rating'	=> $l10n->t('Rating'),
				],
			],
			'podcast' => [
				$l10n->t('Podcast metadata') => [
					'title'				=> $l10n->t('Name'),
					'podcast_episode'	=> $l10n->t('Podcast episode'),
					'time'				=> $l10n->t('Duration (seconds)'),
				],
				$l10n->t('History') => [
					'pubdate'			=> $l10n->t('Date published'),
					'added'				=> $l10n->t('Add date'),
					'updated'			=> $l10n->t('Update date'),
					'recent_added'		=> $l10n->t('Recently added'),
					'recent_updated'	=> $l10n->t('Recently updated'),
				],
				$l10n->t('Rating') => [
					'favorite'	=> $l10n->t('Favorite'),
					'rating'	=> $l10n->t('Rating'),
				],
			],
			'live_stream' /* proprietary extension */ => [
				'' => [
					'title'				=> $l10n->t('Name'),
					'stream_url'		=> $l10n->t('Stream URL'),
					'added'				=> $l10n->t('Add date'),
					'updated'			=> $l10n->t('Update date'),
					'recent_added'		=> $l10n->t('Recently added'),
					'recent_updated'	=> $l10n->t('Recently updated'),
				],
			],
		];

		$result = [];

		foreach ($allRules[$entityType] as $title => $rules) {
			foreach ($rules as $name => $label) {
				$type = self::typeForRule($name);
				$widget = $this->widgetForRule($name, $type, $userId);
				$result[] = [
					'name' => $name,
					'label' => $label,
					'type' => $type,
					'widget' => $widget,
					'title' => $title
				];
			}
		}

		return $result;
	}

	/**
	 * @param array<string> $restParams
	 * @return array<array{rule: string, operator: string, input: string}>
	 */
	public static function getAndConvertRules(array $restParams) : array {
		// organize the rule parameters from the HTTP call
		$rules = self::getRuleParams($restParams);

		// apply some conversions on the rules
		foreach ($rules as &$rule) {
			$rule['rule'] = self::resolveRuleAlias($rule['rule']);
			$rule['operator'] = self::interpretOperator($rule['operator'], $rule['rule']);
			$rule['input'] = self::convertInput($rule['input'], $rule['rule']);
		}

		return $rules;
	}

	private static function getRuleParams(array $urlParams) : array {
		$rules = [];

		// read and organize the rule parameters
		foreach ($urlParams as $key => $value) {
			$parts = \explode('_', $key, 3);
			if ($parts[0] == 'rule' && \count($parts) > 1) {
				if (\count($parts) == 2) {
					$rules[$parts[1]]['rule'] = $value;
				} elseif ($parts[2] == 'operator') {
					$rules[$parts[1]]['operator'] = (int)$value;
				} elseif ($parts[2] == 'input') {
					$rules[$parts[1]]['input'] = $value;
				}
			}
		}

		// validate the rule parameters
		if (\count($rules) === 0) {
			throw new AmpacheException('At least one rule must be given', 400);
		}
		foreach ($rules as $rule) {
			if (\count($rule) != 3) {
				throw new AmpacheException('All rules must be given as triplet "rule_N", "rule_N_operator", "rule_N_input"', 400);
			}
		}

		return $rules;
	}

	private static function resolveRuleAlias(string $rule) : string {
		switch ($rule) {
			case 'name':					return 'title';
			case 'song_title':				return 'song';
			case 'album_title':				return 'album';
			case 'artist_title':			return 'artist';
			case 'podcast_title':			return 'podcast';
			case 'podcast_episode_title':	return 'podcast_episode';
			case 'album_artist_title':		return 'album_artist';
			case 'song_artist_title':		return 'song_artist';
			case 'tag':						return 'genre';
			case 'song_tag':				return 'song_genre';
			case 'album_tag':				return 'album_genre';
			case 'artist_tag':				return 'artist_genre';
			case 'no_tag':					return 'no_genre';
			default:						return $rule;
		}
	}

	// NOTE: alias rule names should be resolved to their base form before calling this
	private static function interpretOperator(int $rule_operator, string $rule) : string {
		// Operator mapping is different for different types of rules
		$type = self::typeForRule($rule);

		$mapping = [
			'text' => [
				0 => 'contain',		// contains
				1 => 'notcontain',	// does not contain;
				2 => 'start',		// starts with
				3 => 'end',			// ends with;
				4 => 'is',			// is
				5 => 'isnot',		// is not
				6 => 'sounds',		// sounds like
				7 => 'notsounds',	// does not sound like
				8 => 'regexp',		// matches regex
				9 => 'notregexp'	// does not match regex
			],
			'numeric' => [
				0 => '>=',
				1 => '<=',
				2 => '=',
				3 => '!=',
				4 => '>',
				5 => '<'
			],
			'numeric_limit' => [
				0 => 'limit'
			],
			'date' => [
				0 => 'before',
				1 => 'after'
			],
			'days' => [
				0 => 'before',
				1 => 'after'
			],
			'boolean' => [
				0 => 'true',
				1 => 'false'
			],
			'boolean_numeric' => [
				0 => 'equal',
				1 => 'ne'
			]
		];

		return $mapping[$type][$rule_operator]
			?? throw new AmpacheException("Search operator '$rule_operator' not supported for '$type' type rule '$rule", 400);
	}

	private static function convertInput(string $input, string $rule) : string {
		switch ($rule) {
			case 'last_play':
				// days diff to ISO date
				$date = new \DateTime("$input days ago");
				return $date->format(BaseMapper::SQL_DATE_FORMAT);
			case 'time':
				// minutes to seconds
				return (string)(int)((float)$input * 60);
			default:
				return $input;
		}
	}

	private static function typeForRule(string $rule) : string {
		$rulesPerType = [
			'text' => [
				'anywhere', 'title', 'song', 'album', 'artist', 'podcast', 'podcast_episode', 'album_artist', 'song_artist',
				'favorite', 'favorite_album', 'favorite_artist', 'genre', 'song_genre', 'album_genre', 'artist_genre',
				'playlist_name', 'type', 'file', 'mbid', 'mbid_album', 'mbid_artist', 'mbid_song', 'stream_url' /* proprietary extension */
			],
			// text but not supported: 'composer', 'summary', 'placeformed', 'release_type', 'release_status', 'barcode',
			// 'catalog_number', 'label', 'comment', 'lyrics', 'username', 'category'

			'numeric' => [
				'track', 'year', 'original_year', 'myrating', 'rating', 'songrating', 'albumrating', 'artistrating',
				'played_times', 'album_count', 'song_count', 'disk_count', 'time', 'bitrate'
			],
			// numeric but not supported: 'yearformed', 'skipped_times', 'play_skip_ratio', 'image_height', 'image_width'

			'numeric_limit' => ['recent_played', 'recent_added', 'recent_updated'],

			'date' => ['added', 'updated', 'pubdate'],

			'days' => ['last_play'],
			// days but not supported: 'last_play_or_skip'

			'boolean' => [
				'played', 'myplayed', 'myplayedalbum', 'myplayedartist', 'has_image', 'no_genre',
				'my_flagged', 'my_flagged_album', 'my_flagged_artist'
			],
			// boolean but not supported: 'smartplaylist', 'possible_duplicate', 'possible_duplicate_album'

			'boolean_numeric' => ['playlist', 'album_artist_id' /* proprietary extension */],
			// boolean numeric but not supported: 'license', 'state', 'catalog'
		];

		foreach ($rulesPerType as $type => $rules) {
			if (\in_array($rule, $rules)) {
				return $type;
			}
		}

		throw new AmpacheException("Search rule '$rule' not supported", 400);
	}

	private function widgetForRule(string $rule, string $type, string $userId) : array {
		if (\in_array($rule, ['myrating', 'rating', 'songrating', 'albumrating', 'artistrating'])) {
			return ['select', \array_map(fn($val) => $this->l10n->n('%n Star', '%n Stars', $val), [0,1,2,3,4,5])];
		} elseif ($type == 'text') {
			return ['input', 'text'];
		} elseif ($type == 'numeric' || $type == 'numeric_limit' || $type == 'days') {
			return ['input', 'number'];
		} elseif ($type == 'date') {
			return ['input', 'datetime-local'];
		} elseif ($type == 'boolean') {
			return ['input', 'hidden'];
		} elseif ($type == 'boolean_numeric') {
			if ($rule == 'playlist') {
				$playlists = $this->playlistBusinessLayer->findAll($userId);
				$options = [];
				foreach ($playlists as $playlist) {
					$options[(string)$playlist->getId()] = $playlist->getName();
				}
				return ['select', $options];
			} else {
				return ['input', 'number'];
			}
		}
		throw new \LogicException("Unexpected type '$type'");
	}
}