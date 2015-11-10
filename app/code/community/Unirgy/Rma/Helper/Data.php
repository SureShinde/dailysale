<?php

class Unirgy_Rma_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function addVendorSkus($rma)
    {
        $orderItemIds = array();
        foreach ($rma->getAllItems() as $rItem) {
            $orderItemIds[] = $rItem->getOrderItemId();
        }
        $res = Mage::getSingleton('core/resource');
        $read = $res->getConnection('catalog_read');
        if (Mage::helper('udropship')->isUdpoActive()) {
            $table = $res->getTableName('udpo/po_item');
        } else {
            $table = $res->getTableName('sales/shipment_item');
        }
        $select = $read->select()
            ->from(array('item'=>$table), array('order_item_id', 'vendor_sku', 'vendor_simple_sku'))
            ->where('order_item_id in (?)', $orderItemIds);
        $rows = $read->fetchAll($select);
        foreach ($rows as $row) {
            foreach ($rma->getAllItems() as $rItem) {
                if ($rItem->getOrderItemId()==$row['order_item_id']) {
                    $rItem->setVendorSku($row['vendor_sku']);
                    $rItem->setVendorSimpleSku($row['vendor_simple_sku']);
                    break;
                }
            }
        }
        return $this;
    }
    public function getOrderItemQtyToUrma($item, $skipDummy=false)
    {
        if ($item->isDummy(true) && !$skipDummy) {
            return 0;
        }
        $qty = $item->getQtyShipped()
            - $item->getQtyUrma();
        return max($qty, 0);
    }
    public function getRmaStatusName($status)
    {
        $statuses = Mage::getSingleton('urma/source')->setPath('rma_status')->toOptionHash();
        return isset($statuses[$status]) ? $statuses[$status] : (in_array($status, $statuses) ? $status : 'Unknown');
    }
    public function getVendorRmaStatuses()
    {
        return Mage::getSingleton('urma/source')->setPath('rma_status')->toOptionHash();
    }
    public function processRmaStatusSave($rma, $status, $save, $vendor=false, $comment='', $isVendorNotified=null, $isVisibleToVendor=null)
    {
        $isVendorNotified  = is_null($isVendorNotified) ? false : $isVendorNotified;
        $isVisibleToVendor = is_null($isVisibleToVendor) ? true : $isVisibleToVendor;
        if ($rma->getRmaStatus() != $status) {
            $oldStatus = $rma->getRmaStatus();
            Mage::dispatchEvent(
                'udropship_rma_status_save_before',
                array('rma'=>$rma, 'old_status'=>$oldStatus, 'new_status'=>$status)
            );
            $rma->setRmaStatus($status);
            $_comment = '';
            if ($vendor) {
                $_comment = sprintf("[%s changed RMA status from '%s' to '%s']",
                    $vendor->getVendorName(),
                    $this->getRmaStatusName($oldStatus),
                    $this->getRmaStatusName($status)
                );
            } else {
                $_comment = sprintf("[RMA status changed from '%s' to '%s']",
                    $this->getRmaStatusName($oldStatus),
                    $this->getRmaStatusName($status)
                );
            }
            if (!empty($comment)) {
                $_comment = $comment."\n\n".$_comment;
            }
            $rma->addComment($_comment, false, false, $isVendorNotified, $isVisibleToVendor);
            $rma->getResource()->saveAttribute($rma, 'rma_status');
            $rma->saveComments();
            Mage::dispatchEvent(
                'udropship_rma_status_save_after',
                array('rma'=>$rma, 'old_status'=>$oldStatus, 'new_status'=>$status)
            );
        }
        return $this;
    }
    public function initOrderRmasCollection($order, $forceReload=false)
    {
        if (!$order->hasRmasCollection() || $forceReload) {
            $rmasCollection = Mage::getResourceModel('urma/rma_collection')
                ->setOrderFilter($order);
            $order->setRmasCollection($rmasCollection);

            if ($order->getId()) {
                foreach ($rmasCollection as $rma) {
                    $rma->setOrder($order);
                }
            }
            $order->setHasUrmas(count($rmasCollection));
        }
        return $this;
    }

    public function hasRMA($order)
    {
        $rHlp = Mage::getResourceSingleton('udropship/helper');
        $conn = $rHlp->getReadConnection();
        $checkSql = $conn->select()->from($rHlp->getTable('urma/rma'))->columns(array('count(*)'))->where('order_id=?', $order->getId());
        $res = $conn->fetchOne($checkSql);
        return $res;
    }
    public function canRMA($order)
    {
        $hasShipments = $order->hasShipments();
        $hasItemToRma = false;
        foreach ($order->getAllItems() as $orderItem) {
            if ($this->getOrderItemQtyToUrma($orderItem)) {
                $hasItemToRma = true;
                break;
            }
        }
        return $hasShipments && $hasItemToRma;
    }

    public function getRMAViewUrl($order)
    {
        return Mage::getUrl('sales/order/rma', array('order_id' => $order->getId()));;
    }

    public function getRMAUrl($order)
    {
        return Mage::getUrl('sales/order/newRma', array('order_id' => $order->getId()));;
    }

    public function beforeRmaLabel($vendor, $rma)
    {
        Mage::helper('urma/protected')->beforeRmaLabel($vendor, $rma);
        return $this;
    }

    public function afterRmaLabel($vendor, $rma)
    {
        Mage::helper('urma/protected')->afterRmaLabel($vendor, $rma);
        return $this;
    }

    protected $_vendorRmaCollection;

    public function getVendorRmaCollection()
    {
        if (!$this->_vendorRmaCollection) {
            $vendorId = Mage::getSingleton('udropship/session')->getVendorId();
            $vendor = Mage::helper('udropship')->getVendor($vendorId);
            $collection = Mage::getModel('urma/rma')->getCollection();
            $orderTableQted = $collection->getResource()->getReadConnection()->quoteIdentifier('sales/order');
            $collection->addFilterToMap('ordertbl_increment_id', "$orderTableQted.increment_id");
            $collection->addFilterToMap('ordertbl_created_at', "$orderTableQted.created_at");
            $collection->join('sales/order', "$orderTableQted.entity_id=main_table.order_id", array(
                'order_increment_id' => 'increment_id',
                'order_created_at' => 'created_at',
                'shipping_method',
            ));

            $collection->addAttributeToFilter('main_table.udropship_vendor', $vendorId);

            $r = Mage::app()->getRequest();

            if (($v = $r->getParam('filter_order_id_from'))) {
                $collection->addAttributeToFilter("ordertbl_increment_id", array('gteq'=>$v));
            }
            if (($v = $r->getParam('filter_order_id_to'))) {
                $collection->addAttributeToFilter("ordertbl_increment_id", array('lteq'=>$v));
            }

            if (($v = $r->getParam('filter_order_date_from'))) {
                $_filterDate = Mage::app()->getLocale()->date();
                $_filterDate->set($v, Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT));
                $_filterDate->setTimezone(Mage_Core_Model_Locale::DEFAULT_TIMEZONE);
                $collection->addAttributeToFilter("ordertbl_created_at", array('gteq'=>$_filterDate->toString(Varien_Date::DATETIME_INTERNAL_FORMAT)));
            }
            if (($v = $r->getParam('filter_order_date_to'))) {
                $_filterDate = Mage::app()->getLocale()->date();
                $_filterDate->set($v, Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT));
                $_filterDate->addDay(1);
                $_filterDate->setTimezone(Mage_Core_Model_Locale::DEFAULT_TIMEZONE);
                $collection->addAttributeToFilter("ordertbl_created_at", array('lteq'=>$_filterDate->toString(Varien_Date::DATETIME_INTERNAL_FORMAT)));
            }

            if (($v = $r->getParam('filter_rma_id_from'))) {
                $collection->addAttributeToFilter('main_table.increment_id', array('gteq'=>$v));
            }
            if (($v = $r->getParam('filter_rma_id_to'))) {
                $collection->addAttributeToFilter('main_table.increment_id', array('lteq'=>$v));
            }

            if (($v = $r->getParam('filter_rma_date_from'))) {
                $_filterDate = Mage::app()->getLocale()->date();
                $_filterDate->set($v, Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT));
                $_filterDate->setTimezone(Mage_Core_Model_Locale::DEFAULT_TIMEZONE);
                $collection->addAttributeToFilter('main_table.created_at', array('gteq'=>$_filterDate->toString(Varien_Date::DATETIME_INTERNAL_FORMAT)));
            }
            if (($v = $r->getParam('filter_rma_date_to'))) {
                $_filterDate = Mage::app()->getLocale()->date();
                $_filterDate->set($v, Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT));
                $_filterDate->addDay(1);
                $_filterDate->setTimezone(Mage_Core_Model_Locale::DEFAULT_TIMEZONE);
                $collection->addAttributeToFilter('main_table.created_at', array('lteq'=>$_filterDate->toString(Varien_Date::DATETIME_INTERNAL_FORMAT)));
            }

            if (!$r->getParam('apply_filter') && $vendor->getData('vendor_rma_grid_status_filter')) {
                $filterStatuses = $vendor->getData('vendor_rma_grid_status_filter');
                $filterStatuses = array_combine($filterStatuses, array_fill(0, count($filterStatuses), 1));
                $r->setParam('filter_status', $filterStatuses);
            }

            if (($v = $r->getParam('filter_status'))) {
                $collection->addAttributeToFilter('main_table.rma_status', array('in'=>array_keys($v)));
            }
            if (($v = $r->getParam('filter_reason'))) {
                $collection->addAttributeToFilter('main_table.rma_reason', array('in'=>array_keys($v)));
            }

            if (!$r->getParam('sort_by') && $vendor->getData('vendor_rma_grid_sortby')) {
                $r->setParam('sort_by', $vendor->getData('vendor_rma_grid_sortby'));
                $r->setParam('sort_dir', $vendor->getData('vendor_rma_grid_sortdir'));
            }
            if (($v = $r->getParam('sort_by'))) {
                $map = array('order_date'=>'order_created_at', 'rma_date'=>'created_at');
                if (isset($map[$v])) {
                    $v = $map[$v];
                }
                $collection->setOrder($v, $r->getParam('sort_dir'));
            }
            $this->_vendorRmaCollection = $collection;
        }
        return $this->_vendorRmaCollection;
    }

    public function sendVendorComment($urma, $comment, $notify=false, $visibleOnFront=false)
    {
        $store = $urma->getStore();
        $to = $store->getConfig('urma/general/vendor_comments_receiver');
        $subject = $store->getConfig('urma/general/vendor_comments_subject');
        $template = $store->getConfig('urma/general/vendor_comments_template');
        $vendor = $urma->getVendor();
        $ahlp = Mage::getModel('adminhtml/url');

        if ($subject && $template && $vendor->getId()) {
            $toEmail = $store->getConfig('trans_email/ident_'.$to.'/email');
            $toName = $store->getConfig('trans_email/ident_'.$to.'/name');
            $data = array(
                'vendor_name'   => $vendor->getVendorName(),
                'order_id'         => $urma->getOrder()->getIncrementId(),
                'rma_id'         => $urma->getIncrementId(),
                'vendor_url'    => $ahlp->getUrl('adminhtml/udropshipadmin_vendor/edit', array(
                    'id'        => $vendor->getId(),
                    '_store'    => 0
                )),
                'order_url'     => $ahlp->getUrl('adminhtml/sales_order/view', array(
                    'order_id'  => $urma->getOrder()->getId(),
                    '_store'    => 0
                )),
                'rma_url'  => $ahlp->getUrl('adminhtml/urmaadmin_order_rma/view', array(
                    'rma_id'  => $urma->getId(),
                    '_store'    => 0
                )),
                'comment'      => $comment,
            );
            foreach ($data as $k=>$v) {
                $subject = str_replace('{{'.$k.'}}', $v, $subject);
                $template = str_replace('{{'.$k.'}}', $v, $template);
            }

            $mail = Mage::getModel('core/email')
                ->setFromEmail($vendor->getEmail())
                ->setFromName($vendor->getVendorName())
                ->setToEmail($toEmail)
                ->setToName($toName)
                ->setSubject($subject)
                ->setBody($template)
                ->send();
            //mail('"'.$toName.'" <'.$toEmail.'>', $subject, $template, 'From: "'.$vendor->getVendorName().'" <'.$vendor->getEmail().'>');
        }

        $urma->addComment(Mage::helper('udropship')->__($vendor->getVendorName().': '.$comment), $notify, $visibleOnFront, true, true)->saveComments();

        if ($notify) {
            $urma->sendUpdateEmail($notify, Mage::helper('udropship')->__($vendor->getVendorName().': '.$comment));
        }

        return $this;
    }

    public function sendNewRmaNotificationEmail($rma, $comment='')
    {
        $order = $rma->getOrder();
        $store = $order->getStore();

        $vendor = $rma->getVendor();

        $hlp = Mage::helper('udropship');
        $data = array();

        $hlp->assignVendorSkus($rma);
        $hlp->setDesignStore($store);
        $shippingAddress = $order->getShippingAddress();
        if (!$shippingAddress) {
            $shippingAddress = $order->getBillingAddress();
        }
        $data += array(
            'rma'              => $rma,
            'order'           => $order,
            'vendor'          => $vendor,
            'comment'         => $comment,
            'is_admin_comment'=> $comment&&$rma->getIsAdmin(),
            'is_customer_comment'=> $comment&&$rma->getIsCustomer(),
            'store_name'      => $store->getName(),
            'vendor_name'     => $vendor->getVendorName(),
            'rma_id'           => $rma->getIncrementId(),
            'order_id'        => $order->getIncrementId(),
            'customer_info'   => Mage::helper('udropship')->formatCustomerAddress($shippingAddress, 'html', $vendor),
            'rma_url'          => Mage::getUrl('urma/vendor/', array('_query'=>'filter_rma_id_from='.$rma->getIncrementId().'&filter_rma_id_to='.$rma->getIncrementId())),
        );

        $template = $store->getConfig('urma/general/new_rma_vendor_email_template');
        $identity = $store->getConfig('udropship/vendor/vendor_email_identity');

        $data['_BCC'] = $vendor->getNewOrderCcEmails();
        if (($emailField = $store->getConfig('udropship/vendor/vendor_notification_field'))) {
            $email = $vendor->getData($emailField) ? $vendor->getData($emailField) : $vendor->getEmail();
        } else {
            $email = $vendor->getEmail();
        }
        Mage::getModel('udropship/email')->sendTransactional($template, $identity, $email, $vendor->getVendorName(), $data);

        $hlp->setDesignStore();
        $hlp->unassignVendorSkus($rma);
    }


    public function sendRmaCommentNotificationEmail($rma, $comment)
    {
        $order = $rma->getOrder();
        $store = $order->getStore();

        $vendor = $rma->getVendor();

        $hlp = Mage::helper('udropship');
        $data = array();

        $hlp->setDesignStore($store);

        $data += array(
            'rma'              => $rma,
            'order'           => $order,
            'vendor'          => $vendor,
            'comment'         => $comment,
            'store_name'      => $store->getName(),
            'vendor_name'     => $vendor->getVendorName(),
            'rma_id'           => $rma->getIncrementId(),
            'rma_status'       => $rma->getRmaStatusName(),
            'order_id'        => $order->getIncrementId(),
            'rma_url'          => Mage::getUrl('urma/vendor/', array('_query'=>'filter_rma_id_from='.$rma->getIncrementId().'&filter_rma_id_to='.$rma->getIncrementId())),
        );

        $template = $store->getConfig('urma/general/rma_comment_vendor_email_template');
        $identity = $store->getConfig('udropship/vendor/vendor_email_identity');

        $data['_BCC'] = $vendor->getNewOrderCcEmails();
        if (($emailField = $store->getConfig('udropship/vendor/vendor_notification_field'))) {
            $email = $vendor->getData($emailField) ? $vendor->getData($emailField) : $vendor->getEmail();
        } else {
            $email = $vendor->getEmail();
        }
        Mage::getModel('udropship/email')->sendTransactional($template, $identity, $email, $vendor->getVendorName(), $data);

        $hlp->setDesignStore();
    }

    public function getOptionsDefinition($cfgField, $store=null)
    {
        $optDef = Mage::getStoreConfig('urma/general/'.$cfgField, $store);
        $optDef = Mage::helper('udropship')->unserialize($optDef);
        return $optDef;
    }

    public function getOptionsDefinitionTitles($cfgField, $filterFlag=null, $filterValue=null, $store=null)
    {
        $optDef = $this->getOptionsDefinition($cfgField, $store);
        $_optDef = array();
        if (is_array($optDef)) {
            foreach ($optDef as $optd) {
                if (!$filterFlag || @$optd[$filterFlag]==$filterValue) {
                    $_optDef[@$optd['code']] = Mage::helper('udropship')->__(@$optd['title']);
                }
            }
        }
        return array_unique(array_filter($_optDef));
    }

    public function getOptionsDefinitionTitle($cfgField, $code, $store=null)
    {
        $title = $code;
        $optDef = $this->getOptionsDefinition($cfgField, $store);
        if (is_array($optDef)) {
            foreach ($optDef as $optd) {
                if ($code == @$optd['code']) {
                    $title = Mage::helper('udropship')->__($optd['title']);
                    break;
                }
            }
        }
        return $title;
    }

    public function getOptionsDefinitionExtra($cfgField, $subField, $code, $store=null)
    {
        $title = $code;
        $optDef = $this->getOptionsDefinition($cfgField, $store);
        if (is_array($optDef)) {
            foreach ($optDef as $optd) {
                if ($code == @$optd['code']) {
                    $title = Mage::helper('udropship')->__(@$optd[$subField]);
                    break;
                }
            }
        }
        return $title;
    }

    public function getReasonTitles()
    {
        return $this->getOptionsDefinitionTitles('reasons');
    }
    public function getReasonTitle($code)
    {
        return $this->getOptionsDefinitionTitle('reasons', $code);
    }
    public function getStatusTitles()
    {
        return $this->getOptionsDefinitionTitles('statuses');
    }
    public function getStatusTitle($code)
    {
        return $this->getOptionsDefinitionTitle('statuses', $code);
    }
    public function getStatusCustomerNotes($code)
    {
        return $this->getOptionsDefinitionExtra('statuses', 'customer_notes', $code);
    }
    public function getAllowedResolutionNotesStatuses()
    {
        return $this->getOptionsDefinitionTitles('statuses', 'allow_resolution_notes', 1);
    }
    public function getAllowedResolutionNotesStatusesIdsJson()
    {
        $allowed = $this->getOptionsDefinitionTitles('statuses', 'allow_resolution_notes', 1);
        return Zend_Json::encode(array_keys($allowed));
    }
    public function getReceiverVisibleStatuses()
    {
        return $this->getOptionsDefinitionTitles('statuses', 'show_receiver', 1);
    }
    public function getItemConditionTitles()
    {
        return $this->getOptionsDefinitionTitles('item_conditions');
    }
    public function getItemConditionTitle($code)
    {
        return $this->getOptionsDefinitionTitle('item_conditions', $code);
    }

}
