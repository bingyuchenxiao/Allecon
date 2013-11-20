<?php

/**
 * Import product from csv
 */
abstract class Allecon_Teeth_Model_Importer_Abstract extends Varien_Object {

	protected $_attributeMaps = array();

	protected $_attributeOptions = array();

	protected $_attributeOptions2 = array();

	/**
	 * CSV data from file
	 *
	 * @var array
	 */
	protected $_csvRows = null;

	/**
	 * Import
	 *
	 * @param string $filename
	 */
	abstract public function import($filename);

	/**
	 * Import teeth from rows
	 */
	protected function _importProductOptions() {
		$this->log("Import option of attributes.\n");
		$count = 0;
		$scount = array();
        
		foreach ( $this->_csvRows as $row ) {

            if( $row['TOP LEVEL CATEGORY'] == 'dentist'){
                $row['TOP LEVEL CATEGORY'] = 'acrylics';
            }else if( $row['TOP LEVEL CATEGORY'] == 'denturist / dental lab'){
                $row['TOP LEVEL CATEGORY'] = 'laboratory';
            }
			if (! isset($this->_attributeMaps[$row['TOP LEVEL CATEGORY']])) {
				continue;
			}
			$map = $this->_attributeMaps[$row['TOP LEVEL CATEGORY']];
			foreach ( $map as $field => $code ) {
				$label = $row[$field];
				if ($label) {
					$value = $this->_convAttributeOptionValue($code, $label);
					if (! isset($this->_attributeOptions[$code])) {
						$this->_loadAttributeOptions($code);
					}
					if (! $this->_attributeOptions[$code][$value]) {
						$this->log("Add: %s -> %s\n", $code, $label);
						$this->_addAttributeOption($code, $label);
						$count++;
						$scount[$code] = isset($scount[$code]) ? $scount[$code] + 1 : 1;
					}
				}
			}
		}
		foreach ( $scount as $code => $num ) {
			$this->log("  %s:\t%d\n", str_pad($code, 10, ' ', STR_PAD_LEFT), $num);
		}
		$this->log("Added %d option values.\n", $count);
		$this->log("\n");
	}

	protected function _convAttributeOptionValue($code, $label) {
		return strtolower($label);
	}

	protected function _addAttributeOption($code, $label) {
		$attribute = Mage::getModel('eav/entity_attribute');

		$attributeId = $attribute->getIdByCode('catalog_product', $code);
		$attribute->load($attributeId);

		$value = $this->_convAttributeOptionValue($code, $label);

		$attribute->setData('option', array(
			'value'=>array(
				'option'=>array(
					$value, $label))));
		$attribute->save();

		$this->_loadAttributeOptions($code);
	}

	protected function _loadAttributeOptions($code) {
		$attribute = Mage::getModel('eav/entity_attribute');
		$attributeId = $attribute->getIdByCode('catalog_product', $code);
		$attribute->load($attributeId);

		$attribute_options_model = Mage::getModel('eav/entity_attribute_source_table');
		$attribute_table = $attribute_options_model->setAttribute($attribute);
		$options = $attribute_options_model->getAllOptions(false);

		$this->_attributeOptions[$code] = array();
		foreach ( $options as $option ) {
			$this->_attributeOptions[$code][$option['label']] = $option['value'];
			$this->_attributeOptions2[$code][$option['value']] = $option['label'];
		}
	}

	public function getAttributeOption($code, $label) {
		$value = $this->_convAttributeOptionValue($code, $label);
		if (! isset($this->_attributeOptions[$code])) {
			$this->_loadAttributeOptions($code);
		}
		if (! isset($this->_attributeOptions[$code][$value])) {
			Mage::throwException("Attribute '$code' do not have option '$label'.");
		}/* if ($code == 'teeth_model') {
			var_dump($this->_attributeOptions[$code], $value);exit;
		} */
		return $this->_attributeOptions[$code][$value];
	}

	public function getAttributeOptionLabel($code, $value) {
		if (! isset($this->_attributeOptions2[$code])) {
			$this->_loadAttributeOptions($code);
		}
		if (! isset($this->_attributeOptions2[$code][$value])) {
			Mage::throwException("Attribute '$code' do not have value '$value'.");
		}
		return $this->_attributeOptions2[$code][$value];
	}

	protected $_categoryIds = array();

