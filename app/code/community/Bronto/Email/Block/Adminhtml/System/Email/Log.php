<?php

/**
 * @package     Bronto\Email
 * @copyright   2011-2013 Bronto Software, Inc.
 */
class Bronto_Email_Block_Adminhtml_System_Email_Log extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    /**
     * @var string
     */
    protected $_controller = 'adminhtml_system_email_log';

    /**
     * @var string
     */
    protected $_clearButtonLabel = 'Clear Log History';

    /**
     * @var string
     */
    protected $_blockGroup = 'bronto_email';

    public function __construct()
    {
        $this->_headerText = Mage::helper('bronto_email')->__('Bronto Transactional Email Delivery Log');

        parent::__construct();

        $this->_addBackButton();
        $this->_addButton('clear', array(
            'label'   => $this->getClearButtonLabel(),
            'onclick' => 'setLocation(\'' . $this->getClearUrl() . '\')',
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
        return $this->getUrl('*/system_email_template/index');
    }
}
