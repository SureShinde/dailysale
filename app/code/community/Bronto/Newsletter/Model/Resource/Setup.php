<?php

class Bronto_Newsletter_Model_Resource_Setup extends Mage_Core_Model_Resource_Setup
{
    /**
     * Removes the ListIds in the default scope
     *
     * @return Bronto_Newsletter_Model_Resource_Setup
     */
    public function removeListsInDefaultScope()
    {
        $path = 'bronto_newsletter/contacts/lists';
        $this->run("
          DELETE FROM `{$this->getTable('core/config_data')}`
          WHERE `path`='{$path}' AND `scope`='default' AND `scope_id`=1;");
    }
}
