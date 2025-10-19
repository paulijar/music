<?php declare(strict_types=1);

/**
 * ownCloud - Music app
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Leizh <leizh@free.fr>
 * @author Pauli Järvinen <pauli.jarvinen@gmail.com>
 * @copyright Thomas Müller 2013
 * @copyright Bart Visscher 2013
 * @copyright Leizh 2014
 * @copyright Pauli Järvinen 2017 - 2025
 */

namespace OCA\Music\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use OCP\IGroupManager;
use OCP\IUserManager;

use OCA\Music\Service\Scanner;

class Scan extends BaseCommand {

	private Scanner $scanner;

	public function __construct(IUserManager $userManager, IGroupManager $groupManager, Scanner $scanner) {
		$this->scanner = $scanner;
		parent::__construct($userManager, $groupManager);
	}

	protected function doConfigure() : void {
		$this
			->setName('music:scan')
			->setDescription('scan and index any unindexed or dirty audio files, and remove obsolete references')
			->addOption(
					'debug',
					null,
					InputOption::VALUE_NONE,
					'will run the scan in debug mode, showing memory and time consumption'
			)
			->addOption(
					'rescan',
					null,
					InputOption::VALUE_NONE,
					'rescan also any previously scanned tracks'
			)
			->addOption(
					'skip-obsolete',
					null,
					InputOption::VALUE_NONE,
					'do not remove the obsolete file references from the library'
			)
			->addOption(
					'skip-dirty',
					null,
					InputOption::VALUE_NONE,
					'do not rescan the files marked "dirty" or having timestamp after the latest scan time'
			)
			->addOption(
					'folder',
					null,
					InputOption::VALUE_OPTIONAL,
					'scan only files within this folder (path is relative to the user home folder)'
			)
			->addOption('test', null, InputOption::VALUE_NONE, '!!! test only');
		;
	}

	protected function doExecute(InputInterface $input, OutputInterface $output, array $users) : void {
		if (!$input->getOption('debug')) {
			$this->scanner->listen(Scanner::class, 'update', fn($path) => $output->writeln("Scanning <info>$path</info>"));
			$this->scanner->listen(Scanner::class, 'exclude', fn($path) => $output->writeln("!! Removing <info>$path</info>"));
		}

		if ($input->getOption('rescan') && $input->getOption('skip-obsolete')) {
			throw new \InvalidArgumentException('The options <error>rescan</error> and <error>skip-obsolete</error> are mutually exclusive');
		}

		if ($input->getOption('all')) {
			$users = $this->userManager->search('');
			$users = \array_map(fn($u) => $u->getUID(), $users);
		}

		foreach ($users as $user) {
			$this->scanUser(
					$user,
					$output,
					$input->getOption('rescan'),
					$input->getOption('skip-dirty'),
					$input->getOption('skip-obsolete'),
					$input->getOption('folder'),
					$input->getOption('debug')
			);
		}
	}

	protected function scanUser(
			string $user, OutputInterface $output, bool $rescan, bool $skipDirty,
			bool $skipObsolete, ?string $folder, bool $debug) : void {

		$output->writeln("Check library scan status for <info>$user</info>...");
		$startTime = \hrtime(true);
		$libStatus = $this->scanner->getStatusOfLibraryFiles($user, $folder);
		$statusTime = (int)((\hrtime(true) - $startTime) / 1000000);
		$output->writeln("  Status got in $statusTime ms");
		$output->writeln("  Scanned files: " . $libStatus['scannedCount']);
		$output->writeln("  Unscanned files: " . \count($libStatus['unscannedFiles']));
		$output->writeln("  Dirty files: " . \count($libStatus['dirtyFiles']));
		$output->writeln("  Obsolete files: " . \count($libStatus['obsoleteFiles']));
		$output->writeln("");

		if (!$skipObsolete && !empty($libStatus['obsoleteFiles'])) {
			$this->scanner->deleteAudio($libStatus['obsoleteFiles'], [$user]);
			$output->writeln("The obsolete files no longer available in the the library of <info>$user</info> were removed");
		}

		if ($rescan) {
			$filesToScan = $this->scanner->getAllMusicFileIds($user, $folder);
		} else {
			$filesToScan = $libStatus['unscannedFiles'];
			if (!$skipDirty) {
				$filesToScan = \array_merge($filesToScan, $libStatus['dirtyFiles']);
			}
		}
		$output->writeln('Total ' . \count($filesToScan) . ' files to scan' . ($folder ? " in '$folder'" : ''));

		if (\count($filesToScan)) {
			$stats = $this->scanner->scanFiles($user, $filesToScan, $debug ? $output : null);
			$output->writeln("Added {$stats['count']} files to database of <info>$user</info>");
			$output->writeln('  Time consumed to analyze files: ' . ($stats['anlz_time'] / 1000) . ' s');
			$output->writeln('  Time consumed to update DB: ' . ($stats['db_time'] / 1000) . ' s');
		}

		$output->writeln("");
		$output->writeln("Searching cover images for albums with no cover art set...");
		$startTime = \hrtime(true);
		if ($this->scanner->findAlbumCovers($user)) {
			$output->writeln("  Some album cover image(s) were found and added");
		}
		$albumCoverTime = (int)((\hrtime(true) - $startTime) / 1000000);
		$output->writeln("  Search took $albumCoverTime ms");

		$output->writeln("Searching cover images for artists with no cover art set...");
		$startTime = \hrtime(true);
		if ($this->scanner->findArtistCovers($user)) {
			$output->writeln("  Some artist cover image(s) were found and added");
		}
		$artistCoverTime = (int)((\hrtime(true) - $startTime) / 1000000);
		$output->writeln("  Search took $artistCoverTime ms");
	}
}
