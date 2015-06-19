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

        foreach($value as $key => &$item){
            $numberOfProducts = &$item['number_of_products'];
            if($numberOfProducts['checkbox'] == 'on'){
                $numberOfProducts['checkbox'] = 'checked';
            }else{
                $numberOfProducts['checkbox'] = '';
            }
            if($item['checkbox'] == 'on'){
                $item['checkbox'] = 'checked';
            }else{
                $item['checkbox'] = '';
            }
        }

        if($this->duplicate_array_unique($value)){
            Mage::throwException('Notice: Duplicate categories are prohibited.');
        }

        $modName = 'Fiuze_Bestsellercron';
        $config = Mage::getConfig();
        $configFile = $config->getModuleDir('etc', $modName).DS.'config.xml';

        $localXml = BP . DS . 'app' . DS . 'etc'.DS.'local.xml';
        //check permission for local.xml
        if(!is_writable($localXml)) {
            Mage::throwException('To create a dynamic schedule for crons file '.$localXml.' must have the permission to 0777');
        }

        $mergeModel = Mage::getModel('core/config_base');
        $mergeModel->loadFile($localXml);

        //clean crontab
        $mergeModel->setNode('crontab', '');
        $xmlData = $mergeModel->getNode()->asNiceXml();
        if (file_exists($localXml)) {
            $perm = fileperms($localXml);
        }
        //chmod($localXml, 33279);
        if(is_writable($localXml)) {
            @file_put_contents($localXml, $xmlData);
        }

        foreach($value as $key => $itemValue){
            $mergeModel->setNode('crontab/jobs/'.$key.'/schedule/cron_expr', $itemValue['task_schedule']);
            $mergeModel->setNode('crontab/jobs/'.$key.'/run/model', 'bestsellercron/cron::bestSellers');
        }

        $xmlData = $mergeModel->getNode()->asNiceXml();
        $perm;
        if (file_exists($localXml)) {
            $perm = fileperms($localXml);
        }

        //chmod($localXml, 33279);
        if(is_writable($localXml)) {
            @file_put_contents($localXml, $xmlData);
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
    private function duplicate_array_unique($array, $keyCheck = 'category')
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

