<?php

class IWD_AddressVerification_Helper_Data extends Mage_Core_Helper_Abstract
{
    protected $_agree = null;

    public function isAddressVerificationEnabled()
    {
        return (bool)Mage::getStoreConfig('addressverification/general/enabled');
    }
    
	public function isMageEnterprise(){
		return Mage::getConfig()->getModuleConfig('Enterprise_Enterprise') && Mage::getConfig()->getModuleConfig('Enterprise_AdminGws') && Mage::getConfig()->getModuleConfig('Enterprise_Checkout') && Mage::getConfig()->getModuleConfig('Enterprise_Customer');
	}

    public function getMagentoVersion()
    {
		$ver_info = Mage::getVersionInfo();
		$mag_version	= "{$ver_info['major']}.{$ver_info['minor']}.{$ver_info['revision']}.{$ver_info['patch']}";
		
		return $mag_version;
    }  

    public function getEnabledVerification()
    {
    	if($this->isUPSAddressVerificationEnabled())
    		return 'ups';
    	if($this->isUSPSAddressVerificationEnabled())
    		return 'usps';

    	return false;
    }
    
    public function isUPSAddressVerificationEnabled()
    {
    	return (bool)Mage::getStoreConfig('addressverification/ups_address_verification/enabled');
    }

    public function isUSPSAddressVerificationEnabled()
    {
    	return (bool)Mage::getStoreConfig('addressverification/usps_address_verification/enabled');
    }
    
    public function allowNotValidAddress()
    {
    	return (bool)Mage::getStoreConfig('addressverification/general/allow_not_valid_address');
    }
    
	function isMobile()
	{
		$mobiles = array('foma','softbank','android','kddi','dopod','helio','hosin','huawei','coolpad',
		'webos','techfaith','ktouch','nexian','wellcom','bunjalloo','maui','mmp','wap','phone','iemobile',
		'longcos','pantech','gionee','portalmmm','haier','mobileexplorer','palmsource',
		'palmscape','motorola','nokia','palm','iphone','ipad','ipod','sony','ericsson','blackberry',
		'cocoon','blazer','lg','amoi','xda','mda','vario','htc','samsung','sharp','sie-','alcatel','benq',
		'ipaq','mot-','playstation portable','hiptop','nec-','panasonic','philips','sagem','sanyo',
		'spv','zte','sendo','symbian','symbianos','elaine','palm','series60','windows ce','obigo',
		'netfront','openwave','mobilexplorer','operamini','opera mini','digital paths','avantgo',
		'xiino','novarra','vodafone','docomo','o2','mobile','wireless','j2me','midp','cldc',
		'up.link','up.browser','smartphone','cellphone');

		$agent	= strtolower($_SERVER['HTTP_USER_AGENT']);
		foreach ($mobiles as $device)
		{
			if (FALSE !== (strpos($agent, $device)))
				return TRUE;
		}
		
		return FALSE;    
	}
    
	function iwd_log($err, $file_name = false)
	{
        $logdir = Mage::getBaseDir().'/var/log/';
        if(empty($file_name))
        	$file_name = 'iwd_av';
		$file = $logdir.$file_name.'.log';
		
		$fp   = fopen($file, 'a+');
		if($fp)
		{
			$time = date('Y-d-m H:i:s');
			//TO DO- add exec("chmod 777 path/logs/".$file_name."_".date("Y_d_m")."");
			fwrite($fp, '['.$time.']'.$err."\n\n");
			fclose($fp);
		}        
	}
}