<?php
class Allecon_Teeth_Helper_Data extends Mage_Core_Helper_Abstract {

    public function getCategoriesBackUp($entity_id){

        $manu = array();
        $mcategories = Mage::getModel('catalog/category')->load($entity_id)->getChildrenCategories();

        $mcategory = Mage::getModel("catalog/category")->load($entity_id);
        $layer = Mage::getModel("catalog/layer");
        $layer->setCurrentCategory($mcategory)->setIsAnchor(1);
        $attributes = $layer->getFilterableAttributes();

        foreach ($attributes as $attribute) {

            if ($attribute->getAttributeCode() == 'manufacturer') {

                $filterBlockName = 'catalog/layer_filter_attribute';
                $result = Mage::app('base')->getLayout()->createBlock($filterBlockName)->setLayer($layer)->setAttributeModel($attribute)->init();
                $j = 0;
                foreach($result->getItems() as $option) {

                    if($option->getLabel() && $option->getValue()){
                        $manu[$entity_id][$j]['id'] = $option->getValue();
                        $manu[$entity_id][$j]['label'] = $option->getLabel() ;
                    } $j++;
                }
            }
        }

        return $manu;

    }
    public function getCategories($entity_id){

        $manu = array();
//        $_category = Mage::getModel('catalog/category')->load($entity_id);
//        $collection = Mage::getResourceModel('catalog/product_collection')
//        ->addCategoryFilter($_category)
//        ->addAttributeToSelect('name')
//        ->addAttributeToSelect('*')
//        ->load();
        $attribute = Mage::getModel('eav/entity_attribute');
        $attributeId = $attribute->getIdByCode('catalog_product', 'manufacturer');
        $attribute->load($attributeId);
        $attribute_options_model = Mage::getModel('eav/entity_attribute_source_table');
        $attribute_table = $attribute_options_model->setAttribute($attribute);
        $options = $attribute_options_model->getAllOptions(false);

        $proCollection = Mage::getModel('catalog/category')->load($entity_id)
            ->getProductCollection()
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('manufacturer')
            ->addAttributeToSelect('*')
            ->load();
        $manufacturer = array_unique($proCollection->getColumnValues('manufacturer'));
        if( !empty($manufacturer) ){
            $j = 0;
            foreach( $manufacturer as $mv){
                $manu[$entity_id][$j]['id'] = $mv;
                foreach($options as $av ){
                    if( $av['value'] == $mv ){
                        $manu[$entity_id][$j]['label'] = $av['label'];
                    }
                }

                $j++;
            }
        }

        return $manu;

    }
}