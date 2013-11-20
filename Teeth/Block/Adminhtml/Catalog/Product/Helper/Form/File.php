<?php

class Allecon_Teeth_Block_Adminhtml_Catalog_Product_Helper_Form_File extends Varien_Data_Form_Element_File
{

	/**
	 * Return element html code
	 *
	 * @return string
	 */
	public function getElementHtml()
	{
		$html = '';

		if ((string)$this->getValue()) {
			$url = $this->_getUrl();

			if( !preg_match("/^http\:\/\/|https\:\/\//", $url) ) {
				$url = Mage::getBaseUrl('media') . $url;
			}

			$html = '<a href="' . $url . '">'. $this->getValue(). '</a> ';
		}
		$this->setClass('input-file');
		$html .= parent::getElementHtml();
		$html .= $this->_getDeleteCheckbox();

		return $html;
	}

	/**
	 * Return html code of hidden element
	 *
	 * @return string
	 */
	protected function _getHiddenInput()
	{
		return '<input type="hidden" name="' . parent::getName() . '[value]" value="' . $this->getValue() . '" />';
	}

	/**
	 * Return name
	 *
	 * @return string
	 */
	public function getName()
	{
		return  $this->getData('name');
	}

    protected function _getUrl()
    {
        $url = false;
        if ($this->getValue()) {
            $url = Mage::getBaseUrl('media').'catalog/product_pdf/'. $this->getValue();
        }
        return $url;
    }

    /**
     * Return html code of delete checkbox element
     *
     * @return string
     */
    protected function _getDeleteCheckbox()
    {
    	$html = '';
    	if ($this->getValue()) {
    		$label = Mage::helper('core')->__('Delete');
    		$html .= '<span class="delete-image">';
    		$html .= '<input type="checkbox"'
    				. ' name="' . parent::getName() . '[delete]" value="1" class="checkbox"'
    						. ' id="' . $this->getHtmlId() . '_delete"' . ($this->getDisabled() ? ' disabled="disabled"': '')
    						. '/>';
    		$html .= '<label for="' . $this->getHtmlId() . '_delete"'
    				. ($this->getDisabled() ? ' class="disabled"' : '') . '> ' . $label . '</label>';
    		$html .= $this->_getHiddenInput();
    		$html .= '</span>';
    	}

    	return $html;
    }

}