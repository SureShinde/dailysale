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
class ProxiBlue_DynCatProd_Model_Rule_Condition_Additional_Conditions_Transformations_Simples
    extends
    ProxiBlue_DynCatProd_Model_Rule_Condition_Additional_Conditions_Abstract
{
    /**
     * Internal cached helper object
     *
     * @var object
     */
    protected $_helper = null;
    protected $_inputType = 'select';
    protected $_subselectObject;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->setType(
            'dyncatprod/rule_condition_additional_conditions_transformations_simples'
        )
            ->setValue(null);
    }

    /**
     * Populate the internal Operator data with accepatble operators
     *
     * @return object ProxiBlue_DynCatProd_Model_Rule_Condition_Additional_Conditions_Transformations_Parents
     */
    public function loadOperatorOptions()
    {
        $this->setOperatorOption(
            array(
                '+A' => Mage::helper('rule')->__(
                    'then also add its associated products'
                ),
                '+R' => Mage::helper('rule')->__(
                    'then replace it with its associated products'
                ),
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
                "If a complex product type was found %s",
                $this->getOperatorElement()->getHtml()
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
        $str = Mage::helper('dyncatprod')->__(
            "If a complex product type was found <strong>%s</strong>",
            $this->getOperatorName()
        );

        return $str;
    }

    /**
     * Validate
     *
     * Simply place a flag to run this rules validatelater method after
     * collection was built.
     *
     * @param type $object
     *
     * @return boolean
     */
    public function validate(Varien_Object $object)
    {
        mage::register('transform_simples', $this ,true);
        return true;
    }

    /**
     * validateLater
     *
     * Iterate all products found, and if a complex product type
     * add in the products associated with it
     *
     * @param  Varien_Object $object Quote
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
                foreach ($associatedProducts as $associatedProduct) {
                    $this->_subselectObject->addItem(
                        $associatedProduct->getId()
                    );
                }
                if ($this->getOperator() == '+A') {
                    $this->_subselectObject->addItem($product->getId());
                }
            }
        } catch (Exception $e) {
            mage::logException($e);
        }
    }

}
