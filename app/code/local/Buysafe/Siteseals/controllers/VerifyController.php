<?php 
class Buysafe_Siteseals_VerifyController extends Mage_Core_Controller_Front_Action {        
    public function indexAction() {
    	$values = array(
    		'Platform Edition' => Mage::helper('buysafe')->register(),
    		'Version Number' => Mage::getVersion(),
    		'Base URL' => Mage::getBaseUrl($type = Mage_Core_Model_Store::URL_TYPE_LINK, $secure = null),
    		'Hash Value' => Mage::helper('buysafe')->getHash(),
    	);
    	$toHtml = '';
    	foreach ($values as $label => $value) {
    		$toHtml .= '<strong>'.$label.':</strong>';
    		$toHtml .= '&nbsp;&nbsp;'.$value.'<br/>';
    	}
    	echo $toHtml;
    }
}