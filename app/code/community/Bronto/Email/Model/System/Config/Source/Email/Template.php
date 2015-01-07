<?php

class Bronto_Email_Model_System_Config_Source_Email_Template extends Mage_Adminhtml_Model_System_Config_Source_Email_Template
{

    /**
     * Generate list of email templates
     *
     * @return array
     */
    public function toOptionArray()
    {
        // If Collection isn't already in registry, create it
        if (!$collection = Mage::registry('config_system_email_template')) {
            // Define Tables
            $templateTable = Mage::getSingleton('core/resource')->getTableName('bronto_email/template');
            $brontoTable   = Mage::getSingleton('core/resource')->getTableName('bronto_email/message');

            // Load Collection
            $collection = Mage::getModel('bronto_email/template')->getCollection();

            // Apply conditional logic to handle 1.9 overriding collection _construct
            if (Mage::helper('bronto_common')->isVersionMatch(Mage::getVersionInfo(), 1, array(4, 5, array('edition' => 'Professional', 'major' => 9), 10))) {
                $collection->getSelect()->joinLeft(
                    $brontoTable,
                    "`{$templateTable}`.`template_id` = `{$brontoTable}`.`core_template_id`"
                );
            }

            // If module is enabled
            if (Mage::helper('bronto_email')->isEnabled()) {
                // If Store Scope
                if (Mage::app()->getRequest()->getParam('store')) {
                    // if Store ID Specified, filter collection
                    if ($storeCode = Mage::app()->getRequest()->getParam('store')) {
                        $store   = Mage::app()->getStore($storeCode);
                        $storeId = $store->getId();

                        $collection->addStoreViewFilter($storeId);
                    }
                }

                // Add Where statement to prevent loading templates without core_template_id
                $collection->getSelect()->where("`{$brontoTable}`.`core_template_id` IS NOT NULL");
            }

            $collection->addOrder('template_code', 'asc')->load();

            Mage::register('config_system_email_template', $collection);
        }

        // Get Array of Template Options
        $options = $collection->toOptionArray();

        // Set up Default Template Name
        $templateName = Mage::helper('adminhtml')->__('Default Template from Locale');

        // Add support for Template configuration page
        $pathParts = explode('/', $this->getPath());

        if ('bronto_email' == $pathParts[0] && 'templates' == $pathParts[1]) {
            $path = str_replace('-', '/', array_pop($pathParts));
        } else {
            $path = $this->getPath();
        }

        // Add a 'Do Not Send' option
        array_unshift(
            $options, array(
                'value' => 'nosend',
                'label' => 'Do Not Send',
            )
        );

        $nodeName = str_replace('/', '_', $path);

        $templateLabelNode = Mage::app()->getConfig()->getNode(self::XML_PATH_TEMPLATE_EMAIL . $nodeName . '/label');
        if ($templateLabelNode) {
            $templateName = Mage::helper('adminhtml')->__((string)$templateLabelNode);
            $templateName = Mage::helper('adminhtml')->__('%s (Default Template from Locale)', $templateName);
        }

        array_unshift(
            $options, array(
                'value' => $nodeName,
                'label' => $templateName
            )
        );

        return $options;
    }

}
