<?php

/**
 * Import products
 */
abstract class Allecon_Teeth_Model_Importer_Laboratory extends Allecon_Teeth_Model_Importer_Acrylics {

	protected function _importLaboratoryProduct() {
		$this->log("Import laboratory entity.\n");
		
		$attributeMap = array(
			'MANUFACTURER'=>'manufacturer');
		
		$forceUpdate = $this->getForceUpdate();
        $masterProduct = array();
        $relatedProduct = array();
        $i = 1;
		foreach ( $this->_csvRows as $row ) {
			if ('denturist / dental lab' != $row['TOP LEVEL CATEGORY']) {
				continue;
			}
			
			$this->_getCategoryId('Denturist / Dental Lab', ucwords($row['CATEGORY']),ucwords($row['SUBCATEGORY']));
			
			// import teeth
            $is_master = 0;
            if( $row['ITEM'] ){
                $sku = $row['ITEM'];
                $is_master = 0;

            }else{
                $sku = $row['MASTERPRODUCT'];
                $is_master = 1;
                $masterProduct[] = $row['MASTERPRODUCT'];
            }
			$product = Mage::helper('catalog/product')->getProduct($sku, 0, 'sku');
			
			if ($product->getId()) {
//				if (! $forceUpdate) {
//					$this->log("  [E]: %s , [%d]\n", $sku, $product->getId());
//					unset($product);
//					continue;
//				}
				$this->log("  [U]: %s , ", $sku);
				
				$product->setStockData(
					array(
						"is_in_stock"=>$row['QUANTITY ON HAND'] ? 1 : 0, 
						"qty"=>$row['QUANTITY ON HAND']));
			} else {

				$this->log("  [I]: %s ... ", $sku);
				
				$attributeSetId = Mage::getModel('eav/entity_attribute_set')->load('Laboratory', 
					'attribute_set_name')->getId();
                $product->setIsMaster($is_master);
				$product->setSku($sku);
				$product->setAttributeSetId($attributeSetId);
				$product->setTypeId('simple');
				$product->setStatus(1);
				$product->setVisibility(4);
				$product->setTaxClassId(0);
                $product->setUnit($row['UNIT']);
                $product->setSize( $row['SIZE']);
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
			
			$product->setCategoryIds($this->_getCategoryId('Denturist / Dental Lab', ucwords($row['CATEGORY']),ucwords($row['SUBCATEGORY'])));
			
			$product->setDescription($product->getName());
			$product->setShortDescription($product->getName());
			
			$product->save();
            if(!$product->getIsMaster()){
                $relatedProduct[$row['MASTERPRODUCT']][] = $product->getId();
            }else{
                $this->log("Add Related Products To Master Products \n");
                $this->setRelatedProducts($product,$relatedProduct[$row['MASTERPRODUCT']]);
            }

            $i++;
			$this->log("[%d]\n", $product->getId());
			unset($product);
		}
		$this->log("\n");
	}
    protected function setRelatedProducts( $masterProduct,$relatedIds){
        $api = Mage::getModel('catalog/product_link_api');
        foreach( $relatedIds as $k=>$v){
            $api->assign('related', $masterProduct->getId(), $v);
        }
    }
}
