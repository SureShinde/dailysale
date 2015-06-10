<?php
class Fiuze_Bestsellercron_Model_System_Config_Backend_General extends Mage_Adminhtml_Model_System_Config_Backend_Serialized_Array
{
    protected $_eventPrefix = 'bestsellercron_system_config_backend_general';
    /**
     * Unset array element with '__empty' key
     */
    protected function _beforeSave()
    {
        $value = $this->getValue();
        if (is_array($value)) {
            unset($value['__empty']);
        }

        if($this->вuplicate_array_unique($value)){
            Mage::throwException('Notice: Duplicate categories are prohibited.');
        }

        $this->setValue($value);
        parent::_beforeSave();
    }

    /**
     * Check duplicate category
     * @param $array
     * @param string $keyCheck
     * @return bool
     */
    private function вuplicate_array_unique($array, $keyCheck = 'category')
    {
        $tmpValue = array();
        $tmpArray  = $array;
        $result = false;
        while(list($key, $val) = each($tmpArray)){
            $tmpValue[$key] = $val;
            unset($tmpArray[$key]);
            foreach($tmpArray as $tmpArray_key => $tmpArray_value){
                if($tmpArray[$tmpArray_key][$keyCheck] == $tmpValue[$key][$keyCheck]){
                    unset($tmpArray[$tmpArray_key]);
                    $result = true ;
                }
            }
        }
        return $result;
    }
}

