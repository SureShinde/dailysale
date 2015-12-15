<?php

class Unirgy_DropshipMicrositePro_Model_Registration extends Unirgy_DropshipMicrosite_Model_Registration
{
    public function getRegFields()
    {
        return Mage::helper('udmspro')->getRegFields();
    }
    public function attachLabelVars()
    {
        foreach ($this->getRegFields() as $name=>$rf) {
            switch ($rf['type']) {
                case 'statement_po_type': case 'payout_po_status_type': case 'notify_lowstock':
                case 'select': case 'multiselect': case 'checkboxes':
                    $srcModel = $rf['source_model'];
                    $source = Mage::getSingleton($srcModel);
                    if (is_callable(array($source, 'setPath'))) {
                        $source->setPath(!empty($rf['source']) ? $rf['source'] : $name);
                    }
                    if ($rf['type']=='multiselect') {
                        $msValues = $this->getData($name);
                        if (!is_array($msValues)) {
                            $msValues = explode(',', $msValues);
                        }
                        $values = array_map('trim', $msValues);
                    } else {
                        $values = $this->getData($name);
                    }
                    $values = array_filter((array)$values);
                    if (!empty($values) && is_callable(array($source, 'getOptionLabel'))) {
                        $lblValues = array();
                        foreach ($values as $value) {
                            $lblValues[] = $source->getOptionLabel($value);
                        }
                        $lblValues = implode(', ', $lblValues);
                        $this->setData($name.'_label', $lblValues);
                    }
                    break;
            }
        }
    }

    public function validate()
    {
        $hlp = Mage::helper('umicrosite');
        $dhlp = Mage::helper('udropship');
        extract($this->getData());

        $hasPasswordField = false;
        foreach ($this->getRegFields() as $rf) {
            $rfName = str_replace('[]', '', $rf['name']);
            if (!empty($rf['required'])
                && in_array($this->getData($rfName), array('',null), true)
                && !in_array($rf['type'], array('image','file'))
                && !in_array($rfName, array('payout_paypal_email'))
            ) {
                if ($rfName != 'region_id' || !$this->getData('region')) {
                    Mage::throwException(Mage::helper('udropship')->__('Incomplete form data'));
                }
            }
            $hasPasswordField = $hasPasswordField || in_array($rfName, array('password_confirm','password'));
            if ($rfName=='password_confirm'
                && $this->getData('password') != $this->getData('password_confirm')
            ) {
                Mage::throwException(Mage::helper('udropship')->__('Passwords do not match'));
            }
        }

        $this->setStreet(@$street1."\n".@$street2);
        $this->initPassword(@$password);
        $this->initUrlKey(@$url_key);
        $this->setRemoteIp($_SERVER['REMOTE_ADDR']);
        $this->setRegisteredAt(now());
        $this->setStoreId(Mage::app()->getStore()->getId());
        $dhlp->processCustomVars($this);
        $this->attachLabelVars();

        return $this;
    }
    public function initPassword($password=null)
    {
        if (empty($password)) {
            $password = $this->generatePassword();
        }
        $this->setPasswordEnc(Mage::helper('core')->encrypt($password));
        $this->setPasswordHash(Mage::helper('core')->getHash($password, 2));
        $this->unsPassword();
        return $this;
    }
    public function formatUrlKey($str)
    {
        $urlKey = preg_replace('#[^0-9a-z]+#i', '-', Mage::helper('catalog/product_url')->format($str));
        $urlKey = strtolower($urlKey);
        $urlKey = trim($urlKey, '-');

        return $urlKey;
    }
    public function initUrlKey($urlKey=null)
    {
        if (empty($urlKey)) {
            $urlKey = $this->formatUrlKey($this->getData('vendor_name'));
        }
        $this->setData('url_key', $urlKey);
        return $this;
    }
    protected function generatePassword()
    {
        return Mage::helper('udmspro')->processRandomPattern('[AN*6]');
    }

    protected function _beforeSave()
    {
        parent::_beforeSave();
        Mage::helper('udropship')->processCustomVars($this);
    }

}