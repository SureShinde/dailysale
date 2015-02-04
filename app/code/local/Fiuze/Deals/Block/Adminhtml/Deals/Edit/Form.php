<?php

/**
 * @category    Fiuze
 * @package     Fiuze_Deals
 * @author     Alena Tsareva <alena.tsareva@webinse.com>
 */
class Fiuze_Deals_Block_Adminhtml_Deals_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{

    protected function _construct()
    {
        parent::_construct();

    }

    /**
     * Prepare student form
     * @return Mage_Adminhtml_Block_Widget_Form
     */
    protected function _prepareForm()
    {
        $data = Mage::registry('current_product_deal');


        $categoryIds = $data->getCategoryIds();
        $categoryAll = '';
        foreach ($categoryIds as $category_id) {
            $category = Mage::getModel('catalog/category')->load($category_id);
            $categoryAll .= $category->getName() . ', ';
        }
        $categoryAll = trim($categoryAll, ', ');
        $data->setCategoryAll($categoryAll);

        $productDeals = Mage::getResourceModel('fiuze_deals/deals_collection')
            ->addFilter('product_id',$data->getData('entity_id'))->getFirstItem();

        $dealsPrice = $productDeals->getDealsPrice();
        $data->setDealPrice($dealsPrice);

        $dealsQty = $productDeals->getDealsQty();
        $data->setDealQty($dealsQty);

        $qty = (int)$data->getStockItem()->getQty();
        $data->setQty($qty);

        $originSpecialPrice = $productDeals->getOriginSpecialPrice();
        $data->setOriginSpecialPrice($originSpecialPrice);

        $sortOrder = $productDeals->getSortOrder();
        $data->setSortOrder($sortOrder);

        $dealsActive = $productDeals->getDealsActive();
        $data->setDealsActive($dealsActive);



        $helper = Mage::helper('fiuze_deals');
        $form = new Varien_Data_Form(array(
            'id' => 'edit_form',
            'action' => $this->getUrl('*/*/save', array('id' => $this->getRequest()->getParam('id'))),
            'method' => 'post',
        ));


        $fieldset = $form->addFieldset('new_user', array(
            'legend' => $helper->__('Product Information')
        ));

        if (!is_null($data->getId())) {
            // If edit add id
            $form->addField('entity_id', 'hidden', array(
                    'name' => 'entity_id',
                    'value' => $data->getId())
            );
        }

        $fieldset->addField('name', 'label', array(
            'label' => $helper->__('Product Name'),
            'name' => 'name'
        ));

        $fieldset->addField('category_all', 'label', array(
            'label' => $helper->__('Product category'),
            'name' => 'category_all',
        ));

        $fieldset->addField('price', 'label', array(
            'label' => $helper->__('Original Price ').Mage::app()->getStore()->getBaseCurrency()->getCode(),
            'name' => 'price',
        ));

        $fieldset->addField('origin_special_price', 'label', array(
            'label' => $helper->__('Original special price ').Mage::app()->getStore()->getBaseCurrency()->getCode(),
            'name' => 'origin_special_price',
        ));

        $fieldset->addField('deal_price', 'text', array(
            'label' => $helper->__('Deal Price ').Mage::app()->getStore()->getBaseCurrency()->getCode(),
            'class' => 'required-entry validate-number',
            'required' => true,
            'name' => 'deal_price',));

        $fieldset->addField('qty', 'label', array(
            'label' => $helper->__('Quantity'),
            'class' => 'require-entry validate-number',
            'name' => 'qty',
            'note' => $helper->toQuantityHtml($qty) ,
        ));

        $fieldset->addField('deal_qty', 'text', array(
            'label' => $helper->__('Deal Quantity'),
            'class' => 'require-entry validate-number validate-not-negative-number',
            'name' => 'deal_qty',
            'required' => true,
            'note' => $helper->__('Quantity products with special price'),
        ));

        $fieldset->addField('sort_order', 'text', array(
            'label' => $helper->__('Sort order'),
            'class' => 'require-entry validate-number',
            'name' => 'sort_order',
            'required' => true,
        ));

        $fieldset->addField('deals_active', 'select', array(
            'label' => $helper->__('Status'),
            'name' => 'deals_active',
            'value' => 1,
            'values' => Mage::getModel('fiuze_deals/System_Config_Source_Status')->toOptionArray(),
        ));


        $form->addValues($data->getData());

        $form->setUseContainer(true);
        $form->setId('edit_form');
        $this->setForm($form);

    }
}