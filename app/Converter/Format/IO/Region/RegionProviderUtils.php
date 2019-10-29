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

namespace App\Converter\Format\IO\Region;

use pocketmine\level\format\Chunk;
use pocketmine\level\format\io\BaseLevelProvider;
use pocketmine\level\format\io\exception\CorruptedChunkException;
use pocketmine\level\format\io\region\McRegion;
use pocketmine\level\Level;
use Symfony\Component\Console\Output\OutputInterface;

final class RegionProviderUtils extends McRegion{

    public function __construct(string $path){
        // NOOP
    }

    public static function createRegionIterator(BaseLevelProvider $provider) : \RegexIterator{
        return new \RegexIterator(
            new \FilesystemIterator(
                $provider->getPath() . '/region/',
                \FilesystemIterator::CURRENT_AS_PATHNAME | \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS
            ),
            '/\/r\.(-?\d+)\.(-?\d+)\.' . $provider::REGION_FILE_EXTENSION . '$/',
            \RegexIterator::GET_MATCH
        );
    }

    /**
     * @param BaseLevelProvider    $provider
     * @param bool                 $skipCorrupted
     * @param OutputInterface|null $output
     *
     * @return \Generator|Chunk[]
     */
    public static function getAllChunks(BaseLevelProvider $provider, bool $skipCorrupted = false, ?OutputInterface $output = null) : \Generator{
        $iterator = self::createRegionIterator($provider);
        foreach($iterator as $region){
            $regionX = ((int) $region[1]);
            $regionZ = ((int) $region[2]);
            $rX = $regionX << 5;
            $rZ = $regionZ << 5;
            for($chunkX = $rX; $chunkX < $rX + 32; ++$chunkX){
                for($chunkZ = $rZ; $chunkZ < $rZ + 32; ++$chunkZ){
                    try{
                        $chunk = $provider->loadChunk($chunkX, $chunkZ);
                        if($chunk !== null){
                            yield $chunk;
                        }
                    }catch(CorruptedChunkException $e){
                        if(!$skipCorrupted){
                            throw $e;
                        }
                        if($output !== null){
                            $output->writeln("Skipped corrupted chunk $chunkX $chunkZ (" . $e->getMessage() . ")");
                        }
                    }
                }
            }
            self::unloadRegion($provider, $regionX, $regionZ);
        }
    }

    public static function calculateChunkCount(BaseLevelProvider $provider) : int{
        $count = 0;
        foreach(self::createRegionIterator($provider) as $region){
            $regionX = ((int) $region[1]);
            $regionZ = ((int) $region[2]);
            $provider->loadRegion($regionX, $regionZ);
            $count += RegionLoaderUtils::calculateChunkCount($provider->getRegion($regionX, $regionZ));
            self::unloadRegion($provider, $regionX, $regionZ);
        }

        return $count;
    }

    protected static function unloadRegion(BaseLevelProvider $provider, int $regionX, int $regionZ) : void{
        if(isset($provider->regions[$hash = Level::chunkHash($regionX, $regionZ)])){
            $provider->regions[$hash]->close();
            unset($provider->regions[$hash]);
        }
    }

}
