<?php

/**
 * Import product from csv
 */
abstract class Allecon_Teeth_Model_Importer_Teeth extends Allecon_Teeth_Model_Importer_Abstract {

	/**
	 *
	 * @var array
	 */
	protected $_teethCategoryId = array();

	/**
	 *
	 * @var Mage_Catalog_Model_Category
	 */
	protected $_teethRoot = null;

	protected $_teethConfigurableAttribute = null;

	protected $_teethConfigurableAttributeData = null;

	protected $_teethRelationship = array();

	protected $_teethModelUpdated = array();
	
	/*
	 * (non-PHPdoc) @see Varien_Object::_construct()
	 */
	protected function _construct() {
		$this->_teethCategoryId['_'] = Mage::getStoreConfig('allecon/importer/teeth_category');
	}

	/**
	 * Add related teeth
	 */
	protected function _setTeethRelationship() {
		$this->log("Update set products in Related Products.\n");
		
		/* @var $api Mage_Catalog_Model_Product_Link_Api */
		$api = Mage::getModel('catalog/product_link_api');
		foreach ( $this->_teethRelationship as $sku => $related ) {
			/* @var $product Mage_Catalog_Model_Product */
			$product = Mage::helper('catalog/product')->getProduct($sku, 0, 'sku');
			$productId = $product->getId();
			if (! $productId) {
				continue;
				// Mage::throwException("Teeth '$sku' not found.");
			}
			
			$exists = $product->getRelatedProductIds();
			$values = array();
			foreach ( $related as $childSku ) {
				$childId = $product->getIdBySku($childSku);
				if (! $childId) {
					continue;
					// Mage::throwException("Teeth '$childSku' not found.");
				}
				$values[$childId] = $childSku;
			}
			
			$add = array_diff(array_keys($values), $exists);
			$remove = array_diff($exists, array_keys($values));
			
			if ($add) {
				foreach ( $add as $childId ) {
					$this->log("  +: %s - %s\n", $sku, $values[$childId]);
					$api->assign('related', $productId, $childId);
				}
			}
			if ($remove) {
				foreach ( $remove as $childId ) {
					$this->log("  -: %s - %s\n", $sku, $values[$childId]);
					$api->remove('related', $productId, $childId);
				}
			}
		}
		$this->log("\n");
	}

