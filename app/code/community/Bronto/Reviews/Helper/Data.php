<?php

/**
 * @package     Bronto\Reviews
 * @copyright   2011-2013 Bronto Software, Inc.
 */
class Bronto_Reviews_Helper_Data
    extends Bronto_Common_Helper_Data
    implements Bronto_Common_Helper_DataInterface
{

    const XML_PATH_GENERAL_TIME_OF_DAY = 'bronto_reviews/general/time_of_day';
    const XML_PATH_GENERAL_CONCURRENT  = 'bronto_reviews/general/conncurrent_limit';

    const XML_PATH_ENABLED        = 'bronto_reviews/settings/enabled';
    const XML_PATH_STATUS         = 'bronto_reviews/settings/status';
    const XML_PATH_CANCEL_STATUS  = 'bronto_reviews/settings/cancel_status';
    const XML_PATH_URL_SUFFIX     = 'bronto_reviews/settings/url_suffix';
    const XML_PATH_PERIOD         = 'bronto_reviews/settings/period';
    const XML_PATH_MESSAGE        = 'bronto_reviews/settings/message';
    const XML_PATH_EMAIL_IDENTITY = 'bronto_reviews/settings/identity';
    const XML_PATH_SENDER_EMAIL   = 'bronto_reviews/settings/sender_email';
    const XML_PATH_SENDER_NAME    = 'bronto_reviews/settings/sender_name';
    const XML_PATH_REPLY_TO       = 'bronto_reviews/settings/reply_to';
    const XML_PATH_DEFAULT_COUPON = 'bronto_reviews/settings/default_coupon';
    const XML_PATH_DEFAULT_REC    = 'bronto_reviews/settings/default_recommendation';
    const XML_PATH_DEFAULT_SEND_FLAG = 'bronto_reviews/settings/default_send_flags';

    const XML_PATH_POST_ENABLED  = 'bronto_reviews/%s/enabled';
    const XML_PATH_POST_PERIOD   = 'bronto_reviews/%s/period';
    const XML_PATH_POST_MESSAGE  = 'bronto_reviews/%s/message';
    const XML_PATH_POST_TRIGGER  = 'bronto_reviews/%s/status';
    const XML_PATH_POST_CANCEL   = 'bronto_reviews/%s/cancel_status';
    const XML_PATH_POST_COUPON   = 'bronto_reviews/%s/default_coupon';
    const XML_PATH_POST_RECOMEND = 'bronto_reviews/%s/default_recommendation';
    const XML_PATH_POST_FLAGS    = 'bronto_reviews/%s/default_send_flags';
    const XML_PATH_POST_IDENTITY = 'bronto_reviews/%s/identity';
    const XML_PATH_POST_ADJUSTMENT   = 'bronto_reviews/%s/adjustment';
    const XML_PATH_POST_SEND_LIMIT   = 'bronto_reviews/%s/send_limit';
    const XML_PATH_POST_SENDER_EMAIL = 'bronto_reviews/%s/sender_email';
    const XML_PATH_POST_SENDER_NAME  = 'bronto_reviews/%s/sender_name';

    /**
     * Multiplier
     */
    const XML_PATH_POST_MULTIPLIER = 'bronto_reviews/reorder/multipler';

    /**
     * Gets the canonical name for the Bronto Review module
     *
     * @return string
     */
    public function getName()
    {
        return $this->__('Bronto Post-Purchase Emails');
    }

    /**
     * Determine if email can be sent through bronto
     *
     * @param Mage_Core_Model_Email_Template $template
     * @param string|int                     $storeId
     *
     * @return boolean
     */
    public function canSendBronto(Mage_Core_Model_Email_Template $template, $storeId = null)
    {
        return true;
    }

    /**
     * Check if module is enabled
     *
     * @param string $scope
     * @param int    $scopeId
     *
     * @return bool
     */
    public function isEnabled($scope = 'default', $scopeId = 0)
    {
        // Get Enabled Scope
        return (bool)$this->getAdminScopedConfig(self::XML_PATH_ENABLED, $scope, $scopeId);
    }

    /**
     * Gets the active concurrent limit for a scope
     *
     * @param string $scope
     * @param int $scopeId
     * @return int
     */
    public function getConcurrentLimit($scope = 'default', $scopeId = 0)
    {
        return (int)$this->getAdminScopedConfig(self::XML_PATH_GENERAL_CONCURRENT, $scope, $scopeId);
    }

    /**
     * Gets the desired time of day for scheduled emails
     *
     * @param string $scope
     * @param int $scopeId
     * @return int
     */
    public function getDesiredTimeOfDay($scope = 'default', $scopeId = 0)
    {
        return (int)$this->getAdminScopedConfig(self::XML_PATH_GENERAL_TIME_OF_DAY, $scope, $scopeId);
    }

    /**
     * Check if the post type is enabled
     *
     * @param string $type
     * @param string $scope
     * @param int $scopeId
     * @return boolean
     */
    public function isPostEnabled($type, $scope = 'default', $scopeId = 0)
    {
        return (bool)$this->getAdminScopedConfig(sprintf(self::XML_PATH_POST_ENABLED, $type), $scope, $scopeId);
    }

    /**
     * Gets the send period for the post type
     *
     * @param string $type
     * @param string $scope
     * @param int $scopeId
     * @return int
     */
    public function getPostPeriod($type, $scope = 'default', $scopeId = 0)
    {
        return (int)$this->getAdminScopedConfig(sprintf(self::XML_PATH_POST_PERIOD, $type), $scope, $scopeId);
    }

    /**
     * Gets the bronto message id for the post type
     *
     * @param string $type
     * @param string $scope
     * @param int $scopeId
     * @return string
     */
    public function getPostMessage($type, $scope = 'default', $scopeId = 0)
    {
        return $this->getAdminScopedConfig(sprintf(self::XML_PATH_POST_MESSAGE, $type), $scope, $scopeId);
    }

    /**
     * Gets the trigger status for the post purchase message
     *
     * @param string $type
     * @param string $scope
     * @param int $scopeId
     * @return string
     */
    public function getPostTrigger($type, $scope = 'default', $scopeId = 0)
    {
        return $this->getAdminScopedConfig(sprintf(self::XML_PATH_POST_TRIGGER, $type), $scope, $scopeId);
    }

    /**
     * Gets the cancelling status for the post purchase message
     *
     * @param string $type
     * @param string $scope
     * @param int $scopeId
     * @return array
     */
    public function getPostCancel($type, $scope = 'default', $scopeId = 0)
    {
        $statuses = $this->getAdminScopedConfig(sprintf(self::XML_PATH_POST_CANCEL, $type), $scope, $scopeId);
        if (!empty($statuses)) {
            $statuses = explode(',', $statuses);
        } else {
            $statuses = array();
        }
        return $statuses;
    }

    /**
     * Gets the send flags for the post purchase message
     *
     * @param string $type
     * @param string $scope
     * @param int $scopeId
     * @return int
     */
    public function getPostSendFlags($type, $scope = 'default', $scopeId = 0)
    {
        return (int)$this->getAdminScopedConfig(sprintf(self::XML_PATH_POST_FLAGS, $type), $scope, $scopeId);
    }

    /**
     * Gets the coupon pool for the post purchase message
     *
     * @param string $type
     * @param string $scope
     * @param int $scopeId
     * @return int
     */
    public function getPostRule($type, $scope = 'default', $scopeId = 0)
    {
        return $this->getAdminScopedConfig(sprintf(self::XML_PATH_POST_COUPON, $type), $scope, $scopeId);
    }

    /**
     * Gets the recommendation for the post purchase message
     *
     * @param string $type
     * @param string $scope
     * @param int $scopeId
     * @return int
     */
    public function getPostRecommendation($type, $scope = 'default', $scopeId = 0)
    {
        return $this->getAdminScopedConfig(sprintf(self::XML_PATH_POST_RECOMEND, $type), $scope, $scopeId);
    }

    /**
     * Gets the email identity for the post purchase message
     *
     * @param string $type
     * @param string $scope
     * @param int $scopeId
     * @return int
     */
    public function getPostEmailIdentity($type, $scope = 'default', $scopeId = 0)
    {
        return $this->getAdminScopedConfig(sprintf(self::XML_PATH_POST_IDENTITY, $type), $scope, $scopeId);
    }

    /**
     * Gets the custom sender name for the post purchase message
     *
     * @param string $type
     * @param string $scope
     * @param int $scopeId
     * @return int
     */
    public function getPostSenderName($type, $scope = 'default', $scopeId = 0)
    {
        return $this->getAdminScopedConfig(sprintf(self::XML_PATH_POST_SENDER_NAME, $type), $scope, $scopeId);
    }

    /**
     * Gets the custom sender email for the post purchase message
     *
     * @param string $type
     * @param string $scope
     * @param int $scopeId
     * @return int
     */
    public function getPostSenderEmail($type, $scope = 'default', $scopeId = 0)
    {
        return $this->getAdminScopedConfig(sprintf(self::XML_PATH_POST_SENDER_EMAIL, $type), $scope, $scopeId);
    }

    /**
     * Gets the send limit for a post type
     *
     * @param string $type
     * @param string $scope
     * @param int $scopeId
     * @return int
     */
    public function getPostSendLimit($type, $scope = 'default', $scopeId = 0)
    {
        return (int)$this->getAdminScopedConfig(sprintf(self::XML_PATH_POST_SEND_LIMIT, $type), $scope, $scopeId);
    }

    /**
     * Gets the adjustment period for a post type
     *
     * @param string $type
     * @param string $scope
     * @param int $scopeId
     * @return int
     */
    public function getPostAdjustment($type, $scope = 'default', $scopeId = 0)
    {
        return (int)$this->getAdminScopedConfig(sprintf(self::XML_PATH_POST_ADJUSTMENT, $type), $scope, $scopeId);
    }

    /**
     * Disable Module for Specified Scope
     *
     * @param string $scope
     * @param int    $scopeId
     * @param bool   $deleteConfig
     *
     * @return bool
     */
    public function disableModule($scope = 'default', $scopeId = 0, $deleteConfig = false)
    {
        $success = true;
        foreach (array('settings', 'reorder', 'caretip') as $type) {
            $xmlPath = sprintf(self::XML_PATH_POST_ENABLED, $type);
            $success = $success && $this->_disableModule($xmlPath, $scope, $scopeId, $deleteConfig);
        }
        return $success;
    }

    /**
     * Get Order Status at which to send Review Request Emails
     *
     * @param string $scope
     * @param int    $scopeId
     *
     * @return mixed
     */
    public function getReviewSendStatus($scope = 'default', $scopeId = 0)
    {
        return $this->getAdminScopedConfig(self::XML_PATH_STATUS, $scope, $scopeId);
    }

    /**
     * Gets the coupon code selected for this review
     *
     * @param string $scope
     * @param int $scopeId
     * @return string
     */
    public function getDefaultRule($scope = 'default', $scopeId = 0)
    {
        return $this->getAdminScopedConfig(self::XML_PATH_DEFAULT_COUPON, $scope, $scopeId);
    }

    /**
     * Gets the product recommendation selected for this review
     *
     * @param string $scope
     * @param int $scopeId
     * @return string
     */
    public function getDefaultRecommendation($scope = 'default', $scopeId)
    {
        return $this->getAdminScopedConfig(self::XML_PATH_DEFAULT_REC, $scope, $scopeId);
    }

    /**
     * Gets the send flags to be used for this review
     *
     * @param string $scope
     * @param int $scopeId
     * @return string
     */
    public function getDefaultSendFlags($scope = 'default', $scopeId)
    {
        return $this->getAdminScopedConfig(self::XML_PATH_DEFAULT_SEND_FLAG, $scope, $scopeId);
    }

    /**
     * Get Order Status at which to cancel Review Request Emails
     *
     * @param string $scope
     * @param int    $scopeId
     *
     * @return array
     */
    public function getReviewCancelStatus($scope = 'default', $scopeId = 0)
    {
        $status = $this->getAdminScopedConfig(self::XML_PATH_CANCEL_STATUS, $scope, $scopeId);
        if ($status != '') {
            $status = explode(',', $status);
        } else {
            $status = array();
        }

        return $status;
    }

    /**
     * Get suffix to append to product URLs
     *
     * @param string $scope
     * @param int    $scopeId
     *
     * @return mixed
     */
    public function getProductUrlSuffix($scope = 'default', $scopeId = 0)
    {
        return $this->getAdminScopedConfig(self::XML_PATH_URL_SUFFIX, $scope, $scopeId);
    }


    /**
     * Get Period to wait before sending Review Request
     *
     * @param string $scope
     * @param int    $scopeId
     *
     * @return mixed
     */
    public function getReviewSendPeriod($scope = 'default', $scopeId = 0)
    {
        return $this->getAdminScopedConfig(self::XML_PATH_PERIOD, $scope, $scopeId);
    }

    /**
     * Get Bronto Message to use for sending Review Request Email
     *
     * @param string $scope
     * @param int    $scopeId
     *
     * @return mixed
     */
    public function getReviewSendMessage($scope = 'default', $scopeId = 0)
    {
        return $this->getAdminScopedConfig(self::XML_PATH_MESSAGE, $scope, $scopeId);
    }

    /**
     * Get the review url for the product
     *
     * @param $product
     * @return string
     */
    public function getReviewsUrl($product, $storeId = null)
    {
        $url = Mage::getModel('core/url')->setStore($storeId);
        $params = array('id' => $product->getId());
        if ($product->getCategoryId()) {
            $params['category'] = $product->getCategoryId();
        } else {
            $categories = $product->getCategoryIds();
            $categoryId = end($categories);
            $params['category'] = $categoryId;
        }
        return $url->getUrl('review/product/list', $params);
    }

    /**
     * Get Sender Email Address
     *
     * @param string $scope
     * @param int    $scopeId
     *
     * @return mixed
     */
    public function getReviewSenderEmail($scope = 'default', $scopeId = 0)
    {
        return $this->getAdminScopedConfig(self::XML_PATH_SENDER_EMAIL, $scope, $scopeId);
    }

    /**
     * Get email identity
     *
     * @param string $scope
     * @param int $scopeId
     * @return string
     */
    public function getEmailIdentity($scope = 'default', $scopeId = 0)
    {
        return $this->getAdminScopedConfig(self::XML_PATH_EMAIL_IDENTITY, $scope, $scopeId);
    }

    /**
     * Get Sender Name
     *
     * @param string $scope
     * @param int    $scopeId
     *
     * @return mixed
     */
    public function getReviewSenderName($scope = 'default', $scopeId = 0)
    {
        return $this->getAdminScopedConfig(self::XML_PATH_SENDER_NAME, $scope, $scopeId);
    }

    /**
     * Get Reply-To Email Address
     *
     * @param string $scope
     * @param int    $scopeId
     *
     * @return mixed
     */
    public function getReviewReplyTo($scope = 'default', $scopeId = 0)
    {
        return $this->getAdminScopedConfig(self::XML_PATH_REPLY_TO, $scope, $scopeId);
    }

    /**
     * Gets the label for a given post type
     *
     * @param string $postType
     * @return string
     */
    public function getPostLabel($postType)
    {
        switch ($postType) {
        case Bronto_Reviews_Model_Post_Purchase::TYPE_REORDER:
            return $this->__('Reorder Reminder');
        case Bronto_Reviews_Model_Post_Purchase::TYPE_CARETIP:
            return $this->__('Care Tips Notification');
        default:
            return $this->__('Review Request');
        }
    }

    /**
     * Gets the default send period for post
     *
     * @param Bronto_Reviews_Model_Post_Purchase $post
     * @param int $storeId
     * @return int
     */
    public function getDefaultPostPeriod($post, $storeId = 0)
    {
        if (is_null($post->getPeriod())) {
            return $this->getPostPeriod($post->getPostType(), 'store', $storeId);
        }
        return (int)$post->getPeriod();
    }

    /**
     * Gets the default adjustment period for the post
     *
     * @param Bronto_Reviews_Model_Post_Purchase $post
     * @param int $storeId
     * @return int
     */
    public function getDefaultAdjustment($post, $storeId = 0)
    {
        if (is_null($post->getAdjustment())) {
            return $this->getPostAdjustment($post->getPostType(), 'store', $storeId);
        }
        return (int)$post->getAdjustment();
    }

    /**
     * Gets the default send limit for a post
     *
     * @param Bronto_Reviews_Model_Post_Purchase $post
     * @param int $storeId
     * @return int
     */
    public function getDefaultSendLimit($post, $storeId = 0)
    {
        if (is_null($post->getSendLimit())) {
            return $this->getPostSendLimit($post->getPostType(), 'store', $storeId);
        }
        return (int)$post->getSendLimit();
    }

    /**
     * Gets the multiplier for a reorder reminder
     *
     * @param $scope string
     * @param $scopeId int
     * @return boolean
     */
    public function getPostMultiplier($scope='default', $scopeId=0)
    {
        return $this->getAdminScopedConfig(self::XML_PATH_POST_MULTIPLIER, $scope, $scopeId);
    }

    /**
     * Gets the default post multiplier for a post
     *
     * @param Bronto_Reviews_Model_Post_Purchase $post
     * @param int $store_id
     * @return boolean
     */
    public function getDefaultMultiplier($post, $storeId=0)
    {
        if (is_null($post->getMultiplyByQty())) {
            return $this->getPostMultiplier('store', $storeId);
        }
        return (int)$post->getMultiplyByQty();
    }

    /**
     * Gets the default Bronto message for the post type
     *
     * @param Bronto_Reviews_Model_Post_Purchase $post
     * @param int $storeId
     * @return string
     */
    public function getDefaultMessage($post, $storeId = 0)
    {
        if (is_null($post)) {
            return $this->getReviewSendMessage('store', $storeId);
        } else if (is_null($post->getMessage())) {
            return $this->getPostMessage($post->getPostType(), 'store', $storeId);
        } else {
            return $post->getMessage();
        }
    }
}
