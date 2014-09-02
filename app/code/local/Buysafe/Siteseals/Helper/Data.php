<?php 
class Buysafe_Siteseals_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function hasHash()
    {
        if(Mage::getStoreConfig('buysafe_options/buysafe_config/buysafe_hash')){
        	return true;
        }
    }
    
    public function getHash()
    {
        if($hash = Mage::getStoreConfig('buysafe_options/buysafe_config/buysafe_hash')){
        	return $hash;
        }
    } 
    
    public function isEnabled()
    {
        return $this->hasHash() ? true : false;
    } 
    
    public function getKickerList()
    {
        return array(
            array('value' => 1,  'label' => 'Kicker Guaranteed Border 200x40'),
            array('value' => 2,  'label' => 'Kicker Guaranteed Border 200x70'),
            array('value' => 3,  'label' => 'Kicker Guaranteed Border Icons 200x85'),
            array('value' => 4,  'label' => 'Kicker Guaranteed Corners 200x40'),
            array('value' => 5,  'label' => 'Kicker Guaranteed Corners 200x70'),
            array('value' => 6,  'label' => 'Kicker Guaranteed Corners Icons 200x70'),
            array('value' => 7,  'label' => 'Kicker Guaranteed Greyscale 410x60'),
            array('value' => 8,  'label' => 'Kicker Guaranteed Greyscale 465x45'),
            array('value' => 9,  'label' => 'Kicker Guaranteed Medallion 430x45'),
            array('value' => 10, 'label' => 'Kicker Guaranteed Medallion Text'),
            array('value' => 11, 'label' => 'Kicker Guaranteed PostIt 170x120'),
            array('value' => 12, 'label' => 'Kicker Guaranteed PostIt 170x52'),
            array('value' => 13, 'label' => 'Kicker Guaranteed PostIt 170x92'),
            array('value' => 14, 'label' => 'Kicker Guaranteed PostIt 275x225'),
            array('value' => 15, 'label' => 'Kicker Guaranteed PostIt 280x90'),
            array('value' => 16, 'label' => 'Kicker Guaranteed Ribbon 200x30'),
            array('value' => 17, 'label' => 'Kicker Guaranteed Ribbon 200x42'),
            array('value' => 18, 'label' => 'Kicker Guaranteed Ribbon 200x44'),
            array('value' => 19, 'label' => 'Kicker Guaranteed Ribbon 200x90'),
            array('value' => 20, 'label' => 'Kicker Guaranteed Seal 200x45'),
            array('value' => 21, 'label' => 'Kicker Guaranteed Seal 200x85'),
            array('value' => 22, 'label' => 'Kicker Guaranteed Seal Icons 200x85'),
            array('value' => 23, 'label' => 'Kicker Guaranteed Tower 110x130'),
            array('value' => 24, 'label' => 'Kicker Guaranteed Tower 110x220'),
        );
    }
    
    public function register()
    {
    	$modules = Mage::getConfig()->getNode('modules')->children();
		$modulesArray = (array)$modules;		
		if ($modulesArray[base64_decode('RW50ZXJwcmlzZV9QY2k=')]->active == 'false') {
			return base64_decode('UHJvZmVzc2lvbmFs');			
		} elseif (isset($modulesArray[base64_decode('RW50ZXJwcmlzZV9FbnRlcnByaXNl')])) {
		    return base64_decode('RW50ZXJwcmlzZQ==');
		} else {
			return base64_decode('Q29tbXVuaXR5');
		}
    }
    
}