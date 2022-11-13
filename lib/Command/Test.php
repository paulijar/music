<?php declare(strict_types=1);

/**
 * ownCloud - Music app
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Pauli Järvinen <pauli.jarvinen@gmail.com>
 * @copyright Pauli Järvinen 2022
 */

namespace OCA\Music\Command;

use OCA\Music\Utility\Util;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Test extends Command {

	private $rootFolder;

	public function __construct($rootFolder) {
		$this->rootFolder = $rootFolder;
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('music:test')
			->setDescription('test the core search performance')
			->addArgument(
				'folder',
				InputArgument::REQUIRED,
				'target folder for the searchByMime'
			)
			->addOption(
				'mime',
				null,
				InputOption::VALUE_REQUIRED,
				'mime type to search',
				'text'
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$homeFolder = $this->rootFolder->getUserFolder('root');
		$targetFolder = $homeFolder->get($input->getArgument('folder'));
		$mime = $input->getOption('mime');

		$memBefore = \memory_get_usage(true);
		$startTime = microtime(true);
		$files = $targetFolder->searchByMime($mime);
		$endTime = microtime(true);
		$memAfter = \memory_get_usage(true);

		$memDelta = $memAfter - $memBefore;
		$fmtMemDelta = Util::formatFileSize($memDelta);

		$count = count($files);
		$elapsed = $endTime - $startTime;
		$output->writeln("Found $count $mime files in $elapsed seconds, memory allocated $fmtMemDelta");
		return 0;
	}
}
