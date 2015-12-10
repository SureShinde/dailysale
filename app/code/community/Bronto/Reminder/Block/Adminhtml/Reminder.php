<?php

/**
 * @package   Bronto\Reminder
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Reminder_Block_Adminhtml_Reminder extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    /**
     * @var string
     */
    protected $_controller = 'adminhtml_reminder';

    /**
     * @var string
     */
    protected $_blockGroup = 'bronto_reminder';

    public function __construct()
    {
        $this->_headerText     = Mage::helper('bronto_reminder')->__('Bronto Reminder Email Rules');
        $this->_addButtonLabel = Mage::helper('bronto_reminder')->__('Add New Rule');
        parent::__construct();
        $this->setTemplate('bronto/reminder/grid/container.phtml');
    }

    /**
     * @return Mage_Core_Block_Abstract
     */
    protected function _prepareLayout()
    {
        if (!Mage::helper('bronto_reminder')->isEnabledForAny()) {
            return parent::_prepareLayout();
        }

        if (Mage::helper('bronto_reminder')->isLogEnabled()) {
            $this->_addButton(
                'log_button',
                array(
                    'label'   => Mage::helper('adminhtml')->__('Delivery Log'),
                    'onclick' => "setLocation('{$this->getLogUrl()}')",
                    'class'   => 'go',
                ),
                0,
                1
            );
        }

        return parent::_prepareLayout();
    }

    /**
     * Get URL for transactional email log
     *
     * @return string
     */
    public function getLogUrl()
    {
        return $this->getUrl('*/delivery/index');
    }

    /**
     * Get link to transactional email configuration
     *
     * @return mixed
     */
    public function getConfigLink()
    {
        return Mage::helper($this->_blockGroup)->getConfigLink();
    }
}
