<?php

declare(strict_types=1);

namespace OCA\Music\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Migrate the DB schema to Music v3.2.0 level from the v2.1.0 level
 */
class Version030200Date20260729204000 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$tracks = $schema->getTable('music_tracks');

		self::addColumnIfMissing($tracks, 'bpm', 'integer', ['notnull' => false, 'unsigned' => true]);
		self::addColumnIfMissing($tracks, 'composer_id', 'integer', ['notnull' => false, 'unsigned' => true]);
		self::addColumnIfMissing($tracks, 'comment', 'text', ['notnull' => false]);
		self::addColumnIfMissing($tracks, 'mbid_rel_track', 'string', ['notnull' => false, 'length' => 36]);
		self::addIndexIfMissing($tracks, 'music_tracks_composer_id_idx', ['composer_id']);

		$artists = $schema->getTable('music_artists');
		// replace unique index for user_id/hash with one for user_id/hash/mbid
		self::dropObsoleteIndex($artists, 'user_id_hash_idx');
		self::addUniqueIndexIfMissing($artists, 'user_id_hash_mbid_idx', ['user_id', 'hash', 'mbid']);

		return $schema;
	}

	/**
	 * @param \Doctrine\DBAL\Schema\Table $table
	 */
	private static function addColumnIfMissing($table, string $name, string $type, array $args) : void {
		if (!$table->hasColumn($name)) {
			$table->addColumn($name, $type, $args);
		}
	}

	/**
	 * @param \Doctrine\DBAL\Schema\Table $table
	 */
	private static function addIndexIfMissing($table, string $name, array $columns) : void {
		if (!$table->hasIndex($name)) {
			$table->addIndex($columns, $name);
		}
	}

	/**
	 * @param \Doctrine\DBAL\Schema\Table $table
	 */
	private static function addUniqueIndexIfMissing($table, string $name, array $columns) : void {
		if (!$table->hasIndex($name)) {
			$table->addUniqueIndex($columns, $name);
		}
	}

	/**
	 * @param \Doctrine\DBAL\Schema\Table $table
	 */
	private static function dropObsoleteIndex($table, string $name) : void {
		if ($table->hasIndex($name)) {
			$table->dropIndex($name);
		}
	}
}
