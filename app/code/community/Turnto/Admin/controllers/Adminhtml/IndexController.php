<?php
class Turnto_Admin_Adminhtml_IndexController  extends Mage_Core_Controller_Front_Action {
     public function indexAction() {
          echo Mage::getConfig()->getNode()->modules->Turnto_Admin->version;
     }
}
?>
