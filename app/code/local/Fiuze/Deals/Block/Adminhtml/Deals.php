<?php

/**
 * @category    Fiuze
 * @package     Fiuze_Deals
 * @author      Webinse Team <info@webinse.com>
 */
class Fiuze_Deals_Block_Adminhtml_Deals extends Mage_Adminhtml_Block_Widget_Grid_Container{
    /**
     * Block constructor
     */
    public function __construct(){
        $this->_blockGroup = 'fiuze_deals';
        $this->_controller = 'adminhtml_deals';
        $this->_headerText = Mage::helper('fiuze_deals')->__('Deals');
        //$this->setUseAjax(true);

        $this->_addButton('add', array(
            'label' => $this->getAddButtonLabel(),
            'onclick' => 'setLocation(\'' . $this->getCreateUrl() . '\')',
            'class' => 'add',
        ));

        $this->addButton('save_grid', array(
            'label' => Mage::helper('adminhtml')->__('Save'),
            //'onclick' => 'categorySubmit(\''. $this->getSaveGridUrl() .'\', true)',
            //'type' => 'submit',
            'onclick' => "categorySubmit('{$this->getSaveGridUrl()}')",
            //'onclick' =>  "setLocation('{$this->getSaveGridUrl()}')",
            'class' => 'save',
        ),-1);

        parent::__construct();
        if(!Mage::getSingleton('admin/session')->isAllowed('*/*/create')){
            $this->_removeButton('add');
        }

        if(is_null($this->_backButtonLabel)){
            $this->_backButtonLabel = $this->__('Back');
        }

        parent::__construct();
        $this->setTemplate('fiuze/deals/widget/grid/container.phtml');
    }

    public function getHeaderCssClass(){
        return 'icon-head head-products';
    }
    public function getSaveGridUrl()
    {
        return $this->getUrl('adminhtml/deals/saveGrid');
    }
}