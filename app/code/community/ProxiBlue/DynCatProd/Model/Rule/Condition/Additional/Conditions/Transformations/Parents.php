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
class ProxiBlue_DynCatProd_Model_Rule_Condition_Additional_Conditions_Transformations_Parents
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
            'dyncatprod/rule_condition_additional_conditions_transformations_parents'
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
                'RS' => Mage::helper('rule')->__('then replace it with '),
                '+C' => Mage::helper('rule')->__(
                    'then add the simple and also add '
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
                "If a simple product was found %s %s",
                $this->getOperatorElement()->getHtml(),
                $this->getValueElement()->getHtml()
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
            "If a simple product was found <strong>%s %s</strong>",
            $this->getOperatorName(),
            $this->getValueName()
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
        mage::register('transform_parents', $this ,true);
        return true;
    }

    /**
     * validateLater
     * This is a special case rule.
     *
     * To get the parents of simples the following actions must take place:
     *
     * 1. load the given product collection
     * 2. Iterate the results, and determine if that product has a parent.
     * 3. If it does have a parent:
     * 3.1 If replace: add it, remove the simple
     * 3.2 If add simply add it to the collection result
     * 4. set a 'replace ids' flag into the collection to allow the originator
     * code to use this set of ids, and not the collection
     *
     * @param  $category
     *
     * @return boolean
     */
    public function validateLater($category)
    {
        /**
         * Array storage of data just don't work when we have massive catalogs to work with
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
                $this->getParentData($product);
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

    }

    /**
     * Get the parent data of the given product object
     *
     * @param object $product
     *
     * @return array
     */
    private function getParentData($product)
    {
        try {
            if (is_object($product)) {
                if ($product->getTypeId()
                    == Mage_Catalog_Model_Product_Type::TYPE_SIMPLE
                ) {
                    $parentIds = Mage::getModel(
                        'catalog/product_type_configurable'
                    )
                        ->getParentIdsByChild(
                            $product->getId()
                        ); //check for config product
                    if (!$parentIds) {
                        $parentIds = Mage::getModel(
                            'catalog/product_type_grouped'
                        )
                            ->getParentIdsByChild(
                                $product->getId()
                            ); // check for grouped product
                    }
                    if ($parentIds) {
                        //some simples belong to multiple parents, so make sure they are all singular entries.
                        if (count($parentIds) > 1) {
                            mage::helper('dyncatprod')->debug(
                                'Found multiple parents for child '
                                . $product->getId(),
                                5
                            );
                            foreach ($parentIds as $parentId) {
                                $this->_subselectObject->addItem($parentId);
                            }
                        } else {
                            $parentId = array_pop($parentIds);
                            $this->_subselectObject->addItem($parentId);
                        }
                        if ($this->getOperator() != 'RS') {
                            mage::helper('dyncatprod')->debug(
                                'simple '
                                . $product->getId()
                                . ' was placed back after parent',
                                10
                            );
                            $this->_subselectObject->addItem($product->getId());
                        }
                    } else {
                        $this->_subselectObject->addItem($product->getId());
                    }
                } else {
                    $this->_subselectObject->addItem($product->getId());
                }
            }
        } catch (Exception $e) {
            mage::logException($e);
        }
    }

    /**
     * Populate the available Value options for the rule in admin
     *
     * @return \ProxiBlue_DynCatProd_Model_Rule_Condition_Additional_Conditions_Transformations_Parents
     */
    public function loadValueOptions()
    {
        $this->setValueOption(
            array(
                'parent' => Mage::helper('rule')->__(
                    'Configurable or Group Parent Product'
                )
            )
        );

        return $this;
    }

}
