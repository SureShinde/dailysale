<?php

class Unirgy_DropshipMicrosite_Model_AdminObserver extends Mage_Admin_Model_Observer
{
    public function actionPreDispatchAdmin($event)
    {
        parent::actionPreDispatchAdmin($event);
        $session = Mage::getSingleton('admin/session');
        $user = $session->getUser();
        if (!$user || !$user->getId()) {
            if (Mage::getSingleton('core/cookie')->get('udvendor_portal')) {
                Mage::app()->getResponse()->setRedirect(Mage::getUrl('udropship/vendor/login'));
            }
        }
    }
}