<?php

/**
 * @package     Bronto\Email
 * @copyright   2011-2013 Bronto Software, Inc.
 */
class Bronto_Email_Model_Mysql4_Log_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    protected function _construct()
    {
        parent::_construct();
        $this->_init('bronto_email/log');
    }

    /**
     * Specify collection select order by attribute value
     * Backward compatibility with EAV collection
     *
     * @param string $attribute
     * @param string $dir
     *
     * @return Bronto_Email_Model_Mysql4_Log_Collection
     */
    public function addAttributeToSort($attribute, $dir = 'asc')
    {
        $this->addOrder($attribute, $dir);

        return $this;
    }

    /**
     * Delete all the entities in the collection
     */
    public function delete()
    {
        foreach ($this->getItems() as $item) {
            $item->delete($item);
        }

        return $this;
    }
}
