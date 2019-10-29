<?php

declare(strict_types=1);

/**
 * Copyright (C) NxtLvL Software Solutions
 *
 * @author Jack Noordhuis
 *
 * This is free and unencumbered software released into the public domain.
 *
 * Anyone is free to copy, modify, publish, use, compile, sell, or
 * distribute this software, either in source code form or as a compiled
 * binary, for any purpose, commercial or non-commercial, and by any means.
 *
 * In jurisdictions that recognize copyright laws, the author or authors
 * of this software dedicate any and all copyright interest in the
 * software to the public domain. We make this dedication for the benefit
 * of the public at large and to the detriment of our heirs and
 * successors. We intend this dedication to be an overt act of
 * relinquishment in perpetuity of all present and future rights to this
 * software under copyright law.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
 * IN NO EVENT SHALL THE AUTHORS BE LIABLE FOR ANY CLAIM, DAMAGES OR
 * OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE,
 * ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.
 *
 * For more information, please refer to <http://unlicense.org/>
 *
 */

namespace App\Converter\Format\IO;

use App\Converter\Format\IO\Region\RegionProviderUtils;
use App\Utils\Filesystem;
use pocketmine\level\format\io\BaseLevelProvider;
use pocketmine\level\format\io\LevelProvider;
use pocketmine\level\generator\GeneratorManager;
use pocketmine\utils\Utils;
use Symfony\Component\Console\Output\OutputInterface;

class FormatConverter{

	/** @var LevelProvider */
	private $oldProvider;

	/** @var LevelProvider|string */
	private $newProvider;

	/** @var string */
	private $backupPath;

	/** @var OutputInterface */
	private $output;

	public function __construct(LevelProvider $oldProvider, string $newProvider, string $backupPath, OutputInterface $output){
		$this->oldProvider = $oldProvider;
		Utils::testValidInstance($newProvider, LevelProvider::class);
		$this->newProvider = $newProvider;
		$this->output = $output;

		if(!file_exists($backupPath)){
			@mkdir($backupPath, 0777, true);
		}
		$nextSuffix = "";
		do{
			$this->backupPath = $backupPath . DIRECTORY_SEPARATOR . basename($this->oldProvider->getPath()) . $nextSuffix;
			$nextSuffix = "_" . crc32(random_bytes(4));
		}while(file_exists($this->backupPath));
	}

	public function getBackupPath() : string{
		return $this->backupPath;
	}

	public function execute() : LevelProvider{
		$new = $this->generateNew();

		$this->populateLevelData($new);
		$this->convertTerrain($new);

		$path = $this->oldProvider->getPath();
		$this->oldProvider->close();
		$new->close();

		$this->output->writeln("Backing up pre-conversion world to " . $this->backupPath);
		rename($path, $this->backupPath);
		rename($new->getPath(), $path);

		$this->output->writeln("Conversion completed");

		/**
		 * @see WritableWorldProvider::__construct()
		 */
		return new $this->newProvider($path);
	}

	private function generateNew() : LevelProvider{
		$this->output->writeln("Generating new world");

		$convertedOutput = rtrim($this->oldProvider->getPath(), "/\\") . "_converted" . DIRECTORY_SEPARATOR;
		if(file_exists($convertedOutput)){
			$this->output->writeln("Found previous conversion attempt, deleting...");
			Filesystem::recursiveUnlink($convertedOutput);
		}
		$this->newProvider::generate($convertedOutput, $this->oldProvider->getName(), $this->oldProvider->getSeed(), GeneratorManager::getGenerator($this->oldProvider->getGenerator()), $this->oldProvider->getGeneratorOptions());

		/**
		 * @see WritableWorldProvider::__construct()
		 */
		return new $this->newProvider($convertedOutput);
	}

	private function populateLevelData(LevelProvider $data) : void{
		$this->output->writeln("Converting world manifest");
		$data->setDifficulty($this->oldProvider->getDifficulty());
		$data->setSpawn($this->oldProvider->getSpawn());
		$data->setTime($this->oldProvider->getTime());

		if($this->oldProvider instanceof BaseLevelProvider){
			$this->oldProvider->saveLevelData();
		}
		$this->output->writeln("Finished converting manifest");
		//TODO: add more properties as-needed
	}

	private function convertTerrain(LevelProvider $new) : void{
		$this->output->writeln("Calculating chunk count");
		$count = RegionProviderUtils::calculateChunkCount($this->oldProvider);
		$this->output->writeln("Discovered $count chunks");

		$counter = 0;

		$start = microtime(true);
		$thisRound = $start;
		static $reportInterval = 256;
		foreach(RegionProviderUtils::getAllChunks($this->oldProvider, true, $this->output) as $chunk){
			$chunk->setChanged();
			$new->saveChunk($chunk);
			$counter++;
			if(($counter % $reportInterval) === 0){
				$time = microtime(true);
				$diff = $time - $thisRound;
				$thisRound = $time;
				$this->output->writeln("Converted $counter / $count chunks (" . floor($reportInterval / $diff) . " chunks/sec)");
			}
		}
		$total = microtime(true) - $start;
		$this->output->writeln("Converted $counter / $counter chunks in " . round($total, 3) . " seconds (" . floor($counter / $total) . " chunks/sec)");
	}

}
