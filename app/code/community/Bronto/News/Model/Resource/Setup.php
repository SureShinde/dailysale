<?php

class Bronto_News_Model_Resource_Setup extends Mage_Core_Model_Resource_Setup
{

    /**
     * Creates the initial items in the queue
     *
     * @return boolean
     */
    public function createInitialItems()
    {
        $helper = Mage::helper('bronto_news');
        if (!$helper->validApiToken()) {
            return false;
        }

        try {
            $helper->pullNewItems();
        } catch (Exception $e) {
            $helper->writeError('Failed to pull items on install.');

            return false;
        }

        return true;
    }

    /**
     * Load the initial config data for the module
     */
    public function loadInitialSettings()
    {
        Mage::getConfig()->reinit();
        Mage::app()->reinitStores();
    }
}
