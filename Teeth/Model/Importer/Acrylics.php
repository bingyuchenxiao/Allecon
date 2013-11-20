<?php

/**
 * Import products
 */
abstract class Allecon_Teeth_Model_Importer_Acrylics extends Allecon_Teeth_Model_Importer_Teeth {

	protected function _importAcrylicsProduct() {
		$this->log("Import acrylics entity.\n");

		$attributeMap = array(
			'MANUFACTURER'=>'manufacturer');

		$forceUpdate = $this->getForceUpdate();
		foreach ( $this->_csvRows as $row ) {

			if ('dentist' != $row['TOP LEVEL CATEGORY']) {
				continue;
			}

			$this->_getCategoryId('Dentist', ucwords($row['CATEGORY']),ucwords($row['SUBCATEGORY']));
			// import teeth
			$sku = $row['ITEM'];
			$product = Mage::helper('catalog/product')->getProduct($sku, 0, 'sku');

			if ($product->getId()) {
				if (! $forceUpdate) {
					$this->log("  [E]: %s , [%d]\n", $sku, $product->getId());
					unset($product);
					continue;
				}
				$this->log("  [U]: %s , ", $sku);

				$product->setStockData(
					array(
						"is_in_stock"=>$row['QUANTITY ON HAND'] ? 1 : 0,
						"qty"=>$row['QUANTITY ON HAND']));
			} else {
				$this->log("  [I]: %s ... ", $sku);

				$attributeSetId = Mage::getModel('eav/entity_attribute_set')->load('Acrylics',
					'attribute_set_name')->getId();

				$product->setSku($sku);
				$product->setAttributeSetId($attributeSetId);
				$product->setTypeId('simple');
				$product->setStatus(1);
				$product->setVisibility(4);
				$product->setTaxClassId(0);
                $product->setUnit($row['UNIT']);
                $product->setSize($row['SIZE']);
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

			$product->setName($row['DESCRIPTION']);

			$product->setCategoryIds($this->_getCategoryId('Dentist', ucwords($row['CATEGORY']),ucwords($row['SUBCATEGORY'])));

			$product->setDescription($product->getName());
			$product->setShortDescription($product->getName());

			$product->save();

			$this->log("[%d]\n", $product->getId());
			unset($product);
		}

		$this->log("\n");
	}

}
