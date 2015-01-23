<?php

/**
 * @package   Bronto\Reminder
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Reminder_Block_Adminhtml_Delivery extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    /**
     * @var string
     */
    protected $_controller = 'adminhtml_delivery';

    /**
     * @var string
     */
    protected $_clearButtonLabel = 'Clear Log History';

    /**
     * @var string
     */
    protected $_blockGroup = 'bronto_reminder';

    public function __construct()
    {
        $this->_headerText = Mage::helper('bronto_reminder')->__('Bronto Reminder Delivery Log');

        parent::__construct();

        $this->_addBackButton();
        $this->_addButton('clear', array(
            'label'   => $this->getClearButtonLabel(),
            'onclick' => "setLocation('{$this->getClearUrl()}')",
            'class'   => 'delete'
        ));

        $this->_removeButton('add');
    }

    /**
     * @return string
     */
    public function getClearUrl()
    {
        return $this->getUrl('*/*/clear');
    }

    /**
     * @return string
     */
    protected function getClearButtonLabel()
    {
        return $this->_clearButtonLabel;
    }

    /**
     * Get URL to go back
     *
     * @return string
     */
    public function getBackUrl()
    {
        return $this->getUrl('*/reminders/index');
    }
}
