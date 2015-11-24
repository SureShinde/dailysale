<?php

class Unirgy_Rma_Model_Rma extends Mage_Sales_Model_Abstract
{
    const XML_PATH_EMAIL_TEMPLATE               = 'sales_email/rma/template';
    const XML_PATH_EMAIL_IDENTITY               = 'sales_email/rma/identity';
    const XML_PATH_EMAIL_COPY_TO                = 'sales_email/rma/copy_to';
    const XML_PATH_EMAIL_COPY_METHOD            = 'sales_email/rma/copy_method';
    const XML_PATH_EMAIL_ENABLED                = 'sales_email/rma/enabled';

    const XML_PATH_UPDATE_EMAIL_TEMPLATE        = 'sales_email/rma_comment/template';
    const XML_PATH_UPDATE_EMAIL_IDENTITY        = 'sales_email/rma_comment/identity';
    const XML_PATH_UPDATE_EMAIL_COPY_TO         = 'sales_email/rma_comment/copy_to';
    const XML_PATH_UPDATE_EMAIL_COPY_METHOD     = 'sales_email/rma_comment/copy_method';
    const XML_PATH_UPDATE_EMAIL_ENABLED         = 'sales_email/rma_comment/enabled';

    protected $_items;
    protected $_order;
    protected $_comments;
    
    protected $_eventPrefix = 'urma_rma';
    protected $_eventObject = 'rma';
    
    protected $_commentsChanged = false;

    protected function _construct()
    {
        $this->_init('urma/rma');
    }

    public function loadByIncrementId($incrementId)
    {
        $ids = $this->getCollection()
            ->addAttributeToFilter('increment_id', $incrementId)
            ->getAllIds();

        if (!empty($ids)) {
            reset($ids);
            $this->load(current($ids));
        }
        return $this;
    }

    public function setOrder(Mage_Sales_Model_Order $order)
    {
        $this->_order = $order;
        $this->setOrderId($order->getId())
            ->setStoreId($order->getStoreId());
        return $this;
    }

    public function getProtectCode()
    {
        return (string)$this->getOrder()->getProtectCode();
    }

    public function getOrder()
    {
        if (!$this->_order instanceof Mage_Sales_Model_Order) {
            $this->_order = Mage::getModel('sales/order')->load($this->getOrderId());
        }
        return $this->_order;
    }

    public function getBillingAddress()
    {
        return $this->getOrder()->getBillingAddress();
    }

    public function getShippingAddress()
    {
        return $this->getOrder()->getShippingAddress();
    }

    public function register()
    {
        if ($this->getId()) {
            Mage::throwException(
                Mage::helper('udropship')->__('Cannot register existing rma')
            );
        }

        $totalQty = 0;
        foreach ($this->getAllItems() as $item) {
            if ($item->getQty()>0) {
                $item->register();
                if (!$item->getOrderItem()->isDummy(true)) {
                    $totalQty+= $item->getQty();
                }
            }
            else {
                $item->isDeleted(true);
            }
        }
        $this->setTotalQty($totalQty);

        return $this;
    }
    
    public function getItemsCollection()
    {
        if (empty($this->_items)) {
            $this->_items = Mage::getResourceModel('urma/rma_item_collection')
                ->setRmaFilter($this->getId());

            if ($this->getId()) {
                foreach ($this->_items as $item) {
                    $item->setRma($this);
                }
            }
        }
        return $this->_items;
    }

    public function getAllItems()
    {
        $items = array();
        foreach ($this->getItemsCollection() as $item) {
            if (!$item->isDeleted()) {
                $items[] =  $item;
            }
        }
        return $items;
    }

    public function getItemById($itemId)
    {
        foreach ($this->getItemsCollection() as $item) {
            if ($item->getId()==$itemId) {
                return $item;
            }
        }
        return false;
    }

    public function addItem(Unirgy_Rma_Model_Rma_Item $item)
    {
        $item->setRma($this)
            ->setParentId($this->getId())
            ->setStoreId($this->getStoreId());
        if (!$item->getId()) {
            $this->getItemsCollection()->addItem($item);
        }
        return $this;
    }
    
    public function addComment($comment, $notify=false, $visibleOnFront=false, $notifyVendor=false, $visibleToVendor=true)
    {
        $this->_commentsChanged = true;
        if (!($comment instanceof Unirgy_Rma_Model_Rma_Comment)) {
            $comment = Mage::getModel('urma/rma_comment')
                ->setComment($comment)
                ->setIsCustomerNotified($notify)
                ->setIsVisibleOnFront($visibleOnFront)
                ->setIsVendorNotified($notifyVendor)
                ->setIsVisibleToVendor($visibleToVendor);
        }
        $comment->setRma($this)
            ->setParentId($this->getId())
            ->setStoreId($this->getStoreId());
        if (!$comment->getId()) {
            $this->getCommentsCollection()->addItem($comment);
        }
        return $this;
    }