	/**
	 * Genereate teeth configable product
	 */
	protected function _generateConfigableTeeth() {
		$this->log("Genereate teeth product.\n");
		
		if (! isset($this->_attributeOptions['teeth_model'])) {
			$this->_loadAttributeOptions('teeth_model');
		}
		
		$this->_getConfigurableAttributesData('anterior');
		$this->_getConfigurableAttributesData('posterior');
		
		foreach ( $this->_attributeOptions['teeth_model'] as $model => $modelId ) {
			
			$sku = $model;
			$product = Mage::helper('catalog/product')->getProduct($sku, 0, 'sku');
			
			if ($product->getId()) {
				if (! in_array($sku, $this->_teethModelUpdated)) {
					$this->log("  [E]: %s , [%d]\n", $model, $product->getId());
					continue;
				}
				$product->isDeleted(true);
				$product->delete();

				$product = Mage::helper('catalog/product')->getProduct($sku, 0, 'sku');
				$this->log("  [U]: %s , ", $model);
				
			} else {
				$this->log("  [G]: %s ... ", $model);
			}
			
				$collection = Mage::getResourceModel('catalog/product_collection')->addAttributeToFilter(
					'teeth_model', $modelId)->addAttributeToFilter('type_id', 'simple')->addAttributeToSelect(
						'*');
				
				$simple = $collection->getFirstItem();
				if (! $simple || !$simple->getId()) {
					$this->log("  no item exits\n", $model);
					continue;
				}
				
				$attributeSetId = Mage::getModel('eav/entity_attribute_set')->load('Teeth', 
					'attribute_set_name')->getId();
				
				$product->setSku($sku);
                $product->setUnit($simple->getUnit());
				$product->setAttributeSetId($attributeSetId);
				$product->setTypeId('configurable');
				$product->setStatus(1);
				$product->setVisibility(4);
				$product->setWebsiteIds(array(
					1));
				$product->setPrice(0);
				$product->setTaxClassId(0);
				
				/*$product->setName('Teeth ' . $model);*/
                $product->setName($model);
				$product->setUrlKey('teeth-' . strtolower($model));
				$product->setDescription('Teeth ' . $model);
				$product->setShortDescription('Teeth ' . $model);
				
				$product->setStockData(
					array(
						'use_config_manage_stock'=>'1', 
						'use_config_enable_qty_increments'=>'1', 
						'use_config_qty_increments'=>'1', 
						'is_in_stock'=>'1', 
						'is_decimal_divided'=>0));
				
				$product->setConfigurableAttributesData($this->_getConfigurableAttributesData(
					$this->getAttributeOptionLabel('teeth_type', $simple->getTeethType())));
				
			
			$copyFields = array(
				'teeth_model', 
				'teeth_type',
                'teeth_anterior_type',//zhang
				'teeth_brand', 
				'manufacturer');
			foreach ( $copyFields as $field ) {
				$product->setData($field, $simple->getData($field));
			}
			
			$product->setCategoryIds($simple->getCategoryIds());
			
			$children = array();
			foreach ( $collection as $simple ) {
				$children[$simple->getId()] = array();
				
				$code = 'teeth_color';
				$children[$simple->getId()][0] =
					array(
						'label'=>$this->getAttributeOptionLabel($code, $simple->getData($code)), 
						'attribute_id'=>$this->_teethConfigurableAttribute[$code]->getId(), 
						'value_index'=>$simple->getData($code), 
						'is_percent'=>0, 
						'pricing_value'=>'');
				
				$code = 'teeth_'.$this->getAttributeOptionLabel('teeth_type', $simple->getTeethType()).'_type';
                if($code == 'teeth_posterior_type'){
                    $children[$simple->getId()][1] =
                        array(
                            'label'=>$this->getAttributeOptionLabel($code, $simple->getData($code)),
                            'attribute_id'=>$this->_teethConfigurableAttribute[$code]->getId(),
                            'value_index'=>$simple->getData($code),
                            'is_percent'=>0,
                            'pricing_value'=>'');
                }
			}
			$product->setConfigurableProductsData($children);
			
			$product->save();
			
			$this->log("[%d]\n", $product->getId());
			unset($product, $simple, $collection);
		}
		$this->log("\n");
	}

	protected function _getConfigurableAttributesData($type) {
		if (! isset($this->_teethConfigurableAttribute[$type])) {

			$data = array();

                $code = 'teeth_'.$type.'_type';


                $this->_teethConfigurableAttribute[$code] = $attribute = Mage::getModel('eav/entity_attribute');
                $attributeId = $attribute->getIdByCode('catalog_product', $code);
                $attribute->load($attributeId);

                $attribute_options_model = Mage::getModel('eav/entity_attribute_source_table');
                $attribute_table = $attribute_options_model->setAttribute($attribute);
                $options = $attribute_options_model->getAllOptions(false);
               if($type != 'anterior'){
                    $data[0] = array(
                        'use_default'=>1,
                        'values'=>array(),
                        'label'=>$attribute->getFrontendLabel(),
                        'attribute_id'=>$attribute->getId(),
                        'attribute_code'=>$attribute->getAttributeCode(),
                        'frontend_label'=>$attribute->getFrontendLabel(),
                        'store_label'=>$attribute->getFrontendLabel(),
                        'html_id'=>'configurable__attribute_1');

                    foreach ( $options as $option ) {
                        $data[0]['values'][] = array(
                            'label'=>$option['label'],
                            'attribute_id'=>$attribute->getId(),
                            'value_index'=>$option['value'],
                            'is_percent'=>0,
                            'pricing_value'=>'');
                    }
                }
                $code = 'teeth_color';

                $this->_teethConfigurableAttribute[$code] = $attribute = Mage::getModel('eav/entity_attribute');
                $attributeId = $attribute->getIdByCode('catalog_product', $code);
                $attribute->load($attributeId);

                $attribute_options_model = Mage::getModel('eav/entity_attribute_source_table');
                $attribute_table = $attribute_options_model->setAttribute($attribute);
                $options = $attribute_options_model->getAllOptions(false);

                $data[1] = array(
                    'use_default'=>1,
                    'values'=>array(),
                    'label'=>$attribute->getFrontendLabel(),
                    'attribute_id'=>$attribute->getId(),
                    'attribute_code'=>$attribute->getAttributeCode(),
                    'frontend_label'=>$attribute->getFrontendLabel(),
                    'store_label'=>$attribute->getFrontendLabel(),
                    'html_id'=>'configurable__attribute_0');

                foreach ( $options as $option ) {
                    $data[1]['values'][] = array(
                        'label'=>$option['label'],
                        'attribute_id'=>$attribute->getId(),
                        'value_index'=>$option['value'],
                        'is_percent'=>0,
                        'pricing_value'=>'');
                }

                $this->_teethConfigurableAttributeData[$type] = $data;
            }

		return $this->_teethConfigurableAttributeData[$type];
	}

