<?php

class Bronto_Reviews_Model_Process_Context extends Varien_Object
{
    /**
     * Copies the contents of this context into a new context
     *
     * @return Bronto_Reviews_Model_Process_Context
     */
    public function hardCopy()
    {
        return Mage::getModel('bronto_reviews/process_context', $this->getData() + array(
            'parent' => $this
        ));
    }

    /**
     * Is the sending pipeline unlocked for this context
     *
     * @return boolean
     */
    public function isSendingUnlocked()
    {
        if (!$this->hasConcurrentLimit() || $this->getConcurrentLimit() < 0) {
            return true;
        }
        return $this->getCurrentlyScheduled() < $this->getConcurrentLimit();
    }

    /**
     * Gets a child post type or review request setting
     *
     * @return string
     */
    public function getPostType()
    {
        if ($this->hasPost()) {
            return $this->getPost()->getPostType();
        }
        return 'settings';
    }

    /**
     * Modifies currentScheduled number for future reference
     *
     * @param int $amount
     * @return Bronto_Reviews_Model_Process_Context
     */
    public function incrementSchedule($amount = 1)
    {
        if ($this->hasCurrentlyScheduled()) {
            $this->setCurrentlyScheduled($this->getCurrentlyScheduled() + $amount);
        } else {
            $this->setCurrentlyScheduled($amount);
        }
        return $this;
    }

    /**
     * Always return an array
     * @return array
     */
    public function getExtra()
    {
        if ($this->hasExtra()) {
            return $this->getData('extra');
        }
        return array();
    }
}
