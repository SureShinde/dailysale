<?php
class Fiuze_Aftershipcustomization_Model_Observer {
    public function replaceBlock($data){

        if($data->getEvent()->getBlock()->getTemplate() == 'unirgy/udbatch/vendor/importorders.phtml'){
            $data->getEvent()->getBlock()->setTemplate('unirgy/udbatch/vendor/importorders_fix.phtml');
        }
    }
}