<?php

class Bronto_Common_Model_Delivery extends Bronto_Api_Delivery
{
    /**
     * @var string
     */
    protected $_emailClass;

    /**
     * @see parent
     *
     * @param array $config
     */
    public function __construct($config = array())
    {
        parent::__construct($config);
        if (isset($config['email_class'])) {
            $this->_emailClass = $config['email_class'];
        }
    }

    /**
     * @see parent
     * @return string
     */
    public function getExceptionClass()
    {
        return 'Bronto_Api_Delivery_Exception';
    }

    /**
     * Expose the email template associated with this delivery
     *
     * @return string
     */
    public function getEmailClass()
    {
        return $this->_emailClass;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'Delivery';
    }

    /**
     * @return string
     */
    public function getRowClass()
    {
        return 'Bronto_Api_Delivery_Row';
    }
}