	/**
	 * Import teeth simple product
	 */
	protected function _importTeethProduct() {
		$this->log("Import teeth entity.\n");
		
		$attributeMap = array(
			'Teeth_Model'=>'teeth_model', 
			'Teeth_Color'=>'teeth_color', 
			'Teeth_Type'=>'teeth_type', 
			'Teeth_Brand'=>'teeth_brand', 
			'Teeth_Anterior_Type'=>'teeth_anterior_type',
			'Teeth_Posterior_Type'=>'teeth_posterior_type',
			'MANUFACTURER'=>'manufacturer',
           // 'SUBCATEGORY'=>'manufacturer'
        );
		
		$forceUpdate = $this->getForceUpdate();
		foreach ( $this->_csvRows as $row ) {
			if ('teeth' != $row['TOP LEVEL CATEGORY']) {
				continue;
			}

			// create category for manufactor
			//$this->_getCategoryId('Dental', ucfirst($row['MANUFACTURER']));
          //  $this->_getCategoryId('Dental', ucfirst($row['CATEGORY']));
            $this->_getCategoryId('Dental', ucwords($row['CATEGORY']), ucwords($row['SUBCATEGORY']));
			// store teeth relationship
			$set = array(
				$row['Teeth_Model']);
			$tokens = preg_split("/[, ]+/", $row['SET PRODUCT']);
			foreach ( $tokens as $token ) {
				$token = trim($token);
				if ($token && ! in_array($token, $set)) {
					$set[] = $token;
				}
			}
			
			if (count($set) > 1) {
				foreach ( $set as $token1 ) {
					foreach ( $set as $token2 ) {
						if ($token1 != $token2) {
							if (! isset($this->_teethRelationship[$token1])) {
								$this->_teethRelationship[$token1] = array(
									$token2);
							} elseif (! in_array($token2, $this->_teethRelationship[$token1])) {
								$this->_teethRelationship[$token1][] = $token2;
							}
						}
					}
				}
			}
			
			// import teeth
			$sku = $row['SKU'];
			$product = Mage::helper('catalog/product')->getProduct($sku, 0, 'sku');
			
			if ($product->getId()) {
				if (! $forceUpdate) {
					$this->log("  [E]: %s , [%d]\n", $row['SKU'], $product->getId());
					unset($product);
					continue;
				}
				$this->log("  [U]: %s , ", $row['SKU']);
				
				$product->setStockData(
					array(
						"is_in_stock"=>$row['QUANTITY ON HAND'] ? 1 : 0, 
						"qty"=>$row['QUANTITY ON HAND']));
			} else {
				$this->log("  [I]: %s ... ", $row['SKU']);
				
				$attributeSetId = Mage::getModel('eav/entity_attribute_set')->load('Teeth', 
					'attribute_set_name')->getId();

				$product->setSku($sku);
                $product->setUnit($row['UNIT']);
				$product->setAttributeSetId($attributeSetId);
				$product->setTypeId('simple');
				$product->setStatus(1);
				$product->setVisibility(1);
				$product->setTaxClassId(0);
				$product->setWeight(0);
				$product->setWebsiteIds(array(
					1));
				
				$product->setStockData(
					array(
						"use_config_manage_stock"=>1, 
						"original_inventory_qty"=>0, 
						"use_config_min_qty"=>1, 
						"use_config_min_sale_qty"=>1, 
						"use_config_max_sale_qty"=>1, 
						"is_qty_decimal"=>0, 
						"is_decimal_divided"=>0, 
						"use_config_backorders"=>1, 
						"use_config_notify_stock_qty"=>1, 
						"use_config_enable_qty_increments"=>1, 
						"qty_increments"=>0, 
						"is_in_stock"=>$row['QUANTITY ON HAND'] ? 1 : 0, 
						"qty"=>$row['QUANTITY ON HAND']));
			}
			
			$product->setData('_edit_mode', true);
			
			$product->setCost($row['COST']);
			$product->setPrice($row['PRICE']);
			
			foreach ( $attributeMap as $field => $attr ) {
				if (isset($row[$field])) {
					$product->setData($attr, $this->getAttributeOption($attr, $row[$field]));
				}
			}
            //$product->setName($row['SKU']);

			$product->setName(
				sprintf("%s %s, %s %s", $row['Teeth_Color'], $row['Teeth_Model'], 
					$row['Teeth_Type'], 
					isset($row['Teeth_Anterior_Type']) ? $row['Teeth_Anterior_Type'] : $row['Teeth_Posterior_Type']));
           
			
			//$product->setCategoryIds($this->_getCategoryId('Dental', ucfirst($row['MANUFACTURER'])));
			$product->setCategoryIds($this->_getCategoryId('Dental', ucfirst($row['CATEGORY']),ucwords($row['SUBCATEGORY'])));
			$product->setDescription($product->getName());
			$product->setShortDescription($product->getName());
			
			$product->save();
			
			if (! in_array($row['Teeth_Model'], $this->_teethModelUpdated)) {
				$this->_teethModelUpdated[] = $row['Teeth_Model'];
			}
			
			$this->log("[%d]\n", $product->getId());
			unset($product);
		}
		
		$this->log("\n");
	}

