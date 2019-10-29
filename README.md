minecraft world converter
===============
_Convert your minecraft worlds between different formats!_

### About

This is a simple CLI tool that allows you to convert your minecraft worlds between different formats. Currently the converter
can only convert from McRegion to Anvil. In the future we plan to add support for converting between other formats.

### Usage

Here's a quick example:
```php
$ converter mcregion:anvil /full/path/to/your/world
```

### Installation

All you have to do to install with composer is the following:

```bash
$ composer global require nxtlvlsoftware/enums
```

You can also download directly from the releases and execute the phar file directly or add it to your path.

### Under the hood

We take advantage of [PocketMine](https://github.com/pmmp/PocketMine-MP)'s world reading capabilities to load worlds from
disk and then populate a new world using the new format with all the information from the old one and then save it to disk.

### Issues

Found a problem with this project? Make sure to open an issue on the [issue tracker](https://github.com/NxtLvLSoftware/minecraft-world-converter/issues) and we'll get it sorted!

#

__The content of this repo is licensed under the Unlicense. A full copy of the license is available [here](LICENSE).__
