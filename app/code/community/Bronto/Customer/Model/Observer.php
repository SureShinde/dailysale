<?php

/**
 * @package     Bronto\Customer
 * @copyright   2011-2012 Bronto Software, Inc.
 */
class Bronto_Customer_Model_Observer extends Mage_Core_Model_Abstract
{
    private $_fieldMap = array();
    private $_blackList = array();
    private $_rewardsInfo = array(
        'reward_points' => array('Reward Points', 'getPointsBalance'),
        'reward_points_dollars' => array('Reward Curreny Amount', 'getFormatedCurrencyAmount')
    );
    private $_expandedfields = array(
        'country_id' => array('getCountryId', 'Country Code', 'country_code_id'),
        'region' => array('getRegionCode', 'Region Code', 'region_code_id')
    );

    /**
     * Observes module becoming enabled and displays message warning user to configure settings
     *
     * @param Varien_Event_Observer $observer
     */
    public function watchEnableAction(Varien_Event_Observer $observer)
    {
        Mage::getSingleton('adminhtml/session')->addNotice(Mage::helper('bronto_customer')->__(Mage::helper('bronto_customer')->getModuleEnabledText()));
    }

    /**
     * @param  mixed $storeId
     * @param  int   $limit
     *
     * @return array
     */
    public function processCustomersForStore($storeId = null, $limit)
    {
        if (!$limit) {
            Mage::helper('bronto_customer')->writeDebug('  Limit empty. Skipping...');

            return false;
        }

        /** @var Mage_Core_Model_Store $store */
        $store   = Mage::app()->getStore($storeId);
        $storeId = $store->getId();

        $result = array('total' => 0, 'success' => 0, 'error' => 0);
        Mage::helper('bronto_customer')->writeDebug("Starting Customer Import process for store: {$store->getName()} ({$storeId})");

        if (!$store->getConfig(Bronto_Customer_Helper_Data::XML_PATH_ENABLED)) {
            Mage::helper('bronto_customer')->writeDebug('  Module disabled for this store. Skipping...');

            return false;
        }

        // Retrieve Store's configured API Token
        $token = $store->getConfig(Bronto_Common_Helper_Data::XML_PATH_API_TOKEN);

        /** @var Bronto_Common_Model_Api $api */
        $api = Mage::helper('bronto_customer')->getApi($token);

        /** @var Bronto_Api_Contact $contactObject */
        $contactObject = $api->getContactObject();

        // Get all customers in queue who haven't been imported into bronto
        $customerRows = Mage::getModel('bronto_customer/queue')
            ->getCollection()
            ->addBrontoNotImportedFilter()
            ->addBrontoNotSuppressedFilter()
            ->orderByUpdatedAt()
            ->setPageSize($limit)
            ->addStoreFilter($storeId)
            ->getItems();

        if (empty($customerRows)) {
            Mage::helper('bronto_customer')->writeVerboseDebug('  No Customers to process. Skipping...');

            return $result;
        }

        /** @var Mage_Customer_Model_Entity_Attribute_Collection $customerAttributes */
        $customerAttributes = Mage::getModel('customer/entity_attribute_collection');
        /** @var Mage_Customer_Model_Entity_Address_Attribute_Collection $addressAttributes */
        $addressAttributes = Mage::getModel('customer/entity_address_attribute_collection')->addVisibleFilter();
        $this->_buildValidFieldMapForStore($store, $customerAttributes, $addressAttributes);
        $customerCache     = array();

        // For each Customer...
        foreach ($customerRows as $customerRow) {
            $customerId = $customerRow->getCustomerId();
            if ($customer = Mage::getModel('customer/customer')->load($customerId)/* @var $customer Mage_Customer_Model_Customer */) {
                Mage::helper('bronto_customer')->writeDebug("  Processing Customer ID: {$customerId} for Store ID: {$storeId}");
                $customerCache[] = array('customerId' => $customerId, 'storeId' => $storeId);

                /** @var Bronto_Api_Contact_Row $brontoContact */
                $brontoContact        = $contactObject->createRow(array());
                $brontoContact->email = $customer->getEmail();

                /* Process Customer Attributes */
                try {
                    $brontoContact = $this->_processAttributes($brontoContact, $customer, $customerAttributes, $store, 'customer');
                    $brontoContact = $this->_processRewardPoints($brontoContact, $customer, $store);
                    $brontoContact = $this->_processStoreCredit($brontoContact, $customer, $store);
                    foreach (Mage::helper('bronto_customer')->getAddressTypes() as $prefix => $methodName) {
                        $address = $customer->$methodName();
                        if (!empty($address)) {
                            $brontoContact = $this->_processAttributes($brontoContact, $address, $addressAttributes, $store, $prefix);
                        }
                    }

                    $brontoContact->persist();
                } catch (Exception $e) {
                    Mage::helper('bronto_customer')->writeError($e);
                }

                $result['total']++;
                try {
                    // Flush every 100 Customers
                    if ($result['total'] % 100 === 0) {
                        $result        = $this->_flushCustomers($contactObject, $customerCache, $result);
                        $customerCache = array();
                    }
                } catch (Exception $e) {
                    Mage::helper('bronto_customer')->writeError($e);

                    // Mark Customer as *not* imported
                    $customerRow->setBrontoImported(null)
                        ->save();

                    $result['error']++;
                }
            }
        }

        // Final flush (for any we miss)
        if (!empty($customerCache)) {
            $result = $this->_flushCustomers($contactObject, $customerCache, $result);
        }

        Mage::helper('bronto_customer')->writeDebug('  Success: ' . $result['success']);
        Mage::helper('bronto_customer')->writeDebug('  Error:   ' . $result['error']);
        Mage::helper('bronto_customer')->writeDebug('  Total:   ' . $result['total']);

        return $result;
    }

