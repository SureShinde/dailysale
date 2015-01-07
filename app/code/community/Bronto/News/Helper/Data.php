<?php

class Bronto_News_Helper_Data extends Bronto_Common_Helper_Data
{
    const XML_PATH_FEEDS = 'bronto_news/feeds';

    /**
     * @var Varien_Http_Adapter_Curl
     */
    protected $_xmlProvider;

    /**
     * @var Zend_Http_Client
     */
    protected $_client;

    /**
     * Module Human Readable Name
     */
    protected $_name = 'Bronto News & Announcements';

    /**
     * Get Human Readable Name
     *
     * @return string
     */
    public function getName()
    {
        return $this->__($this->_name);
    }

    /**
     * Gets the RSS item threshold
     *
     * @return int
     */
    public function getThreshold()
    {
        return (int)$this->getAdminScopedConfig(self::XML_PATH_FEEDS . '/threshold');
    }

    /**
     * Gets the release announcements RSS url
     *
     * @return string
     */
    public function getReleaseUrl()
    {
        $path = self::XML_PATH_FEEDS . '/' . Bronto_News_Model_Item::TYPE_RELEASE;

        return $this->getAdminScopedConfig($path);
    }

    /**
     * Gets the general feed RSS url
     *
     * @return string
     */
    public function getGeneralUrl()
    {
        $path = self::XML_PATH_FEEDS . '/' . Bronto_News_Model_Item::TYPE_GENERAL;

        return $this->getAdminScopedConfig($path);
    }

    /**
     * Sets the XML provider to be used in conjunction with SimpleXMLElement
     *
     * @param Varien_Http_Adapter_Curl $provider
     *
     * @return Bronto_Common_Helper_Data
     */
    public function setXmlProvider(Varien_Http_Adapter_Curl $provider)
    {
        if (is_null($this->_client)) {
            $this->_client = new Zend_Http_Client();
        }

        $this->_xmlProvider = $provider;
        $this->_client->setAdapter($provider);

        return $this;
    }

    /**
     * Given a url, use the provider to pull from the url
     *
     * @param $url
     *
     * @return string
     */
    protected function _getXml($url)
    {
        if (is_null($this->_xmlProvider)) {
            $this->setXmlProvider(new Varien_Http_Adapter_Curl());
        }

        $this->_client->setUri($url ? $url : 'http');
        $response = $this->_client->request(Zend_Http_Client::GET);

        return $response->getBody();
    }

    /**
     * Is this is the first time? (ie: no RSS items in DB)
     *
     * @return boolean
     */
    public function isFirstTime()
    {
        return Mage::getModel('bronto_news/item')->getCollection()->count() === 0;
    }

    /**
     * Processes the RSS feed based on type and url
     *
     * @param string  $type
     * @param string  $url
     * @param boolean $silence
     *
     * @throws RuntimeException
     */
    protected function _processItems($type, $url, $silence)
    {
        // We want to report on XML parsing errors
        $previousValue = libxml_use_internal_errors(true);

        try {
            $date = Mage::getModel('core/date');
            $xml  = new SimpleXMLElement($this->_getXml($url));

            foreach ($xml->channel->item as $item) {
                $guid          = (string)$item->guid;
                $description   = (string)$item->description;
                $formattedDate = strtotime((string)$item->pubDate);

                // Attempts to load the RSS feed
                $rssItem = Mage::getModel('bronto_news/item')->load($guid, 'link');

                // Silence the alert if this rss item already exists
                $rssItem
                    ->setSilence($silence ? $silence : $rssItem->hasLink())
                    ->setTitle((string)$item->title)
                    ->setDescription(current(explode('--', $description)))
                    ->setPubDate($date->date('Y-m-d H:m:s', $formattedDate))
                    ->setLink($guid)
                    ->setType($type)
                    ->save();

                $this->writeDebug("Processed [$type] item: {$item->title}");
            }
        } catch (Exception $e) {
            $exception = new RuntimeException("Failed to pull RSS for $type announcements.");
            $this->writeError($e->getMessage());
            $this->writeError("Failed processing $url:\n{$e->getTraceAsString()}");
        }

        // Would be great in a finally block
        libxml_use_internal_errors($previousValue);

        if (isset($exception)) {
            throw $exception;
        }
    }

    /**
     * Pulls the RSS information, creating announcements as necessary
     */
    public function pullNewItems()
    {
        $urls = array(
            Bronto_News_Model_Item::TYPE_RELEASE => $this->getReleaseUrl(),
            Bronto_News_Model_Item::TYPE_GENERAL => $this->getGeneralUrl(),
        );

        if ($firstTime = $this->isFirstTime()) {
            try {
                Mage::getModel('bronto_news/item')
                    ->setTitle('Thank you for installing the Bronto Extension for Magento. Please visit our resource center for full documentation and release notes.')
                    ->setDescription('Please click on the Read Details link to be redirected to the resource center.')
                    ->setLink('http://a.bron.to/magento')
                    ->setType(Bronto_News_Model_Item::TYPE_OTHER)
                    ->setPubDate(Mage::getModel('core/date')->date('Y-m-s H:m:s'))
                    ->save();
            } catch (Exception $e) {
                $this->writeError('Could not create initial announcement');
            }
        }

        foreach ($urls as $type => $url) {
            $this->writeDebug("Processing $type RSS feed at $url");
            $this->_processItems($type, $url, $firstTime);
        }
    }

    /**
     * Wraps the internal notification URL with a controller route to
     * mark the notification as read
     *
     * @param Bronto_News_Model_Item $item
     *
     * @return string
     */
    protected function wrapNotificationUrl(Bronto_News_Model_Item $item)
    {
        return Mage::app()->getStore()
            ->getUrl('announcement/route/index', array('item' => $item->getId()));
    }

    /**
     * Creates an in-app Magento announcement
     *
     * @param Bronto_News_Model_Item $item
     */
    public function createAnnouncement(Bronto_News_Model_Item $item)
    {
        if ($item->isAlert()) {
            $notice = Mage::getModel('adminnotification/inbox')
                ->setSeverity(Mage_Adminnotification_Model_Inbox::SEVERITY_NOTICE)
                ->setTitle($item->getAlertTitle())
                ->setDescription($item->getDescription())
                ->setUrl($this->wrapNotificationUrl($item))
                ->save();

            $item
                ->setSilence(true)
                ->setNotificationId($notice->getId())
                ->save();
        }
    }
}
