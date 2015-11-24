<?php

/**
 *
 *
 * @category  ProxiBlue
 * @package   DynCatProd
 * @author    Lucas van Staden <sales@proxiblue.com.au>
 * @copyright 2014 Lucas van Staden (ProxiBlue)
 * @license   http://www.proxiblue.com.au/eula EULA
 * @link      http://www.proxiblue.com.au
 */
class ProxiBlue_DynCatProd_Model_Rule_Condition_Additional_Conditions_Transformations_Childrencount
    extends
    ProxiBlue_DynCatProd_Model_Rule_Condition_Additional_Conditions_Abstract
{
    /**
     * Internal cached helper object
     *
     * @var object
     */
    protected $_helper = null;
    protected $_inputType = 'text';
    protected $_subselectObject;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->setType(
            'dyncatprod/rule_condition_additional_conditions_transformations_childrencount'
        )
            ->setValue(null);
        $this->loadActionOptions();
    }

    /**
     * Populate the internal Operator data with accepatble operators
     */
    public function loadOperatorOptions()
    {
        $this->setOperatorOption(
            array(
                '==' => Mage::helper('rule')->__('equals to '),
                '>'  => Mage::helper('rule')->__('more than '),
                '<'  => Mage::helper('rule')->__('less than '),
                '>=' => Mage::helper('rule')->__('more than or equals to'),
                '<=' => Mage::helper('rule')->__('less than or equals to'),
            )
        );

        return $this;
    }

    /**
     *
     *
     * @return object ProxiBlue_DynCatProd_Model_Rule_Condition_Additional_Conditions_Transformations_Parents
     */
    public function loadActionOptions()
    {
        $this->setActionOption(
            array(
                'RF' => Mage::helper('rule')->__('then remove the complex product from the result'),
                'RS' => Mage::helper('rule')->__('then replace the complex product with the children products'),
                '+C' => Mage::helper('rule')->__('then also add the children products'),
            )
        );

        return $this;
    }


    /**
     * Render this as html
     *
     * @return string
     */
    public function asHtml()
    {
        $html = $this->getTypeElement()->getHtml() .
            $this->getHelper()->__(
                "If a complex product has %s %s children products, then %s",
                $this->getOperatorElement()->getHtml(),
                $this->getValueElement()->getHtml(),
                $this->getActionElement()->getHtml()
            );
        if ($this->getId() != '1') {
            $html .= $this->getRemoveLinkHtml();
        }

        return $html;
    }

    public function getHelper()
    {
        if ($this->_helper == null) {
            $this->_helper = mage::helper('dyncatprod');
        }

        return $this->_helper;
    }

    public function asString($format = '')
    {
        $str = $this->getHelper()->__(
            "if complex product has <strong>%s</strong> children products %s, then %s",
            $this->getOperatorName(),
            $this->getValueName(),
            $this->getActionName()
        );

        return $str;
    }

    /**
     * Validate
     *
     * Simply place a flag to run this rules vlidateLater method after
     * collection was built.
     *
     * @param $object
     *
     * @return boolean
     */
    public function validate(Varien_Object $object)
    {
        mage::register('transform_by_count', $this, true);

        return true;
    }

    /**
     * validateLater
     *
     * Iterate all products found, and if a complex product type
     * determine if selected rule is valid
     *
     *
     *
     * @return boolean
     */
    public function validateLater($category)
    {
        /**
         * Array storage of data just don't work when we have massive catalogs to works with
         * Instead store the found id's in the new subselect model for later use
         */
        $this->_subselectObject = mage::getModel('dyncatprod/subselect')
            ->setCategory($category)
            ->clear();
        $pagesize = mage::getStoreConfig(
            'dyncatprod/rebuild/collection_pagesize'
        );

        $collection = $category->getProductCollection();
        // remove GROUP and DISTINCT from collection, else page count will be 1.
        $countCollection = clone $collection;
        $countSelect = $countCollection->getSelect();
        $countSelect->reset(Zend_Db_Select::GROUP);
        $countSelect->reset(Zend_Db_Select::DISTINCT);
        $collection->setPageSize($pagesize);
        $pages = $countCollection->getLastPageNumber();
        $currentPage = 1;
        if (Mage::getStoreConfig('dyncatprod/debug/enabled')
            && Mage::getStoreConfig('dyncatprod/debug/level') >= 10
        ) {
            // debugging this is hell, so limit the number of pages to pull
            $this->getHelper()->debug(
                "In debug mode with level 10. Only doing last page to speed up debugging."
                . $collection->getSelect(),
                5
            );
            $currentPage = $pages;
        }
        $this->getHelper()->debug(
            "Product collection before transformation of parents: "
            . $collection->getSelect(),
            5
        );
        $collection->setPageSize($pagesize);
        do {
            $memory = memory_get_peak_usage(true);
            $this->getHelper()->debug(
                "Transformations: Processing page {$currentPage} / {$pages} using batch size of {$pagesize} with memory at {$memory}",
                5
            );
            //Tell the collection which page to load.
            $collection->setCurPage($currentPage);
            $collection->load();
            foreach ($collection as $product) {
                $this->getChildData($product);
            }
            $currentPage++;
            //make the collection unload the data in memory so it will pick up the next page when load() is called.
            $collection->clear();
            $this->_subselectObject->dumpDataToDb();
        } while ($currentPage <= $pages);

        $this->getHelper()->debug(
            "End of product transformation loop",
            4
        );

        return true;
    }

    /**
     * Get the child data of the given product object
     *
     * @param object $product
     *
     * @return array
     */
    private function getChildData($product)
    {
        try {
            if (is_object($product)) {
                switch ($product->getTypeId()) {
                    case Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE:
                        $conf = Mage::getModel(
                            'catalog/product_type_configurable'
                        )->setProduct($product);
                        $associatedProducts = $conf->getUsedProductCollection()
                            ->addAttributeToSelect('*')
                            ->addFilterByRequiredOptions();
                        break;
                    case Mage_Catalog_Model_Product_Type::TYPE_GROUPED:
                        $associatedProducts = $product->getTypeInstance(true)
                            ->getAssociatedProducts($product);
                        break;
                    case Mage_Catalog_Model_Product_Type::TYPE_BUNDLE:
                        $associatedProducts = $product->getTypeInstance(true)
                            ->getSelectionsCollection(
                                $product->getTypeInstance(true)->getOptionsIds(
                                    $product
                                ), $product
                            );
                        break;
                    default:
                        $associatedProducts = array();
                        $this->_subselectObject->addItem($product->getId());
                        break;
                }
                if(is_object($associatedProducts)) {
                    $associatedProductCount = $associatedProducts->count();
                    $result = $this->validateAttribute($associatedProductCount);
                    if ($result) {
                        switch ($this->getAction()) {
                            case 'RF': //then remove the complex product from the result
                                $this->getHelper()->debug(
                                    "complex product {$product->getSku()} removed
                                from result as {$associatedProductCount} {$this->getOperator()} {$this->getValue()} ",
                                    10
                                );
                                break;
                            case 'RS': //then replace the complex product with the children products
                                foreach ($associatedProducts as $associatedProduct) {
                                    $this->_subselectObject->addItem(
                                        $associatedProduct->getId()
                                    );
                                }
                                break;
                            case '+C': // then also add the children products
                                $this->_subselectObject->addItem($product->getId());
                                foreach ($associatedProducts as $associatedProduct) {
                                    $this->_subselectObject->addItem(
                                        $associatedProduct->getId()
                                    );
                                }
                                break;
                        }
                    } else {
                        // not validated so add in the item (works in reverse)
                        $this->_subselectObject->addItem($product->getId());
                    }
                }
            }
        } catch (Exception $e) {
            mage::logException($e);
        }
    }

    /**
     * Not really an attribute, but the functionality in the parent is generic enough
     * to allow us to validate our numbers with it :)
     *
     * @param mixed $validatedValue
     *
     * @return bool
     */
    public function validateAttribute($validatedValue)
    {
        return parent::validateAttribute($validatedValue);
    }

}
