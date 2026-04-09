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

use OCA\Music\Db\BaseMapper;
use OCA\Music\Middleware\AmpacheException;

class AmpacheAdvSearch {

	public static function resolveRuleAlias(string $rule) : string {
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

	public static function getRuleParams(array $urlParams) : array {
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

	// NOTE: alias rule names should be resolved to their base form before calling this
	public static function interpretOperator(int $rule_operator, string $rule) : string {
		// Operator mapping is different for text, numeric, date, boolean, and day rules

		$textRules = [
			'anywhere', 'title', 'song', 'album', 'artist', 'podcast', 'podcast_episode', 'album_artist', 'song_artist',
			'favorite', 'favorite_album', 'favorite_artist', 'genre', 'song_genre', 'album_genre', 'artist_genre',
			'playlist_name', 'type', 'file', 'mbid', 'mbid_album', 'mbid_artist', 'mbid_song'
		];
		// text but no support planned: 'composer', 'summary', 'placeformed', 'release_type', 'release_status', 'barcode',
		// 'catalog_number', 'label', 'comment', 'lyrics', 'username', 'category'

		$numericRules = [
			'track', 'year', 'original_year', 'myrating', 'rating', 'songrating', 'albumrating', 'artistrating',
			'played_times', 'album_count', 'song_count', 'disk_count', 'time', 'bitrate'
		];
		// numeric but no support planned: 'yearformed', 'skipped_times', 'play_skip_ratio', 'image_height', 'image_width'

		$numericLimitRules = ['recent_played', 'recent_added', 'recent_updated'];

		$dateOrDayRules = ['added', 'updated', 'pubdate', 'last_play'];

		$booleanRules = [
			'played', 'myplayed', 'myplayedalbum', 'myplayedartist', 'has_image', 'no_genre',
			'my_flagged', 'my_flagged_album', 'my_flagged_artist'
		];
		// boolean but no support planned: 'smartplaylist', 'possible_duplicate', 'possible_duplicate_album'

		$booleanNumericRules = ['playlist', 'album_artist_id' /* own extension */];
		// boolean numeric but no support planned: 'license', 'state', 'catalog'

		if (\in_array($rule, $textRules)) {
			switch ($rule_operator) {
				case 0: return 'contain';		// contains
				case 1: return 'notcontain';	// does not contain;
				case 2: return 'start';			// starts with
				case 3: return 'end';			// ends with;
				case 4: return 'is';			// is
				case 5: return 'isnot';			// is not
				case 6: return 'sounds';		// sounds like
				case 7: return 'notsounds';		// does not sound like
				case 8: return 'regexp';		// matches regex
				case 9: return 'notregexp';		// does not match regex
				default: throw new AmpacheException("Search operator '$rule_operator' not supported for 'text' type rules", 400);
			}
		} elseif (\in_array($rule, $numericRules)) {
			switch ($rule_operator) {
				case 0: return '>=';
				case 1: return '<=';
				case 2: return '=';
				case 3: return '!=';
				case 4: return '>';
				case 5: return '<';
				default: throw new AmpacheException("Search operator '$rule_operator' not supported for 'numeric' type rules", 400);
			}
		} elseif (\in_array($rule, $numericLimitRules)) {
			return 'limit';
		} elseif (\in_array($rule, $dateOrDayRules)) {
			switch ($rule_operator) {
				case 0: return 'before';
				case 1: return 'after';
				default: throw new AmpacheException("Search operator '$rule_operator' not supported for 'date' or 'day' type rules", 400);
			}
		} elseif (\in_array($rule, $booleanRules)) {
			switch ($rule_operator) {
				case 0: return 'true';
				case 1: return 'false';
				default: throw new AmpacheException("Search operator '$rule_operator' not supported for 'boolean' type rules", 400);
			}
		} elseif (\in_array($rule, $booleanNumericRules)) {
			switch ($rule_operator) {
				case 0: return 'equal';
				case 1: return 'ne';
				default: throw new AmpacheException("Search operator '$rule_operator' not supported for 'boolean numeric' type rules", 400);
			}
		} else {
			throw new AmpacheException("Search rule '$rule' not supported", 400);
		}
	}

	public static function convertInput(string $input, string $rule) : string {
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

}