    /**
     * Convenience method for checking availability
     *
     * @param string $token
     * @param string $fieldId
     * @return boolean
     */
    protected function _skippableAttribute($token, $fieldId)
    {
        if (empty($fieldId) || '_none_' == $fieldId) {
            return true;
        }
        if (isset($this->_blackList[$fieldId])) {
            return true;
        }
        if (!empty($token)) {
            if (isset($this->_fieldMap[$token][$fieldId])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Convenience method for checking value availabiility
     *
     * @param string $fieldId
     * @param string $value
     * @return boolean
     */
    protected function _skippableProcessValue($fieldId, $value)
    {
        if ($this->_skippableAttribute('', $fieldId)) {
            return true;
        }
        if ('' === $value || is_null($value)) {
            return true;
        }
        return false;
    }

    /**
     * Build internal field mapping for a token for a given store
     *
     * @param Mage_Core_Model_Store $store
     * @param Iterable $attributes
     * @param Iterable $addressAttrs
     * @return void
     */
    protected function _buildValidFieldMapForStore($store, $attributes, $addressAttrs)
    {
        $helper = Mage::helper('bronto_customer');
        $token = $helper->getApiToken('store', $store->getId());
        if (!isset($this->_fieldMap[$token])) {
            $this->_fieldMap[$token] = array();
        }
        $fieldsToCheck = array();
        // Dynamic attrs
        $pathPrefix = Bronto_Customer_Helper_Data::XML_PREFIX_CUSTOMER_ATTR;
        foreach ($attributes as $attribute) {
            if ('' == $attribute->getFrontendLabel()) {
                continue;
            }
            $fieldId = $helper->getCustomerAttributeField($attribute->getAttributeCode(), 'store', $store->getId());
            if ($this->_skippableAttribute($token, $fieldId)) {
                continue;
            }
            $xmlPath = "{$pathPrefix}{$attribute->getAttributeCode()}";
            $fieldsToCheck[$fieldId] = array($xmlPath, $attribute->getFrontendLabel());
        }
        // Reward Points / Store Credit
        foreach ($this->_rewardsInfo + array('store_credit' => array('Store Credit', '_')) as $key => $labelMethodTuple) {
            list($label, $method) = $labelMethodTuple;
            $fieldId = $helper->getCustomerAttributeField($key, 'store', $store->getId());
            if ($this->_skippableAttribute($token, $fieldId)) {
                continue;
            }
            $xmlPath = "{$pathPrefix}{$key}";
            $fieldsToCheck[$fieldId] = array($xmlPath, $helper->__($label));
        }
        // Address related
        foreach ($helper->getAddressTypes() as $prefix => $methodName) {
            $pathPrefix = "bronto_customer/{$prefix}_attributes/";
            foreach ($addressAttrs as $attribute) {
                if ('' == $attribute->getFrontendLabel()) {
                    continue;
                }
                $code = $attribute->getAttributeCode();
                if (isset($this->_expandedfields[$code])) {
                    list($method, $label, $field) = $this->_expandedfields[$code];
                    $fieldId = $helper->getPrefixedAttributeField($field, $prefix, 'store', $store->getId());
                    if (!$this->_skippableAttribute($token, $fieldId)) {
                        $fieldsToCheck[$fieldId] = array("{$pathPrefix}{$field}", $helper->__($label));
                    }
                }
                $fieldId = $helper->getPrefixedAttributeField($code, $prefix, 'store', $store->getId());
                if ($this->_skippableAttribute($token, $fieldId)) {
                    continue;
                }
                $xmlPath = "{$pathPrefix}{$attribute->getAttributeCode()}";
                $fieldsToCheck[$fieldId] = array($xmlPath, $attribute->getFrontendLabel());
            }
        }
        // Do a read call, diff and warn
        if (!empty($fieldsToCheck)) {
            $configData = Mage::getModel('core/config_data');
            $fieldIds = array_keys($fieldsToCheck);
            $api = $helper->getApi($token, 'store', $store->getId());
            $fieldObject = $api->getFieldObject();
            $filter = array('id' => $fieldIds, 'type' => 'OR');
            foreach ($fieldObject->readAll($filter)->iterate() as $field) {
                $this->_fieldMap[$token][$field->id] = $field->label;
                unset($fieldsToCheck[$field->id]);
            }
            // These contain fieldIds that no longer exist in this account
            foreach ($fieldsToCheck as $fieldId => $codeLabelTuple) {
                list($xmlPath, $label) = $codeLabelTuple;
                $helper->writeError("Field mapping for store {$store->getId()} no longer exists: {$fieldId}: {$label}");
                $collection = $configData->getCollection()
                    ->addFieldToFilter('scope_id', array('eq' => $store->getId()))
                    ->addFieldToFilter('path', array('eq' => $xmlPath));
                // Remove from config data
                foreach ($collection as $config) {
                    $config->delete();
                    $this->_blackList[$fieldId] = $store->getId();
                }
            }
        }
    }

    /**
     * @param Bronto_Api_Contact_Row       $brontoContact
     * @param Mage_Customer_Model_Customer $customer
     * @param Mage_Core_Model_Store        $store
     *
     * @return Bronto_Api_Contact_Row
     */
    protected function _processRewardPoints(Bronto_Api_Contact_Row $brontoContact, Mage_Customer_Model_Customer $customer, Mage_Core_Model_Store $store)
    {
        // If Reward Points is installed
        if (Mage::helper('bronto_common')->isModuleInstalled('Enterprise_Reward')) {
            /** @var Enterprise_Reward_Model_Reward $reward */
            $reward          = Mage::getModel('enterprise_reward/reward')->setCustomerId($customer->getId())->setWebsiteId($store->getWebsiteId())->loadByCustomer();
            foreach ($this->_rewardsInfo as $key => $labelMethodTuple) {
                list($label, $methodName) = $labelMethodTuple;
                $_fieldName = Mage::helper('bronto_customer')->getCustomerAttributeField($key, 'store', $store->getId());
                $_attributeValue = $reward->$methodName();
                // Skip un-mapped or empty attributes
                if ($this->_skippableProcessValue($_fieldName, $_attributeValue)) {
                    continue;
                }

                $brontoContact->setField($_fieldName, $_attributeValue);
            }
        }

        return $brontoContact;
    }

    /**
     * @param Bronto_Api_Contact_Row       $brontoContact
     * @param Mage_Customer_Model_Customer $customer
     * @param Mage_Core_Model_Store        $store
     *
     * @return Bronto_Api_Contact_Row
     */
    protected function _processStoreCredit(Bronto_Api_Contact_Row $brontoContact, Mage_Customer_Model_Customer $customer, Mage_Core_Model_Store $store)
    {
        // If Store Credit is installed
        if (Mage::helper('bronto_common')->isModuleInstalled('Enterprise_CustomerBalance')) {
            $_fieldName = Mage::helper('bronto_customer')->getCustomerAttributeField('store_credit', 'store', $store->getId());

            /** @var Enterprise_CustomerBalance_Model_Balance $balance */
            $balance         = Mage::getModel('enterprise_customerbalance/balance')->setCustomerId($customer->getId())->setWebsiteId($store->getWebsiteId())->loadByCustomer();
            $_attributeValue = Mage::app()->getLocale()->currency($balance->getWebsiteCurrencyCode())
                ->toCurrency($balance->getAmount());

            // Skip un-mapped or empty attributes
            if ($this->_skippableProcessValue($_fieldName, $_attributeValue)) {
                return $brontoContact;
            }

            $brontoContact->setField($_fieldName, $_attributeValue);
        }

        return $brontoContact;
    }

    /**
     * Cycle through attributes and validate against Bronto Field type
     *
     * @param Bronto_Api_Contact_Row $brontoContact
     * @param                        $source
     * @param                        $attributes
     * @param Mage_Core_Model_Store  $store
     * @param string                 $type 'customer' or 'address'
     *
     * @return Bronto_Api_Contact_Row
     */
    protected function _processAttributes(Bronto_Api_Contact_Row $brontoContact, $source, $attributes, Mage_Core_Model_Store $store, $type = 'customer')
    {
        $helper = Mage::helper('bronto_customer');
        // For each Customer attribute
        foreach ($attributes as $attribute) {
            if ('' == $attribute->getFrontendLabel()) {
                continue;
            }
            $_attributeCode = $attribute->getAttributeCode();

            // Get Attribute Field
            switch ($type) {
                case 'billing_address':
                case 'address':
                    $_fieldName = $helper->getPrefixedAttributeField($_attributeCode, $type, 'store', $store->getId());
                    // Backward compatibility for country name and codes
                    if (array_key_exists($_attributeCode, $this->_expandedfields)) {
                        list($method, $label, $field) = $this->_expandedfields[$_attributeCode];
                        $_attributeValue = strtolower($source->$method());
                        $_brontoField = $helper->getPrefixedAttributeField($field, $type, 'store', $store->getId());
                        if (!$this->_skippableProcessValue($_brontoField, $_attributeValue)) {
                            $brontoContact->setField($_brontoField, $_attributeValue);
                        }
                    }
                    break;
                default:
                    $_fieldName = Mage::helper('bronto_customer')->getCustomerAttributeField($_attributeCode, 'store', $store->getId());
                    break;
            }

            // Get Customer Attribute Value
            $_attributeValue = $this->_getReadableValue($attribute, $source->getData($_attributeCode));

            // Skip un-mapped or empty attributes
            if ($this->_skippableProcessValue($_fieldName, $_attributeValue)) {
                continue;
            }

            $brontoContact->setField($_fieldName, $_attributeValue);
        }

        return $brontoContact;
    }

    /**
     * Based on attribute type, pull the value or the label
     *
     * @param string $attribute
     * @param mixed  $value
     *
     * @return mixed
     */
    protected function _getReadableValue($attribute, $value)
    {
        if ('' == $value) {
            return '';
        }

        $_attributeType = $attribute->getFrontendInput();
        $_attributeCode = $attribute->getAttributeCode();
        $_attributeBack = $attribute->getBackendType();

        // Pick up Website/Store/Group Values
        switch ($_attributeCode) {
            case 'website_id':
                $websiteModel = Mage::getModel('core/website')->load($value);

                return $websiteModel->getName();
                break;
            case 'store_id':
                $storeModel = Mage::getModel('core/store')->load($value);

                return $storeModel->getName();
                break;
            case 'group_id':
                $groupModel = Mage::getModel('customer/group')->load($value);

                return $groupModel->getCode();
                break;
            case 'country_id':
                $countryModel = Mage::getModel('directory/country')->load($value);

                return $countryModel->getName();
                break;
            default:
                break;
        }

        if ($_attributeBack == 'datetime' || $_attributeType == 'date') {
            $value = $this->_formatDateValue($value);
        }

        // Format Attribute Values
        switch ($_attributeType) {
            case 'select':
                return strtolower(Mage::helper('bronto_customer')->getAttributeAdminLabel($attribute, $value));
                break;
            case 'boolean':
                return $value == 1 ? 'true' : 'false';
            case 'multiselect':
                $values = array();
                if (!is_array($value)) {
                    $value = explode(',', $value);
                }
                if (!is_array($value)) {
                    $value = array($value);
                }

                foreach ($value as $val) {
                    $values[] = strtolower(Mage::helper('bronto_customer')->getAttributeAdminLabel($attribute, $val));
                }

                return implode(', ', $values);
                break;
            default:
                return $value;
                break;
        }
    }

    /**
     * Format the value into a Bronto acceptable date
     *
     * @param string $value
     * @return string
     */
    protected function _formatDateValue($value)
    {
        $dates = explode(' ', $value);
        $date  = $dates[0];

        if (!preg_match('/^[\d]{4}-[\d]{2}-[\d]{2}$/', $date)) {
            return '';
        } else {
            return implode('T', $dates) . Mage::getSingleton('core/date')->date('P');
        }
    }

    /**
     * @param  Bronto_Api_Contact $customerObject
     * @param  array              $customerCache
     * @param  array              $result
     *
     * @return array
     */
    protected function _flushCustomers($customerObject, $customerCache, $result)
    {
        $fieldModel = Mage::getModel('bronto_common/system_config_source_field');
        $flushResult = $customerObject->flush();
        $flushCount  = count($flushResult);
        Mage::helper('bronto_customer')->writeDebug("  Flush resulted in {$flushCount} customers processed");
        Mage::helper('bronto_customer')->writeVerboseDebug('===== FLUSH =====', 'bronto_customer_api.log');
        Mage::helper('bronto_customer')->writeVerboseDebug(var_export($customerObject->getApi()->getLastRequest(), true), 'bronto_customer_api.log');
        Mage::helper('bronto_customer')->writeVerboseDebug(var_export($customerObject->getApi()->getLastResponse(), true), 'bronto_customer_api.log');
        foreach ($flushResult as $i => $flushResultRow) {
            if ($flushResultRow->hasError()) {
                $hasError     = true;
                $errorCode    = $flushResultRow->getErrorCode();
                $errorMessage = $flushResultRow->getErrorMessage();
            } else {
                $hasError     = false;
                $errorCode    = false;
                $errorMessage = false;
            }

            if (isset($customerCache[$i])) {
                // Get Customer Object
                $customer     = Mage::getModel('customer/customer')->load($customerCache[$i]['customerId']);
                $store        = Mage::getModel('core/store')->load($customerCache[$i]['storeId']);
                $website      = Mage::getModel('core/website')->load($store->getWebsiteId());
                $storeMessage = "For `{$website->getName()}`:`{$store->getName()}`: ";

                $customerRow = Mage::getModel('bronto_customer/queue')
                    ->getCustomerRow($customerCache[$i]['customerId'], $customerCache[$i]['storeId']);
            } else {
                if ($hasError) {
                    Mage::helper('bronto_customer')->writeError("[{$errorCode}] {$errorMessage}");
                    $result['error']++;
                }

                continue;
            }

            if ($hasError) {
                // If Error Code In specified Array, suppress contact
                if (in_array($errorCode, array(302, 303, 314, 315, 317))) {
                    $customerRow->setBrontoSuppressed($errorMessage);
                }

                Mage::helper('bronto_customer')->writeError("[{$errorCode}] {$storeMessage}{$errorMessage} ({$customer->getEmail()})");

                // Mark Customer as not imported
                $customerRow->setBrontoImported(null);
                $customerRow->save();
                $result['error']++;
            } else {
                Mage::helper('bronto_customer')->writeDebug("    {$customerCache[$i]['customerId']} = SUCCESS");

                // Mark Customer as imported
                $customerRow->setBrontoImported(Mage::getSingleton('core/date')->gmtDate());
                $customerRow->save();
                $result['success']++;
            }
        }

        return $result;
    }

    /**
     * @param bool $brontoCron
     *
     * @return array
     */
    public function processCustomers($brontoCron = false)
    {
        $result = array(
            'total'   => 0,
            'success' => 0,
            'error'   => 0,
        );

        // Only allow cron to run if isset to use mage cron or is coming from bronto cron
        if (Mage::helper('bronto_customer')->canUseMageCron() || $brontoCron) {
            $limit = Mage::helper('bronto_customer')->getLimit();

            $stores = Mage::app()->getStores(true);
            foreach ($stores as $_store) {
                if ($limit <= 0) {
                    continue;
                }
                $storeResult = $this->processCustomersForStore($_store, $limit);
                $result['total'] += $storeResult['total'];
                $result['success'] += $storeResult['success'];
                $result['error'] += $storeResult['error'];
                $limit = $limit - $storeResult['total'];
            }
        }

        return $result;
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function markCustomerForReimport(Varien_Event_Observer $observer)
    {
        /* @var $customer Mage_Customer_Model_Customer */
        $customer = $observer->getCustomer();

        /* @var $contactQueue Bronto_Customer_Model_Queue */
        Mage::getModel('bronto_customer/queue')
            ->getCustomerRow($customer->getId(), $customer->getStoreId())
            ->setCreatedAt($customer->getCreatedAt())
            ->setUpdatedAt(Mage::getSingleton('core/date')->gmtDate())
            ->setBrontoImported(null)
            ->setBrontoSuppressed(null)
            ->save();
    }

    /**
     * Grab Config Data Object before save and handle the 'Create New...' value for
     * fields that were generated dynamically
     *
     * @param Varien_Event_Observer $observer
     *
     * @return Varien_Event_Observer
     */
    public function saveDynamicField(Varien_Event_Observer $observer)
    {
        $action = $observer->getEvent()->getControllerAction();

        if ($action->getRequest()->getParam('section') == 'bronto_customer') {
            $groups  = $action->getRequest()->getPost('groups');
            $section = $action->getRequest()->getParam('section');

            // Pre-Process Fields to Strip out and Handle Dynamic Fields
            $groups = $this->_handleDynamicAttributes($groups, $section);

            // Replace Existing 'groups' data with newly stripped 'groups' data and pass on to be saved
            $observer->getEvent()->getControllerAction()->getRequest()->setPost('groups', $groups);
        }

        return $observer;
    }

    /**
     * Process customer and address attributes and save back to observer
     *
     * @param  array $groups
     * @param string $section
     *
     * @return array
     */
    protected function _handleDynamicAttributes($groups, $section)
    {
        // Process Dynamic Customer Attribute Fields
        if (array_key_exists('attributes', $groups)) {
            $attrFieldsCustomer             = $this->_processDynamicAttributes($groups['attributes']['fields'], $section, 'attributes');
            $groups['attributes']['fields'] = $attrFieldsCustomer;
        }

        foreach (Mage::helper('bronto_customer')->getAddressTypes() as $prefix => $methodName) {
            $key = "{$prefix}_attributes";
            // Process Dynamic Address Attribute Fields
            if (array_key_exists($key, $groups)) {
                $attrFieldsAddress      = $this->_processDynamicAttributes($groups[$key]['fields'], $section, $key);
                $groups[$key]['fields'] = $attrFieldsAddress;
            }
        }

        // Return Updated Groups Data
        return $groups;
    }

    /**
     * Capture "Create New..." attributes, create field in Bronto, and save field id
     *
     * @param  array $attributesFields
     * @param string $section
     * @param string $group
     *
     * @return array
     */
    protected function _processDynamicAttributes($attributesFields = array(), $section, $group)
    {
        // Create Config Object
        $config = Mage::getModel('core/config');

        // Get Admin Scope Parameters
        $scopeParams = Mage::helper('bronto_common')->getScopeParams();

        // Get Array of Attributes that are hard-coded into system.xml
        $ignore = Mage::helper('bronto_customer')->getSystemAttributes();

        // Cycle Through Attribute Fields to Find and Save Dynamic Fields
        foreach ($attributesFields as $fieldId => $field) {
            // Save Dynamic 'Create New...' Fields
            if (preg_match('/_dynamic_new/', $fieldId) || preg_match('/_new/', $fieldId)) {
                // Strip off '_dynamic_new' or '_new' from Field ID to Get real Field ID
                $realField = preg_replace('/_dynamic_new|_new$/', '', $fieldId);

                if (!is_array($field)) {
                    $value = $field;
                } else {
                    $value = $field['value'];
                }

                if (is_null($value)) {
                    continue;
                }

                try {
                    /* @var $fieldObject Bronto_Api_Field */
                    $fieldObject        = Mage::helper('bronto_common')->getApi()->getFieldObject();
                    $brontoField        = $fieldObject->createRow();
                    $brontoField->name  = $fieldObject->normalize($value);
                    $brontoField->label = $value;
                    $brontoField->type  = Bronto_Api_Field::TYPE_TEXT;

                    $brontoField->save();
                    $fieldObject->addToCache($brontoField->name, $brontoField);

                    $scope = $scopeParams['scope'];
                    if ($scope != 'default') {
                        $scope .= 's';
                    }

                    // Save Field To Config
                    $config->saveConfig(
                        $section . '/' . $group . '/' . $realField,
                        $brontoField->id,
                        $scope,
                        $scopeParams[$scopeParams['scope'] . '_id']
                    );

                    // Unset Dynamic Fields
                    unset($attributesFields[$realField]);
                    unset($attributesFields[$fieldId]);
                    unset($fieldObject);
                } catch (Exception $e) {
                    Mage::helper('bronto_customer')->writeError("Unable to save new field: {$value}");
                }
            } // Save Dynamic Fields
            elseif (array_key_exists('value', $field) && !in_array($fieldId, $ignore[$group])) {
                $scope = $scopeParams['scope'];
                if ($scope != 'default') {
                    $scope .= 's';
                }

                // Save Field To Config
                $config->saveConfig(
                    $section . '/' . $group . '/' . $fieldId,
                    array_key_exists('value', $field) ? $field['value'] : '',
                    $scope,
                    $scopeParams[$scopeParams['scope'] . '_id']
                );

                // Unset Dynamic Field
                unset($attributesFields[$fieldId]);
            }
        }

        return $attributesFields;
    }
}
