<?php

class Bronto_Product_Model_Recommendation extends Mage_Core_Model_Abstract
{
    const SOURCE_RELATED_PRODUCT   = 'related';
    const SOURCE_UPSELL_PRODUCT    = 'upsell';
    const SOURCE_CROSSSELL_PRODUCT = 'crosssell';
    const SOURCE_BESTSELLER        = 'bestseller';
    const SOURCE_MOST_VIEWED       = 'mostviewed';
    const SOURCE_RECENTLY_VIEWED   = 'recentlyviewed';
    const SOURCE_CUSTOM            = 'custom';
    const SOURCE_NEW_PRODUCT       = 'new';

    const SOURCE_PRIMARY           = 'primary';
    const SOURCE_SECONDARY         = 'secondary';
    const SOURCE_FALLBACK          = 'fallback';
    const SOURCE_EXCLUSION         = 'exclusion';

    const TYPE_API                 = 'api';
    const TYPE_CONTENT_TAG         = 'content_tag';

    /**
     * Local caches of the product ids in the targetted sources
     * @var array
     */
    private $_customSources;
    private $_sources;
    private $_customer;

    private static $_sourceOrder = array(
        self::SOURCE_EXCLUSION => 0,
        self::SOURCE_PRIMARY => 10,
        self::SOURCE_SECONDARY => 20,
        self::SOURCE_FALLBACK => 30
    );

    /**
     * @see parent
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('bronto_product/recommendation');
    }

    /**
     * Is this a content tag type of recommendation?
     *
     * @return bool
     */
    public function isContentTag()
    {
        return $this->getContentType() == self::TYPE_CONTENT_TAG;
    }

    /**
     * Is this content dynamic?
     *
     * @return bool
     */
    public function isDynamicContent()
    {
        return $this->isContentTag() &&
            preg_match('/\{\s*dynamic_code\s*\}/', $this->getTagContent());
    }

    /**
     * Geta an array of Product Ids defined by the user
     *
     * @param string $source
     * @return array
     */
    public function getCustomProductIds($source)
    {
        if (is_null($this->_customSources)) {
            foreach ($this->getSources() as $key => $value) {
                $ids = $this->getData("manual_{$key}_source");
                if (empty($ids)) {
                    $ids = array();
                } else {
                    $ids = preg_split('/\s*,\s*/', $ids);
                }
                $this->_customSources[$key] = $ids;
            }
        }
        return $this->_customSources[$source];
    }

    /**
     * Sets a customer for this recommendation; Required for
     * Recently viewed
     *
     * @param Mage_Customer_Model_Customer $customer
     * @return Bronto_Product_Model_Recommendation
     */
    public function setCustomer(Mage_Customer_Model_Customer $customer)
    {
        $this->_customer = $customer;
        return $this;
    }

    /**
     * Gets the associated customer for the recommendation
     *
     * @return Mage_Customer_Model_Customer
     */
    public function getCustomer()
    {
        return $this->_customer;
    }

    /**
     * Tests whether this recommendation requires a customer id
     *
     * @param string $source (Optional)
     * @return bool
     */
    public function isCustomerRequired($source = null)
    {
        if ($source) {
            return $this->getData("{$source}_source") == self::SOURCE_RECENTLY_VIEWED;
        } else {
            foreach ($this->getSources() as $value) {
                if ($value == self::SOURCE_RECENTLY_VIEWED) {
                    return true;
                }
            }
            return false;
        }
    }

    /**
     * Given delivery fields, process this content tag content
     *
     * @param array $fields
     * @return string
     */
    public function processContent($fields)
    {
        if ($this->isDynamicContent()) {
            return Mage::getModel('bronto_product/filter')
                ->setContent($this->getTagContent())
                ->setFields($fields)
                ->process();
        }
        return $this->getTagContent();
    }

    /**
     * Tests this recommendation require a specific product
     *
     * @param string $source (Optional)
     * @return bool
     */
    public function isProductRelated($source = null)
    {
        $productRelated = array(
          self::SOURCE_RELATED_PRODUCT,
          self::SOURCE_UPSELL_PRODUCT,
          self::SOURCE_CROSSSELL_PRODUCT
        );
        if ($source) {
            return in_array($this->getData("{$source}_source"), $productRelated);
        } else {
            foreach ($this->getSources() as $value) {
                if (in_array($value, $productRelated)) {
                    return true;
                }
            }
            return false;
        }
    }

    /**
     * Gets the defined sources for this recommendation
     *
     * @return array
     */
    public function getSources()
    {
        if (is_null($this->_sources)) {
            $this->_sources = array();
            foreach ($this->getData() as $key => $value) {
                if (preg_match('/^([^manual_].+)_source$/', $key, $matches)) {
                    $this->_sources[$matches[1]] = $value;
                }
            }
            uksort($this->_sources, array($this, 'compareSources'));
        }
        return $this->_sources;
    }

    /**
     * Comparator callback for sources
     *
     * @param string $sourceA
     * @param string $sourceB
     * @return int
     */
    public function compareSources($sourceA, $sourceB)
    {
        $sortValueA = self::$_sourceOrder[$sourceA];
        $sortValueB = self::$_sourceOrder[$sourceB];
        if ($sortValueA == $sortValueB) {
            return 0;
        } else if ($sortValueA < $sortValueB) {
            return -1;
        } else {
            return 1;
        }
    }

    /**
     * Performs a copy of the model without an id
     *
     * @return Bronto_Product_Model_Recommendation
     */
    public function softCopy()
    {
        $data = $this->getData();
        // Pop all static references
        unset($data['entity_id'], $data['tag_id']);
        $originalName = preg_replace('/Copy\s*\d* of /', '', $this->getName());
        $count = $this->getCollection()
            ->nameEndsWith($originalName)
            ->count();
        $data['name'] = sprintf("Copy %d of %s", $count, $originalName);
        return Mage::getModel('bronto_product/recommendation')->setData($data);
    }

    /**
     * The list of saved options as a menu
     *
     * @return array
     */
    public function toOptionArray($default = false)
    {
        $noneSelected = $default ? 'Use Default' : 'None Selected';
        $helper = Mage::helper('bronto_product');
        $options = array();

        $recommendations = $this->getCollection()->orderAlphebetically();
        foreach ($recommendations as $rec) {
            $options[] = array(
                'label' => $rec->getName(),
                'value' => $rec->getId());
        }

        array_unshift($options, array(
            'label' => $helper->__('-- ' . $noneSelected . ' --'),
            'value' => ''
        ));
        return $options;
    }
}
