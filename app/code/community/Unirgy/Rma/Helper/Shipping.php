<?php

class Unirgy_Rma_Helper_Shipping extends Mage_Shipping_Helper_Data
{
    protected $_allowedHashKeys = array('ship_id', 'order_id', 'track_id', 'ustockpo_track_id', 'ustockpo_id', 'rma_track_id', 'rma_id');
    public function getTrackingPopupUrlBySalesModel($model)
    {
        if ($model instanceof Unirgy_Rma_Model_Rma_Track) {
            return $this->_getMyTrackingUrl('rma_track_id', $model);
        } elseif ($model instanceof Unirgy_Rma_Model_Rma) {
            return $this->_getMyTrackingUrl('rma_id', $model);
        } elseif ($model instanceof Unirgy_DropshipStockPo_Model_Po_Track) {
            return $this->_getMyTrackingUrl('ustockpo_track_id', $model);
        } elseif ($model instanceof Unirgy_DropshipStockPo_Model_Po) {
            return $this->_getMyTrackingUrl('ustockpo_id', $model);
        } else {
            return parent::getTrackingPopupUrlBySalesModel($model);
        }
    }
    protected function _getMyTrackingUrl($key, $model, $method = 'getId')
    {
         if (empty($model)) {
             $param = array($key => ''); // @deprecated after 1.4.0.0-alpha3
         } else if (!is_object($model)) {
             $param = array($key => $model); // @deprecated after 1.4.0.0-alpha3
         } else {
             $param = array(
                 'hash' => Mage::helper('core')->urlEncode("{$key}:{$model->$method()}:{$model->getProtectCode()}")
             );
         }
         $storeId = is_object($model) ? $model->getStoreId() : null;
         $storeModel = Mage::app()->getStore($storeId);
         return $storeModel->getUrl('urma/tracking/popup', $param);
    }
}