    public function saveComments()
    {
        if ($this->_commentsChanged) {
            foreach($this->getCommentsCollection() as $comment) {
                if (!$comment->getRmaStatus()) {
                    $comment->setRmaStatus($this->getRmaStatus());
                }
                $comment->save();
            }
        }
        return $this;
    }

    protected $_vendorComments;
    public function getVendorCommentsCollection($reload=false)
    {
        if (is_null($this->_vendorComments) || $reload) {
            $this->_vendorComments = Mage::getResourceModel('urma/rma_comment_collection')
                ->setRmaFilter($this->getId())
                ->addFieldToFilter('is_visible_to_vendor',1)
                ->setCreatedAtOrder();

            $this->_vendorComments->load();

            if ($this->getId()) {
                foreach ($this->_vendorComments as $comment) {
                    $comment->setRma($this);
                }
            }
        }
        return $this->_vendorComments;
    }

    public function getCommentsCollection($reload=false)
    {
        if (is_null($this->_comments) || $reload) {
            $this->_comments = Mage::getResourceModel('urma/rma_comment_collection')
                ->setRmaFilter($this->getId())
                ->setCreatedAtOrder();

            $this->_comments->load();

            if ($this->getId()) {
                foreach ($this->_comments as $comment) {
                    $comment->setRma($this);
                }
            }
        }
        return $this->_comments;
    }
    
    protected function _beforeSave()
    {
        if ((!$this->getId() || null !== $this->_items) && !count($this->getAllItems())) {
            Mage::throwException(
                Mage::helper('udropship')->__('Cannot create an empty rma.')
            );
        }

        if (!$this->getOrderId() && $this->getOrder()) {
            $this->setOrderId($this->getOrder()->getId());
            $this->setShippingAddressId($this->getOrder()->getShippingAddress()->getId());
        }

        return parent::_beforeSave();
    }

    protected function _beforeDelete()
    {
        $this->_protectFromNonAdmin();
        return parent::_beforeDelete();
    }

    protected $_tracks;
    public function getTracksCollection()
    {
        if (empty($this->_tracks)) {
            $this->_tracks = Mage::getResourceModel('urma/rma_track_collection')
                ->setRmaFilter($this->getId());

            if ($this->getId()) {
                foreach ($this->_tracks as $track) {
                    $track->setRma($this);
                }
            }
        }
        return $this->_tracks;
    }

    public function getAllTracks()
    {
        $tracks = array();
        foreach ($this->getTracksCollection() as $track) {
            if (!$track->isDeleted()) {
                $tracks[] =  $track;
            }
        }
        return $tracks;
    }

    public function getTrackById($trackId)
    {
        foreach ($this->getTracksCollection() as $track) {
            if ($track->getId()==$trackId) {
                return $track;
            }
        }
        return false;
    }

    public function addTrack(Unirgy_Rma_Model_Rma_Track $track)
    {
        $track->setRma($this)
            ->setParentId($this->getId())
            ->setStoreId($this->getStoreId());
        if (!$track->getId()) {
            $this->getTracksCollection()->addItem($track);
        }
        return $this;
    }
    
    protected function _afterSave()
    {
        if (null !== $this->_items) {
            foreach ($this->_items as $item) {
                $item->save();
            }
        }

        if (null !== $this->_comments) {
            foreach($this->_comments as $comment) {
                if (!$comment->getRmaStatus()) {
                    $comment->setRmaStatus($this->getRmaStatus());
                }
                $comment->save();
            }
        }

        if (null !== $this->_tracks) {
            foreach($this->_tracks as $track) {
                $track->save();
            }
        }

        return parent::_afterSave();
    }
    
    public function getStore()
    {
        return $this->getOrder()->getStore();
    }

