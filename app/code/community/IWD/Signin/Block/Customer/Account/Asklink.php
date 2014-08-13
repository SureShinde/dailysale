<?php
class IWD_Signin_Block_Customer_Account_Asklink extends Mage_Customer_Block_Form_Login{

    public function getAskLinkPostActionUrl(){
        return $this->helper('signin/paypal')->getAskLinkPostActionUrl();
    }
}
