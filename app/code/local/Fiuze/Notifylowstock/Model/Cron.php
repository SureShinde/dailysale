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

            $category = $helper->getCategoryId();
            $categoryIds = explode(',', $category);
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
            asort($result);
            Mage::register('_NotifyLowStockCategory', $result);
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
            $this->_sendTestEmail($mailList, 'email', $products);
        }
    }


    protected function _sendTestEmail($to, $name, $products)
    {

        $helper = Mage::helper('fiuze_notifylowstock');
        Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
        Mage::getSingleton('core/design_package' )->setStore(Mage::app()->getStore()->getId());

        $translate = Mage::getSingleton('core/translate');
        $translate->setTranslateInline(false);

        $storeId = Mage::app()->getStore()->getId();
        try {
            $emailTemplate = Mage::getModel('core/email_template')
                ->setDesignConfig(array('area' => 'frontend', 'store' => $storeId));
            $emailTemplate->sendTransactional(
                $helper->getTemplateEmail(),
                $helper->getIdentityEmail(),
                $to,
                $name,
                array('object' => $products)
            );
        } catch (Exception $ex) {
            Mage::logException($ex);
        }

        if (!$emailTemplate->getSentSuccess()){
            Mage::log('Sending mail: Failed', null, 'notifylowstock.log');
        }

        $translate->setTranslateInline(true);
    }

}