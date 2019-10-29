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

namespace App\Commands;

use App\Converter\Format\IO\FormatConverter;
use Illuminate\Console\Command;
use pocketmine\level\format\io\region\Anvil;
use pocketmine\level\format\io\region\McRegion;

class McRegionToAnvilCommand extends Command{

	/**
	 * The signature of the command.
	 *
	 * @var string
	 */
	protected $signature = 'mcregion:anvil
                            {directory : The directory where the McRegion world is located.}
                            {backup : The path where the world should be backed up to before conversion begins.}';

	/**
	 * The description of the command.
	 *
	 * @var string
	 */
	protected $description = 'Convert an McRegion world to Anvil world format.';

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function handle(){
		$converter = new FormatConverter(new McRegion($this->argument('directory') . '/'), Anvil::class, $this->argument('backup'), $this->output);
		$converter->execute();
	}

}