    public function sendUpdateEmail($notifyCustomer = true, $comment='')
    {
        $order = $this->getOrder();
        $storeId = $order->getStore()->getId();
        if (!Mage::getStoreConfigFlag(self::XML_PATH_UPDATE_EMAIL_ENABLED, $storeId)) {
            return $this;
        }

        $hlp = Mage::helper('udropship');

        $order  = $this->getOrder();

        $copyTo = $this->_getEmails(self::XML_PATH_UPDATE_EMAIL_COPY_TO);
        $copyMethod = Mage::getStoreConfig(self::XML_PATH_UPDATE_EMAIL_COPY_METHOD, $this->getStoreId());

        if (!$notifyCustomer && !$copyTo) {
            return $this;
        }

        $paymentBlock   = Mage::helper('payment')->getInfoBlock($order->getPayment())
            ->setIsSecureMode(true);
        $paymentBlock->getMethod()->setStore($order->getStore()->getId());

        $mailTemplate = Mage::getModel('udropship/email');

        $template = Mage::getStoreConfig(self::XML_PATH_UPDATE_EMAIL_TEMPLATE, $order->getStoreId());
        if ($order->getCustomerIsGuest()) {
            $customerName = $order->getBillingAddress()->getName();
        } else {
            $customerName = $order->getCustomerName();
        }

        $data = array();
        if ($notifyCustomer) {
            $sendTo[] = array(
                'name'  => $customerName,
                'email' => $order->getCustomerEmail()
            );
            if ($copyTo && $copyMethod == 'bcc') {
                foreach ($copyTo as $email) {
                    $data['_BCC'][] = $email;
                }
            }

        }

        if ($copyTo && ($copyMethod == 'copy' || !$notifyCustomer)) {
            foreach ($copyTo as $email) {
                $sendTo[] = array(
                    'name'  => null,
                    'email' => $email
                );
            }
        }

        if ($this->hasPrintableTracks()) {
            try {
                $lblBatch = Mage::getModel('udropship/label_batch')
                    ->setForcedFilename('rma_label_'.$this->getIncrementId())
                    ->setVendor($this->getVendor())
                    ->renderRmas(array($this));
                $labelModel = Mage::helper('udropship')->getLabelTypeInstance($lblBatch->getLabelType());
                $labelModel->setBatch($lblBatch);
                $data['_ATTACHMENTS'][] = $labelModel->renderBatchContent($lblBatch);
            } catch (Exception $e) {}
        }

        $hlp->setDesignStore($this->getOrder()->getStore());

        $data = array_merge($data, array(
            'order'       => $order,
            'rma'         => $this,
            'comment'     => $comment,
            'billing'     => $order->getBillingAddress(),
            'payment_html'=> $paymentBlock->toHtml(),
            'show_order_info'=>!Mage::getStoreConfigFlag('urma/general/customer_hide_order_info'),
            'show_receiver' => $this->isReceiverVisible(),
            'show_notes'=>$this->getStatusCustomerNotes()||($this->isAllowedResolutionNotes()&&$this->getResolutionNotes()),
            'show_both_notes'=>$this->getStatusCustomerNotes()&&($this->isAllowedResolutionNotes()&&$this->getResolutionNotes()),
            'customer_notes'=>$this->getStatusCustomerNotes(),
            'show_resolution_notes'=>$this->isAllowedResolutionNotes()&&$this->getResolutionNotes(),
            'resolution_notes'=>$this->getResolutionNotes()
        ));

        foreach ($sendTo as $recipient) {
            $mailTemplate
                ->sendTransactional(
                    $template,
                    Mage::getStoreConfig(self::XML_PATH_UPDATE_EMAIL_IDENTITY, $order->getStoreId()),
                    $recipient['email'],
                    $recipient['name'],
                    $data
                );
        }

        $hlp->setDesignStore();

        Mage::helper('udropship')->processQueue();

        return $this;
    }

