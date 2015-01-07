<?php

/**
 * @package     Bronto\Reviews
 * @copyright   2011-2013 Bronto Software, Inc.
 */
class Bronto_Reviews_Model_System_Config_Source_Message
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return Mage::helper('bronto_reviews/message')->getMessagesOptionsArray();
    }
}