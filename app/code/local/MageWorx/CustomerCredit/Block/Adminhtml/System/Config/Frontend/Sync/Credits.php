<?php
/**
 * MageWorx
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MageWorx EULA that is bundled with
 * this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.mageworx.com/LICENSE-1.0.html
 *
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@mageworx.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the extension
 * to newer versions in the future. If you wish to customize the extension
 * for your needs please refer to http://www.mageworx.com/ for more information
 * or send an email to sales@mageworx.com
 *
 * @category   MageWorx
 * @package    MageWorx_CustomerCredit
 * @copyright  Copyright (c) 2011 MageWorx (http://www.mageworx.com/)
 * @license    http://www.mageworx.com/LICENSE-1.0.html
 */
 
/**
 * Customer Credit extension
 *
 * @category   MageWorx
 * @package    MageWorx_CustomerCredit
 * @author     MageWorx Dev Team <dev@mageworx.com>
 */

class MageWorx_CustomerCredit_Block_Adminhtml_System_Config_Frontend_Sync_Credits extends Mage_Adminhtml_Block_System_Config_Form_Field
{    
    
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element) {        
        $html = $element->getElementHtml();
        $this->setElement($element);
        return $html ."<br><br>".$this->_getAddRowButtonHtml($element->getValue());
    }

    protected function _getAddRowButtonHtml($type) {        
        $title = $this->__('Sync Credits');
        $buttonBlock = $this->getElement()->getForm()->getParent()->getLayout()->createBlock('adminhtml/widget_button');

        $_websiteCode = $buttonBlock->getRequest()->getParam('website');
    
        $url = Mage::helper('adminhtml')->getUrl('customercredit/adminhtml_credit/sync/',array('sync_type'=>$type));
        $html = $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setType('button')
                ->setId('sync_button')
                ->setLabel($this->__($title))
                ->setOnClick("setSync()")
                ->toHtml();
        $html .= "<script type='text/javascript'>
            function setSync() {
                var syncType = $('mageworx_customers_customercredit_credit_sync_credits').value;
                var url = '" . $url . "';
                
                return window.location.href=url+'sync_type/'+syncType+'/';
            }
        </script>
        ";
        return $html;
    }

}