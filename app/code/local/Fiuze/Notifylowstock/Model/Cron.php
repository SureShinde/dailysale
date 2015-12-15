<?php

class Fiuze_Notifylowstock_Model_Cron extends Mage_Core_Model_Abstract
{
    /**
     * @return array products
     * @throws Mage_Core_Exception
     */
    public function getNotifyLowStockCategory()
    {
        if (!Mage::registry('_NotifyLowStockCategory')) {
            $helper = Mage::helper('fiuze_notifylowstock');

            $categoryIds = explode(',', $helper->getCategoryId());
            $quantuty = $helper->getQuantity();

            $result = array();
            foreach ($categoryIds as $categoryId) {
                $productsCollection = Mage::getResourceModel('catalog/product_collection')
                    ->joinField(
                        'qty',
                        'cataloginventory/stock_item',
                        'qty',
                        'product_id=entity_id',
                        '{{table}}.stock_id=1',
                        'left'
                    )
                    ->addFieldToFilter('qty', array('lt' => $quantuty))
                    ->addAttributeToSelect('qty')
                    ->addAttributeToSelect('name')
                    //->addAttributeToSelect('status')
                    //->addFieldToFilter('status', array('eq' => '1'))
                    ->addFieldToFilter('type_id', array('neq' => 'configurable'));
                Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($productsCollection);
                $catagory_model = Mage::getModel('catalog/category')->load($categoryId);
                $productsCollection->addCategoryFilter($catagory_model);
                $products = $productsCollection->getItems();

                //add category in product array
                foreach ($products as $product) {
                    if (!array_key_exists($product->getId(), $result)) {
                        $product->setPath($catagory_model->getName());
                        $result[$product->getId()] = $product;
                    } else {
                        $path = $result[$product->getId()]->getPath();
                        $result[$product->getId()]->setPath($path . ',' . $catagory_model->getName());
                    }
                }
            }
            //add custom collection to array
            $customProductsCollection = Mage::getResourceModel('catalog/product_collection')
                ->addFieldToFilter('fiuze_lowstock_flag', '1')
                ->joinField(
                    'qty',
                    'cataloginventory/stock_item',
                    'qty',
                    'product_id=entity_id',
                    '{{table}}.stock_id=1',
                    'left'
                )
                ->addAttributeToSelect('*');

            //add of products by filter
            foreach ($customProductsCollection as $product){
                if(!array_key_exists($product->getId(),$result)){
                    $result[$product->getId()]=$product;
                }
            }
            $customLsArray=array();
            foreach($result as $product){
                if($product->getFiuzeLowstockFlag()==1){
                    $ls = $product->getFiuzeLowstockQty();
                }else{
                    $ls = Mage::helper('fiuze_notifylowstock')->getQuantity();
                }
                $fiuze_notif = Mage::getResourceModel('catalog/product')->getAttributeRawValue(
                    $product->getId(),
                    'fiuze_lowstock_notif',
                    Mage::app()->getStore()->getId()
                );

                if((int)$product->getQty()<$ls AND $fiuze_notif == 0){
                    $customLsArray[]=$product;
                    $product->setFiuzeLowstockNotif('1')->save();
                }
            }
            asort($customLsArray);
            Mage::register('_NotifyLowStockCategory', $customLsArray);
        }
        return Mage::registry('_NotifyLowStockCategory');
    }

    /**
     * @param array $products
     */
    public function sendEmail()
    {
        if (Mage::helper('fiuze_notifylowstock')->getModuleEnabled()) {
            $products = $this->getNotifyLowStockCategory();
            $mailList = Mage::helper('fiuze_notifylowstock')->getEmailArray();
            if(count($products)>0){
                $this->_sendEmail($mailList,  $products);
            }
        }
    }

    protected function _sendEmail($to, $products)
    {
        $io = new Varien_Io_File();
        $path = Mage::getBaseDir('var') . DS . 'emailLowstock';
        $fileName = Mage::getModel('core/date')->gmtTimestamp().'_lowstock';
        $file = $path . DS . $fileName . '.csv';
        $io->setAllowCreateFolders(true);
        $io->open(array('path' => $path));
        $io->streamOpen($file, 'w+');
        $io->streamLock(true);
        //create header for csv
        $io->streamWriteCsv(array('Name of product','Product ID','Qty of product'));
        try{
            //add product to csv
            foreach($products as $product){
                $io->streamWriteCsv(array($product->getName(),$product->getId(),$product->getQty()));
            }
        }catch (Exception $ex){
            Mage::logException($ex);
        }

        $io->streamUnlock();
        $io->streamClose();

        if(file_exists($file)){
            foreach($to as $email){
                $mail = new Zend_Mail();
                $mail->setBodyHtml("CSV file contain list ", "UTF-8");
                $mail->addTo($email, 'vendor');
                $mail->setSubject("Notify Dailysale low stock");
                $mail->createAttachment(
                    file_get_contents($file),
                    Zend_Mime::TYPE_OCTETSTREAM,
                    Zend_Mime::DISPOSITION_ATTACHMENT,
                    Zend_Mime::ENCODING_BASE64,
                    $fileName . '.csv'
                );
                try{
                    $mail->send();
                } catch(Exception $ex){
                }
            }
            unlink($file);
        }


//        $helper = Mage::helper('fiuze_notifylowstock');
//        Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
//        Mage::getSingleton('core/design_package' )->setStore(Mage::app()->getStore()->getId());
//
//        $translate = Mage::getSingleton('core/translate');
//        $translate->setTranslateInline(false);
//
//        $storeId = Mage::app()->getStore()->getId();
//        try {
//            $emailTemplate = Mage::getModel('core/email_template')
//                ->setDesignConfig(array('area' => 'frontend', 'store' => $storeId));
//            $emailTemplate->sendTransactional(
//                $helper->getTemplateEmail(),
//                $helper->getIdentityEmail(),
//                $to,
//                $name,
//                array('object' => $products)
//            );
//        } catch (Exception $ex) {
//            Mage::logException($ex);
//        }
//
//        if (!$emailTemplate->getSentSuccess()){
//            Mage::log('Sending mail: Failed', null, 'notifylowstock.log');
//        }
//
//        $translate->setTranslateInline(true);
    }

}