	protected function _convAttributeOptionValue($code, $label) {
		if ($code == 'teeth_model') {
			return strtoupper($label);
		}
		return strtolower($label);
	}
	
	protected $_WrongTeeth = array();
	
	protected function _showWrongTeeth() {
	    if ($this->_WrongTeeth) {
	       printf("There have %d teeth rows not passed checking:\n", count($this->_WrongTeeth));
	       foreach ($this->_WrongTeeth as $sku=>$description) {
	           printf("  %s: %s\n", $sku, $description);
	       }
	    }
	}
	
	/**
	 * Prepare special values
	 *
	 * @param string $type        	
	 * @param array $row        	
	 * @return array
	 */
	protected function _convCsvRow($type, $row) {
		$row = parent::_convCsvRow($type, $row);
		if ($type == 'teeth') {
			$row['SKU'] = $row["ITEM"];
            $row['UNIT'] = $row["U/M"];
            if (!$row['SKU']) {
                return null;
            }
            
			if (! preg_match("/([\\S\\.]+) *[.,] *(\\S+) *[.,](.*)(upper|lower) *[+]?(posterior|anterior)([^.,]*)[.,](.*)/i",
			        $row['DESCRIPTION'], $match)) {
			    //printf("  [E] %s\n", $row['DESCRIPTION']);
			    $this->_WrongTeeth[$row['SKU']] = $row['DESCRIPTION'];
				return null;
			}
            
			$row['Teeth_Color'] = strtoupper($match[1]);
			$row['Teeth_Model'] = strtoupper($match[2]);
			$row['Teeth_Prop'] = trim($match[3]);
			$row['Teeth_Extra'] = trim($match[6]);
			$row['Teeth_Brand'] = ucfirst(trim($match[7]));
			
			$row['Teeth_Type'] = ucfirst($match[5]);
			switch ($row['Teeth_Type']) {
				case 'Anterior' :
					$row['Teeth_Anterior_Type'] = ucfirst($match[4]);
                    //$row['Teeth_Anterior_Type'] = '';
					break;
				case 'Posterior' :
					$row['Teeth_Posterior_Type'] = ucfirst($match[4]);
					break;
				default :
					Mage::throwException("Teeth description not valid: {$row['DESCRIPTION']}");
			}
			
		}
		
		return $row;
	}

}
