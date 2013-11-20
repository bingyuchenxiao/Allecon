<?php

require_once 'Mage/Checkout/controllers/CartController.php';

class Allecon_Teeth_FormController extends Mage_Checkout_CartController {

    public function indexAction() {
        $id = $this->getRequest()->getParam('m');
        $option = Mage::getModel('eav/entity_attribute_option')->load($id);
        /* @var $source Mage_Eav_Model_Entity_Attribute_Source_Table */
        $source = Mage::getModel('catalog/product')->getResource()->getAttribute('manufacturer')->getSource();
        if (! $source->getOptionId($id)) {
            $this->_redirect('/');
            return;
        }
        Mage::register('current_manufacturer', $id);
        
        $id = $this->getRequest()->getParam('c');
        $category = Mage::getModel('catalog/category')->load($id);
        if (! $category->getId()) {
            $this->_redirect('/');
            return;
        }
        Mage::register('current_category', $category);
        
        $this->loadLayout();

        $this->_initLayoutMessages('checkout/session');
        $this->_initLayoutMessages('catalog/session');
        
        $this->renderLayout();
    }

    public function addAction() {
        $id = $this->getRequest()->getParam('m');
        $option = Mage::getModel('eav/entity_attribute_option')->load($id);
        /* @var $source Mage_Eav_Model_Entity_Attribute_Source_Table */
        $source = Mage::getModel('catalog/product')->getResource()->getAttribute('manufacturer')->getSource();
        
        if (! $source->getOptionId($id)) {
            $this->_redirect('/');
            return;
        }
        
        $qtys = $this->getRequest()->getParam('qtys');
        $cart = $this->_getCart();
        $totalQty = 0;
        
        foreach ($qtys as $productId=>$qty) {
            $qty = (int) $qty;
            if ($qty) {
                $product = Mage::getModel('catalog/product')
                    ->setStoreId(Mage::app()->getStore()->getId())
                    ->load($productId);
            
                try {
                    $cart->addProduct($product, array('qty'=>$qty));
                    $totalQty += $qty;
                } catch (Exception $e) {
                    $this->_getSession()->addError($this->__("Failed add '%s' to your shopping cart.", $product->getName()));
                }
                
            }
        }
        if ($totalQty) {
            $cart->save();

            $message = $this->__('%s teeth products was added to your shopping cart.', $totalQty);
            $this->_getSession()->addSuccess($message);
            
            $this->_getSession()->setCartWasUpdated(true);
        }
        
        $this->_redirect("*/*/", array('m'=>$id));
    }

}
