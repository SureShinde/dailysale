<?php

abstract class Bronto_Reviews_Block_Adminhtml_Reviews_Form_Abstract
    extends Mage_Adminhtml_Block_Widget_Form
    implements Bronto_Reviews_Block_Adminhtml_Reviews_Typer
{
    protected $_helper;
    protected $_productHelper;
    protected $_storeId;
    protected $_dependence;
    protected $_enabled;

    /**
     * @see parent
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_helper = Mage::helper('bronto_reviews');
        $this->_productHelper = Mage::helper('bronto_common/product');
        $this->_storeId = $this->getRequest()->getParam('store', 0);
    }

    /**
     * Sets up form enablement provided certain conditions are met
     *
     * @param Mage_Catalog_Model_Product $product
     * @return string
     */
    protected function _disabledReason($product)
    {
        $disabledTexts = array();
        $post = Mage::getModel('bronto_reviews/post_purchase');
        $parent = $this->_productHelper->getConfigurableProduct($product);
        if ($product->getId() != $parent->getId() && $parent->getTypeId() == Mage_Catalog_Model_Product_TYPE::TYPE_CONFIGURABLE) {
            $posts = $post->getCollection()
                ->filterByActive(true)
                ->filterByType($this->getPostType())
                ->filterByProduct($parent->getId())
                ->filterByStoreId($this->_storeId);
            foreach ($posts as $postEntry) {
                $store = Mage::app()->getStore($postEntry->getStoreId());
                $storeName = $store->getId() ? $store->getName() : $this->_helper->__('Default');
                $disabledTexts[] = $this->_helper->__("<a href='{$this->getProductUrl($parent, $postEntry->getStoreId())}'>{$parent->getName()} ({$storeName})<a/>");
            }
        } else if ($product->getTypeId() == Mage_Catalog_Model_Product_TYPE::TYPE_CONFIGURABLE) {
            $configurable = Mage::getModel('catalog/product_type_configurable');
            foreach ($configurable->getChildrenIds($product->getId()) as $group => $ids) {
                $posts = $post->getCollection()
                    ->filterByActive(true)
                    ->filterByType($this->getPostType())
                    ->filterByProduct($ids)
                    ->filterByStoreId($this->_storeId);
                foreach ($posts as $postEntry) {
                    $store = Mage::app()->getStore($postEntry->getStoreId());
                    $storeName = $store->getId() ? $store->getName() : $this->_helper->__('Default');
                    $child = $this->_productHelper->getProduct($postEntry->getProductId(), $postEntry->getStoreId());
                    $disabledTexts[] = $this->_helper->__("<a href='{$this->getProductUrl($child, $postEntry->getStoreId())}'>{$child->getName()} ({$storeName})</a>");
                }
            }
        }
        if (!empty($disabledTexts)) {
            return implode('<br/>', $disabledTexts);
        }
        return null;
    }

    /**
     * Gets a url to the product in question
     *
     * @param Mage_Catalog_Model_Product $product
     * @return string
     */
    protected function getProductUrl($product, $store = null)
    {
        return $this->getUrl('*/catalog_product/edit', array(
            'store' => is_null($store) ? $this->_storeId : $store,
            'active_tab' => "post_{$this->getPostType()}",
            'id' => $product->getId()
        ));
    }

    /**
     * Configure special parts of the form
     *
     * @param Varien_Data_Form_Fieldset $fieldset
     * @param Bronto_Reviews_Model_Post_Purchase $post
     * @return void
     */
    protected abstract function _configureFieldset($fieldset, $post);

    /**
     * @see parent
     */
    protected function _prepareForm()
    {
        $product = Mage::registry('product');
        $messages = Mage::getModel('bronto_common/system_config_source_message');
        $post = Mage::getModel('bronto_reviews/post_purchase')
            ->loadByProduct($product->getId(), $this->getPostType(), $this->_storeId);

        if ($this->_storeId && !$post->getId()) {
            $post->setUseDefault(true);
        }

        $form = new Varien_Data_Form(array(
            'id' => "post_{$this->getPostType()}_form"
        ));

        $fieldset = $form->addFieldset("post_{$this->getPostType()}", array(
            'legend' => $this->getTabLabel()
        ));

        if ($reason = $this->_disabledReason($product)) {
            $fieldset->addField("{$this->getPostType()}_disabled", 'label', array(
                'label' => $this->_helper->__('Disabled Reason'),
                'value' => '',
                'after_element_html' => $reason
            ));
        } else {
            $periodType = "{$this->getPostType()}_period_type";
            $fieldset->addField($periodType, 'hidden', array(
                'name' => $periodType,
                'value' => Bronto_Reviews_Model_Post_Purchase::PERIOD_DAILY
            ));

            $this->_setUpEnablement($fieldset);
            $this->_configureFieldset($fieldset, $post);

            $messageType = "{$this->getPostType()}_message";
            $message = $fieldset->addField($messageType, 'select', array(
                'label' => $this->_helper->__('Bronto Message'),
                'name' => $messageType,
                'values' => $messages->toOptionArray(null, true)
            ));

            $this->_dependsOnEnablement($message);
            $form->setValues($this->_productFormData($post));
            $this->setChild('form_after', $this->_dependence);
        }

        $this->setForm($form);
        return parent::_prepareForm();
    }

    /**
     * Sets up the enablement and dependence
     *
     * @param Varien_Data_Form_Fieldset $fieldset
     * @return void
     */
    protected function _setUpEnablement($fieldset)
    {
        $this->_dependence = $this->getLayout()->createBlock('adminhtml/widget_form_element_dependence');

        $useDefault = null;
        if ($this->_storeId) {
            $useDefaultType = "{$this->getPostType()}_use_default";
            $useDefault = $fieldset->addField($useDefaultType, 'select', array(
                'label' => Mage::helper('adminhtml')->__('Use Default Value'),
                'name' => $useDefaultType,
                'values' => Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray()
            ));
        }

        $enableType = "{$this->getPostType()}_active";
        $this->_enabled = $fieldset->addField($enableType, 'select', array(
            'label' => Mage::helper('adminhtml')->__('Enabled'),
            'name' => $enableType,
            'values' => Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray()
        ));

        $this->_dependence
            ->addFieldMap(
                $this->_enabled->getHtmlId(),
                $this->_enabled->getName());
        if ($useDefault) {
            $this->_dependence
                ->addFieldMap($useDefault->getHtmlId(), $useDefault->getName())
                ->addFieldDependence(
                    $this->_enabled->getName(),
                    $useDefault->getName(),
                    0);
        }
    }

    /**
     * Helper method to add form elements to depend on enablement
     *
     * @param Varien_Data_Form_Element $element
     * @return Bronto_Reviews_Block_Adminhtml_Catalog_Product_Edit_Tab_Abstract
     */
    protected function _dependsOnEnablement($element)
    {
        $this->_dependence
            ->addFieldMap($element->getHtmlId(), $element->getName())
            ->addFieldDependence(
                $element->getName(),
                $this->_enabled->getName(),
                1);
        return $this;
    }

    /**
     * Creates a default / custom select with to an element
     *
     * @param Varien_Data_Form_Fieldset $fieldset
     * @param array $elementDef
     * @return Varien_Data_Form_Element
     */
    protected function _defaultOverride($fieldset, $post, $elementDef)
    {
        $options = array(
            array(
              'value' => 'default',
              'label' => $this->_helper->__('-- Use Default -- ')
            ),
            array(
              'value' => 'custom',
              'label' => $this->_helper->__('Custom Value')
            ),
        );
        $originalKey = $elementDef['name'];
        $elementDef['name'] = "{$this->getPostType()}_{$elementDef['name']}";
        $overrideKey = "{$elementDef['name']}_override";
        $override = $fieldset->addField($overrideKey, "select", array(
            'label' => $elementDef['label'],
            'name' => $overrideKey,
            'note' => $elementDef['note'],
            'values' => $options
        ));
        $post->setData("{$originalKey}_override", is_null($post->getData($originalKey)) ? 'default' : 'custom');
        $elementDef['label'] .= ' Value';
        unset($elementDef['note']);
        $element = $fieldset->addField($elementDef['name'], 'text', $elementDef);
        $this->_dependsOnEnablement($override)->_dependence
            ->addFieldMap($element->getHtmlId(), $element->getName())
            ->addFieldDependence($element->getName(), $override->getName(), 'custom');
        return $element;
    }

    /**
     * Gets form data values from object
     *
     * @param Bronto_Reviews_Model_Post_Purchase $post
     * @return array
     */
    protected function _productFormData($post)
    {
        $data = array();
        foreach ($post->getData() as $key => $value) {
            $data["{$post->getPostType()}_{$key}"] = $value;
        }
        return $data;
    }
}
