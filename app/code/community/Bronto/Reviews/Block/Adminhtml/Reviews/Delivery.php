<?php

class Bronto_Reviews_Block_Adminhtml_Reviews_Delivery extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    protected $_helper;
    protected $_blockGroup = 'bronto_reviews';
    protected $_controller = 'adminhtml_reviews';
    protected $_clearCancelledLabel = 'Clear Cancelled Logs';
    protected $_clearOldLabel = 'Clear Old Logs';

    /**
     * @see parent
     */
    public function __construct()
    {
        $this->_helper = Mage::helper($this->_blockGroup);
        $this->_headerText = $this->_helper->__('Bronto Post-Purchase Delivery Log');
        parent::__construct();
        $this->_addBackButton();
        $this->_addButton('clear_cancelled', array(
            'label' => $this->getClearCancelledLabel(),
            'onclick' => 'setLocation(\'' . $this->getClearCancelledUrl() . '\')',
            'class' => 'delete'
        ));
        $this->_addButton('clear_old', array(
            'label' => $this->getClearOldLabel(),
            'onclick' => 'setLocation(\'' . $this->getClearOldUrl() . '\')',
            'class' => 'delete'
        ));
        $this->_removeButton('add');
    }

    /**
     * @return string
     */
    public function getClearCancelledUrl()
    {
        return $this->getUrl('*/*/clear', array('type' => 'cancelled'));
    }

    /**
     * @return string
     */
    public function getClearOldUrl()
    {
        return $this->getUrl('*/*/clear', array('type' => 'old'));
    }

    /**
     * @return string
     */
    public function getClearCancelledLabel()
    {
        return $this->_helper->__($this->_clearCancelledLabel);
    }

    /**
     * @return string
     */
    public function getClearOldLabel()
    {
        return $this->_helper->__($this->_clearOldLabel);
    }

    /**
     * Goes back to the configuration url
     *
     * @return string
     */
    public function getBackUrl()
    {
        return $this->getUrl('*/system_config/edit', array(
            'section' => 'bronto_reviews',
            '_current' => true
        ));
    }
}
