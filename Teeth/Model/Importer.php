<?php

/**
 * Import product from csv
 */
class Allecon_Teeth_Model_Importer extends Allecon_Teeth_Model_Importer_Laboratory {

	/**
	 *
	 * @see Evebit_Teeth_Model_Importer_Abstract
	 * @var array
	 */
	protected $_attributeMaps = array(
		'teeth'=>array(
			'Teeth_Model'=>'teeth_model', 
			'Teeth_Color'=>'teeth_color', 
			'Teeth_Brand'=>'teeth_brand', 
			'Teeth_Anterior_Type'=>'teeth_anterior_type', 
			'Teeth_Posterior_Type'=>'teeth_posterior_type', 
			'MANUFACTURER'=>'manufacturer'), 
		'laboratory'=>array(
			'MANUFACTURER'=>'manufacturer'), 
		'acrylics'=>array(
			'MANUFACTURER'=>'manufacturer'));

	/**
	 * Import
	 *
	 * @param string $filename        	
	 */
	public function import($filename) {
		// Read csv file
		$this->_readCsvFile($filename);

		// Import product options
		$this->_importProductOptions();
		
		// Import teeth from file
		$this->_importTeethProduct();
		
		// Generate showcase teeth
		$this->_generateConfigableTeeth();
		
		// Add related teeth
		$this->_setTeethRelationship();
		
		$this->_importAcrylicsProduct();
		
		$this->_importLaboratoryProduct();
		
		$this->_reindexAll();
		
		$this->_showWrongTeeth();
	}

	protected function _reindexAll() {
		$this->log("Rebuild indexes.\n");

		$processes = Mage::getSingleton('index/indexer')->getProcessesCollection();

		foreach ($processes as $process) {
			/* @var $process Mage_Index_Model_Process */
			try {
				$process->reindexEverything();
				$this->log($process->getIndexer()->getName() . " index was rebuilt successfully\n");
			} catch (Mage_Core_Exception $e) {
				$this->log($e->getMessage() . "\n");
			} catch (Exception $e) {
				$this->log($process->getIndexer()->getName() . " index process unknown error:\n");
				$this->log($e . "\n");
			}
		}
		
		foreach ($processes as $process) {
			/* @var $process Mage_Index_Model_Process */
			$status = 'unknown';
			if ($this->getArg('status')) {
				switch ($process->getStatus()) {
					case Mage_Index_Model_Process::STATUS_PENDING:
						$status = 'Pending';
						break;
					case Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX:
						$status = 'Require Reindex';
						break;
		
					case Mage_Index_Model_Process::STATUS_RUNNING:
						$status = 'Running';
						break;
		
					default:
						$status = 'Ready';
						break;
				}
			} else {
				switch ($process->getMode()) {
					case Mage_Index_Model_Process::MODE_REAL_TIME:
						$status = 'Update on Save';
						break;
					case Mage_Index_Model_Process::MODE_MANUAL:
						$status = 'Manual Update';
						break;
				}
			}
			$this->log(sprintf('%-30s ', $process->getIndexer()->getName() . ':') . $status ."\n");
		
		}
		$this->log("\n");
	}
}
