<?php

abstract class Bronto_News_Block_Adminhtml_System_Config_News extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{

    /**
     * @var Mage_Core_Helper_Abstract
     */
    protected $_helper;

    /**
     * @var
     */
    protected $_renderer;

    /**
     * @var Varien_Object
     */
    protected $_itemDefinition;

    /**
     * @return Bronto_News_Model_Mysql4_Item_Collection
     */
    protected abstract function _pullRssItems();

    /**
     * @return Mage_Core_Helper_Abstract
     */
    protected function _getHelper()
    {
        if (is_null($this->_helper)) {
            $this->setHelper(Mage::helper('bronto_news'));
        }

        return $this->_helper;
    }

    /**
     * @param Mage_Core_Helper_Abstract $helper
     *
     * @return Bronto_News_Block_Adminhtml_System_Config_News
     */
    public function setHelper(Mage_Core_Helper_Abstract $helper)
    {
        $this->_helper = $helper;

        return $this;
    }

    /**
     * @return object
     */
    protected function _getRenderer()
    {
        if (is_null($this->_renderer)) {
            $this->_renderer = Mage::getBlockSingleton('bronto_news/adminhtml_itemRender');
        }

        return $this->_renderer;
    }

    /**
     * @return Varien_Object
     */
    protected function _getItemDefinition()
    {
        if (is_null($this->_item)) {
            $this->_itemDefinition = new Varien_Object(array(
                'frontend_type'   => 'link',
                'show_in_default' => 1,
                'show_in_website' => 1,
                'show_in_store'   => 1,
            ));
        }

        return $this->_itemDefinition;
    }

    /**
     * @param Varien_Data_Form_Element_Fieldset $fieldset
     * @param Bronto_News_Model_Item            $item
     *
     * @return Varien_Data_Form_Element_Abstract
     */
    protected function addItemToFieldset($fieldset, $item)
    {
        $field = $fieldset->addField($item->getId(), 'link', array(
            'value'        => $item->getTitle(),
            'title'        => $item->getTitle(),
            'href'         => $item->getLink(),
            'news_item'    => $item,
            'target'       => '_blank',
            'field_config' => $this->_getItemDefinition(),
        ));

        return $field;
    }

    /**
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $helper = $this->_getHelper();

        if (!$helper->validApiToken()) {
            // Route to API token
            $url = $helper->getScopeUrl('*/system_config/edit', array(
                'section' => 'bronto'
            ));

            $element->setComment("In order to receive <strong>{$element->getLegend()}</strong>, you must enter a valid <a href=\"{$url}\">API Token</a>.");
        } else {
            foreach ($this->_pullRssItems() as $rssItem) {
                $this
                    ->addItemToFieldset($element, $rssItem)
                    ->setRenderer($this->_getRenderer());
            }
        }

        return parent::render($element);
    }

    /**
     * Get Header HTML
     *
     * @see parent
     *
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    protected function _getHeaderHtml($element)
    {
        $html       = parent::_getHeaderHtml($element);
        $tableIndex = strpos($html, '<table');

        return substr($html, 0, $tableIndex);
    }

    /**
     * Get Footer HTML
     *
     * @see parent
     *
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    protected function _getFooterHtml($element)
    {
        $html          = parent::_getFooterHtml($element);
        $fieldsetIndex = strpos($html, '</fieldset');

        return substr($html, $fieldsetIndex);
    }
}