    public function sendEmail($notifyCustomer=true, $comment='')
    {
        $order = $this->getOrder();
        $storeId = $order->getStore()->getId();
        if (!Mage::getStoreConfigFlag(self::XML_PATH_EMAIL_ENABLED, $storeId)) {
            return $this;
        }

        $translate = Mage::getSingleton('core/translate');
        /* @var $translate Mage_Core_Model_Translate */
        $translate->setTranslateInline(false);

        $order  = $this->getOrder();
        $copyTo = $this->_getEmails(self::XML_PATH_EMAIL_COPY_TO);
        $copyMethod = Mage::getStoreConfig(self::XML_PATH_EMAIL_COPY_METHOD, $this->getStoreId());

        if (!$notifyCustomer && !$copyTo) {
            return $this;
        }

        $currentDesign = Mage::getDesign()->setAllGetOld(array(
            'package' => Mage::getStoreConfig('design/package/name', $this->getStoreId()),
            'store' => $this->getStoreId()
        ));

        $paymentBlock   = Mage::helper('payment')->getInfoBlock($order->getPayment())
            ->setIsSecureMode(true);
        $paymentBlock->getMethod()->setStore($order->getStore()->getId());

        $mailTemplate = Mage::getModel('udropship/email');

        $template = Mage::getStoreConfig(self::XML_PATH_EMAIL_TEMPLATE, $order->getStoreId());
        if ($order->getCustomerIsGuest()) {
            $customerName = $order->getBillingAddress()->getName();
        } else {
            $customerName = $order->getCustomerName();
        }

        if ($notifyCustomer) {
            $sendTo[] = array(
                'name'  => $customerName,
                'email' => $order->getCustomerEmail()
            );
            if ($copyTo && $copyMethod == 'bcc') {
                foreach ($copyTo as $email) {
                    $mailTemplate->addBcc($email);
                }
            }

        }

        if ($copyTo && ($copyMethod == 'copy' || !$notifyCustomer)) {
            foreach ($copyTo as $email) {
                $sendTo[] = array(
                    'name'  => null,
                    'email' => $email
                );
            }
        }

        $data = array();
        if ($this->hasPrintableTracks()) {
            try {
                $lblBatch = Mage::getModel('udropship/label_batch')
                    ->setForcedFilename('rma_label_'.$this->getIncrementId())
                    ->setVendor($this->getVendor())
                    ->renderRmas(array($this));
                $labelModel = Mage::helper('udropship')->getLabelTypeInstance($lblBatch->getLabelType());
                $labelModel->setBatch($lblBatch);
                $data['_ATTACHMENTS'][] = $labelModel->renderBatchContent($lblBatch);
            } catch (Exception $e) {}
        }

        $data = array_merge($data, array(
            'order'       => $order,
            'rma'         => $this,
            'comment'     => $comment,
            'billing'     => $order->getBillingAddress(),
            'payment_html'=> $paymentBlock->toHtml(),
            'show_order_info'=>!Mage::getStoreConfigFlag('urma/general/customer_new_hide_order_info'),
            'show_receiver' => $this->isReceiverVisible(),
            'show_notes'=>$this->getStatusCustomerNotes()||($this->isAllowedResolutionNotes()&&$this->getResolutionNotes()),
            'show_both_notes'=>$this->getStatusCustomerNotes()&&($this->isAllowedResolutionNotes()&&$this->getResolutionNotes()),
            'customer_notes'=>$this->getStatusCustomerNotes(),
            'show_resolution_notes'=>$this->isAllowedResolutionNotes()&&$this->getResolutionNotes(),
            'resolution_notes'=>$this->getResolutionNotes()
        ));

        foreach ($sendTo as $recipient) {
            $mailTemplate->setDesignConfig(array('area'=>'frontend', 'store'=>$order->getStoreId()))
                ->sendTransactional(
                    $template,
                    Mage::getStoreConfig(self::XML_PATH_EMAIL_IDENTITY, $order->getStoreId()),
                    $recipient['email'],
                    $recipient['name'],
                    $data
                );
        }

        $translate->setTranslateInline(true);

        Mage::getDesign()->setAllGetOld($currentDesign);

        Mage::helper('udropship')->processQueue();

        return $this;
    }

    protected function _getEmails($configPath)
    {
        $data = Mage::getStoreConfig($configPath, $this->getStoreId());
        if (!empty($data)) {
            return explode(',', $data);
        }
        return false;
    }

    public function getRmaStatus()
    {
        $rmaStatus = $this->getData('rma_status');
        if ($rmaStatus === null) {
            $rmaStatus = 'pending';
        }
        return $rmaStatus;
    }
    public function getRmaStatusName()
    {
        return Mage::helper('urma')->getRmaStatusName($this->getRmaStatus());
    }
    public function getRmaReasonName()
    {
        return Mage::helper('urma')->getReasonTitle($this->getRmaReason());
    }
    public function getStatusLabel()
    {
        return Mage::helper('udropship')->__($this->getRmaStatus());
    }
    public function getStatusCustomerNotes()
    {
        return Mage::helper('urma')->getStatusCustomerNotes($this->getRmaStatus());
    }
    public function isAllowedResolutionNotes()
    {
        $allowed = Mage::helper('urma')->getAllowedResolutionNotesStatuses();
        return array_key_exists($this->getRmaStatus(), $allowed);
    }
    public function isReceiverVisible()
    {
        $allowed = Mage::helper('urma')->getReceiverVisibleStatuses();
        return array_key_exists($this->getRmaStatus(), $allowed);
    }

    public function getVendorName()
    {
        return $this->getVendor()->getVendorName();
    }

    public function getVendor()
    {
        return Mage::helper('udropship')->getVendor($this->getUdropshipVendor());
    }

    public function getRemainingWeight()
    {
        $weight = 0;
        foreach ($this->getAllItems() as $item) {
            if ($item->getOrderItem()->isDummy(true)) continue;
            $weight += $item->getWeight()*$item->getQty();
        }
        return $weight;
    }

    public function getRemainingValue()
    {
        $value = 0;
        foreach ($this->getAllItems() as $item) {
            if ($item->getOrderItem()->isDummy(true)) continue;
            $value += $item->getPrice()*$item->getQty();
        }
        return $value;
    }

    public function hasPrintableTracks()
    {
        $has = false;
        foreach ($this->getAllTracks() as $track) {
            if ($track->getLabelImage()) {
                $has = true;
                break;
            }
        }
        return $has;
    }

}
