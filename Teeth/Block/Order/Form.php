<?php

class Allecon_Teeth_Block_Order_Form extends Mage_Core_Block_Template {

    public function getOptionId() {
        return Mage::registry('current_manufacturer');
    }

    public function getCategory() {
        return Mage::registry('current_category');
    }
    
    protected $_source;

    public function getOptionText($code, $value) {
        if (!isset($this->_source[$code])) {
            $this->_source[$code] = Mage::getModel('catalog/product')->getResource()
                ->getAttribute($code)
                ->getSource();
        }
        return $this->_source[$code]->getOptionText($value);
    }
    
    protected function _prepareLayout() {
        /* @var $collection Mage_Catalog_Model_Resource_Product_Collection */
        /* @var $item Mage_Catalog_Model_Product */
        
        $attributeSetId = Mage::getModel('eav/entity_attribute_set')->load('Teeth', 
                'attribute_set_name')->getId();
        
        $collection = Mage::getResourceModel('catalog/product_collection');
        $collection->addAttributeToFilter('manufacturer', $this->getOptionId());
        $collection->addAttributeToFilter('attribute_set_id', $attributeSetId);
        $collection->addAttributeToFilter('type_id', 'simple');
        $collection->addCategoryFilter($this->getCategory());
        
        $collection->addAttributeToSelect(
                array('name', 'teeth_color', 'teeth_brand', 'teeth_model', 'teeth_type', 
                    'teeth_anterior_type', 'teeth_posterior_type'));
        $map = array();
        foreach ( $collection as $item ) {
            if ($item->isDisabled()) {
                continue;
            }
            $key = sprintf("%d-%d-%d-%d", $item->getData('teeth_type'), 
                    $item->getData('teeth_anterior_type'), $item->getData('teeth_posterior_type'), $item->getData('teeth_brand'));
            if (! isset($map[$key])) {
                $map[$key] = array('teeth_type'=>$item->getData('teeth_type'), 
                    'teeth_anterior_type'=>$item->getData('teeth_anterior_type'), 
                    'teeth_posterior_type'=>$item->getData('teeth_posterior_type'), 
                    'teeth_brand'=>$item->getData('teeth_brand'), 
                    'colors'=>array(),
                    'items'=>array());
            }
            if (!in_array($item->getData('teeth_color'), $map[$key]['colors'])) {
                $map[$key]['colors'][] = $item->getData('teeth_color');
            }
            $map[$key]['items'][$item->getData('teeth_model')][$item->getData('teeth_color')] = $item;
            
        }
        
        Mage::register('teeth_map', $map);
    }

}
