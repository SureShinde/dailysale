<?php

/**
 * @category    Fiuze
 * @package     Fiuze_Deals
 * @author     Alena Tsareva <alena.tsareva@webinse.com>
 */
class Fiuze_Deals_Block_Adminhtml_Deals_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{

    protected $_blockGroup = 'fiuze_deals';

    public function __construct()
    {
        parent::__construct();
        $this->_objectId = 'id';
        $this->_controller = 'adminhtml_deals';
        $this->_updateButton('save', 'label', Mage::helper('fiuze_deals')->__('Save'));
        $this->_removeButton('delete', 'label', Mage::helper('fiuze_deals')->__('Delete User'));
        $this->_updateButton('back', 'label', Mage::helper('fiuze_deals')->__('Back'));
        $this->_updateButton('back', 'onclick', 'setLocation(\'' . $this->getUrl('*/*/new') . '\')');
        $this->_addButton('back_grid', array(
                'label' => Mage::helper('fiuze_deals')->__('Back to deals'),
                'onclick' => 'setLocation(\'' . $this->getUrl('*/*/list') . '\')')
        );


    }

    public function getHeaderText()
    {
        return Mage::helper('fiuze_deals')->__('Edit Deal Product');
    }

    public function getHeaderCssClass()
    {
        return 'icon-head head-products';
    }

}

