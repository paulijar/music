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

		if (!$tracks->hasIndex('music_tracks_composer_id_idx')) {
			$tracks->addIndex(['composer_id'], 'music_tracks_composer_id_idx');
		}

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
}