    protected function _getChildCategoryId($parentId, $categoryName, $aliasName) {
        if (! isset($this->_categoryIds["$parentId-$categoryName"])) {

            $parent = Mage::getModel('catalog/category')->load($parentId);

            $children = $parent->getChildrenCategories();
            foreach ( $children as $child ) {
                if ($child->getName() == $categoryName) {
                    $this->_categoryIds["$parentId-$categoryName"] = $child->getId();
                    break;
                }
            }
            unset($children);

            if (! isset($this->_categoryIds["$parentId-$categoryName"])) {

                $this->log("Add category: %s\n", $aliasName);

                $category = Mage::getModel('catalog/category');
                $category->setIsActive(1);
                $category->setDisplayMode('PRODUCTS');
                $category->setIsAnchor(0);

                $category->setPath($parent->getPath());
                $category->setName($categoryName);

                $category->save();
                $this->_categoryIds["$parentId-$categoryName"] = $category->getId();
            }
        }
        return $this->_categoryIds["$parentId-$categoryName"];
    }


    /**
	 * Generate and fetch category id
	 * @param string $type
	 * @param string $manufacturer
	 * @param string $subcat
	 * @return array
	 */
    /*
	protected function _getCategoryId($type, $manufacturer, $subcat = null) {
		$parentId = Mage::app()->getStore(1)->getRootCategoryId();
		$parentId = $this->_getChildCategoryId($parentId, $type, $type);
		$parentId = $this->_getChildCategoryId($parentId, $manufacturer, "$type/$manufacturer");
		if ($subcat) {
			$parentId = $this->_getChildCategoryId($parentId, $subcat,
				"$type/$manufacturer/$subcat");
		}
		return array(
			$parentId);
	}
*/
    protected function _getCategoryId($type, $subcategory, $subcat = null) {

        $category_ids = array();
        $parentId = Mage::app()->getStore(1)->getRootCategoryId();
        $parentId = $this->_getChildCategoryId($parentId, $type, $type);
        $category_ids[] = $parentId;
        $parentId = $this->_getChildCategoryId($parentId, $subcategory, "$type/$subcategory");
        $category_ids[] = $parentId;
        if ($subcat) {
            $parentId = $this->_getChildCategoryId($parentId, $subcat,
                "$type/$subcategory/$subcat");
            $category_ids[] = $parentId;
        }
        return $category_ids;

    }
	/**
	 * Read CSV lines into array
	 *
	 * @param string $filename
	 */
	protected function _readCsvFile($filename) {
		$this->log("Read file: %s\n", $filename);
		$fp = fopen($filename, "r+");
		if (! $fp) {
			Mage::throwException("File '$filename' could not open for read.");
		}

		$line = fgetcsv($fp);
		$nunFields = count($line);

		if (! $line) {
			Mage::throwException("File '$filename' is not valid.");
		}
		$fields = array();
		foreach ($line as $col) {

			$fields[] = strtoupper(trim($col));

		}

		$nums = array();
		$this->_csvRows = array();

		while ( ! feof($fp) ) {
			$line = fgetcsv($fp);
			if (! $line) {
				continue;
			}
			$row = array_combine($fields, $line);

			if (! $row['TOP LEVEL CATEGORY']) {
				//var_dump($row);exit;
				continue;
			}
			$row['TOP LEVEL CATEGORY'] = strtolower(trim($row['TOP LEVEL CATEGORY']));
			
			$nums[$row['TOP LEVEL CATEGORY']] = isset($nums[$row['TOP LEVEL CATEGORY']]) ? $nums[$row['TOP LEVEL CATEGORY']] + 1 : 1;

			$row = $this->_convCsvRow($row['TOP LEVEL CATEGORY'], $row);

			if ($row) {
				$this->_csvRows[] = $row;
			}
		}

		fclose($fp);

		$this->log("Readed %d rows.\n", count($this->_csvRows));
		foreach ( $nums as $type => $num ) {
			$this->log("  %s:\t%d\n", str_pad(ucfirst($type), 10, ' ', STR_PAD_LEFT), $num);
		}
		$this->log("\n");
	}

	/**
	 * Prepare special values
	 *
	 * @param string $type
	 * @param array $row
	 * @return array
	 */
	protected function _convCsvRow($type, $row) {
		$result = array();
		foreach ($row as $k=>$v) {
			$result[$k] = trim($v);
		}
		
		if (!$row['ITEM'] && !$row['MASTERPRODUCT']) {
			return null;
		}
		
		$result['TOP LEVEL CATEGORY'] = strtolower($result['TOP LEVEL CATEGORY']);
		$result['DESCRIPTION'] = trim($result['DESCRIPTION'], "' \r\n\t");
		return $result;
	}

	/**
	 * Log message to console
	 *
	 * @param string $msg
	 */
	public function log($msg) {
		if (! $this->_getData('output')) {
			return;
		}
		if (func_num_args() > 1) {
			$args = func_get_args();
			$fmt = array_shift($args);
			$msg = vsprintf($fmt, $args);
		}
		echo $msg;
		if (ob_get_level() > 0) {
			ob_flush();
		}
	}

}
