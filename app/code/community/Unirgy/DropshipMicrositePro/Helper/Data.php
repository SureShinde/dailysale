<?php

class Unirgy_DropshipMicrositePro_Helper_Data extends Mage_Core_Helper_Data
{
    public function getSkinUrl($path)
    {
        return Mage::getDesign()->getSkinUrl($path);
    }
    public function getSkinBaseUrl()
    {
        return Mage::getDesign()->getSkinBaseUrl();
    }
    public function checkEmailUnique($email)
    {
        if (empty($email)) {
            return false;
        } else {
            $res = Mage::getSingleton('core/resource');
            $read = $res->getConnection('udropship_read');
            $count = $read->fetchOne(
                $read->select()->from($res->getTableName('udropship/vendor'), array('count(*)'))
                    ->where('email=?', $email)
            );
            $count = $count || $read->fetchOne(
                $read->select()->from($res->getTableName('umicrosite/registration'), array('count(*)'))
                    ->where('email=?', $email)
            );
            if ($count) {
                return false;
            } else {
                return true;
            }
        }
    }
    public function checkVendorNameUnique($vendor_name)
    {
        if (empty($vendor_name)) {
            return false;
        } else {
            $res = Mage::getSingleton('core/resource');
            $read = $res->getConnection('udropship_read');
            $count = $read->fetchOne(
                $read->select()->from($res->getTableName('udropship/vendor'), array('count(*)'))
                    ->where('vendor_name=?', $vendor_name)
            );
            $count = $count || $read->fetchOne(
                $read->select()->from($res->getTableName('umicrosite/registration'), array('count(*)'))
                    ->where('vendor_name=?', $vendor_name)
            );
            if ($count) {
                return false;
            } else {
                return true;
            }
        }
    }
    public function checkUrlkeyUnique($urlkey)
    {
        if (empty($urlkey)) {
            return false;
        } else {
            $res = Mage::getSingleton('core/resource');
            $read = $res->getConnection('udropship_read');
            $count = $read->fetchOne(
                $read->select()->from($res->getTableName('udropship/vendor'), array('count(*)'))
                    ->where('url_key=?', $urlkey)
            );
            $count = $count || $read->fetchOne(
                    $read->select()->from($res->getTableName('umicrosite/registration'), array('count(*)'))
                        ->where('url_key=?', $urlkey)
                );
            if ($count) {
                return false;
            } else {
                return true;
            }
        }
    }
    public function processRandomPattern($pattern)
    {
        return preg_replace_callback('#\[([AN]{1,2})\*([0-9]+)\]#', array($this, 'convertPattern'), $pattern);
    }
    public function convertPattern($m)
    {
        $chars = (strpos($m[1], 'A')!==false ? 'ABCDEFGHJKLMNPQRSTUVWXYZ' : '').
            (strpos($m[1], 'N')!==false ? '23456789' : '');
        // no confusing chars, like O/0, 1/I
        return $this->getRandomString($m[2], $chars);
    }
    public function getRegistrationFieldsConfig()
    {
        $regFieldsConfig = Mage::getSingleton('udmspro/source')->getVendorPreferences(false);
        $fields = Mage::getConfig()->getNode('global/udropship/vendor/fields')->asCanonicalArray();
        if (!array_key_exists('comments', $fields)) {
            $regFieldsConfig['vendor_info']['value'][] = array(
                'position' => 99999,
                'label' => 'Comments',
                'value' => 'comments',
            );
        }
        return $regFieldsConfig;
    }

    public function sendVendorConfirmationEmail($vendor)
    {
        $store = Mage::app()->getDefaultStoreView();
        Mage::helper('udropship')->setDesignStore($store);
        Mage::getModel('core/email_template')->sendTransactional(
            $store->getConfig('udropship/microsite/confirmation_template'),
            $store->getConfig('udropship/vendor/vendor_email_identity'),
            $vendor->getEmail(),
            $vendor->getVendorName(),
            array(
                'store_name' => $store->getName(),
                'vendor' => $vendor,
            )
        );
        Mage::helper('udropship')->setDesignStore();

        return $this;
    }

    public function sendVendorRejectEmail($vendor)
    {
        $store = Mage::app()->getDefaultStoreView();
        Mage::helper('udropship')->setDesignStore($store);
        Mage::getModel('core/email_template')->sendTransactional(
            $store->getConfig('udropship/microsite/reject_template'),
            $store->getConfig('udropship/vendor/vendor_email_identity'),
            $vendor->getEmail(),
            $vendor->getVendorName(),
            array(
                'store_name' => $store->getName(),
                'vendor' => $vendor,
            )
        );
        Mage::helper('udropship')->setDesignStore();

        return $this;
    }

    public function isPassFieldInForm()
    {
        $regFields = Mage::helper('udmspro')->getRegFields();
        return array_key_exists('password', $regFields);
    }
    protected $_regFields;
    public function getRegFields()
    {
        if (null === $this->_regFields) {
            $this->_regFields = array();
            $columnsConfig = Mage::getStoreConfig('udsignup/form/fieldsets');
            if (!is_array($columnsConfig)) {
                $columnsConfig = Mage::helper('udropship')->unserialize($columnsConfig);
                if (is_array($columnsConfig)) {
                foreach ($columnsConfig as $fsConfig) {
                if (is_array($fsConfig)) {
                    foreach (array('top_columns','bottom_columns','left_columns','right_columns') as $colKey) {
                    if (isset($fsConfig[$colKey]) && is_array($fsConfig[$colKey])) {
                        $requiredFields = (array)@$fsConfig['required_fields'];
                        foreach ($fsConfig[$colKey] as $fieldCode) {
                            $field = Mage::helper('udmspro/protected')->getRegistrationField($fieldCode);
                            if (!empty($field)) {
                                if (in_array($fieldCode, $requiredFields)) {
                                    $field['required'] = true;
                                } else {
                                    $field['required'] = false;
                                    if (!empty($field['class'])) {
                                        $field['class'] = str_replace('required-entry', '', $field['class']);
                                    }
                                }
                                $this->_regFields[$fieldCode] = $field;
                            }
                        }
                    }}
                }}}
            }
        }
        return $this->_regFields;
    }

    public function serialize($value)
    {
        return Zend_Json::encode($value);
    }
    public function unserialize($value)
    {
        if (empty($value)) {
            $value = empty($value) ? array() : $value;
        } elseif (!is_array($value)) {
            if (strpos($value, 'a:')===0) {
                $value = @unserialize($value);
            } elseif (strpos($value, '{')===0 || strpos($value, '[{')===0) {
                $value = Zend_Json::decode($value);
            }
        }
        return $value;
    }

}
