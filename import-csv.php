<?php

require_once 'abstract.php';

/**
 * Import products from csv
 */
class Mage_Shell_Import extends Mage_Shell_Abstract {

	/**
	 * Run script
	 */
	public function run() {
    $filename = $this->getArg('file');

    ini_set('display_startup_errors', 1);
    ini_set('display_errors', 1);

    if (! $filename && isset($GLOBALS['argv'][1]) && substr($GLOBALS['argv'][1], 0, 2) != '--') {
        $filename = trim($GLOBALS['argv'][1]);
    }

    if (! $filename || ! is_file($filename)) {
        die($this->usageHelp());
    }

    $importer = Mage::getModel('teeth/importer');
    $importer->setOutput(true);
    $importer->setForceUpdate($this->getArg('force'));
    $importer->import($filename);
}

	/**
	 * Retrieve Usage Help Message
	 */
	public function usageHelp() {
		return <<<USAGE
Usage:  php -f import-csv.php [--file] filename.csv [--force]

        [options]:
        file <file>				file path to import from ../path/
		force					force update product

        help					This help
USAGE;
	}

}

$shell = new Mage_Shell_Import();
$shell->run();
