<?php
class Fiuze_Bestsellercron_Block_Adminhtml_System_Config_Form_Field_Bestseller extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{

    /**
     * @var Fiuze_Bestsellercron_Block_Adminhtml_System_Config_Form_Field_Checkboxgroup
     */
    protected $_checkboxGroupRenderer;

    /**
     * @var Fiuze_Bestsellercron_Block_Adminhtml_System_Config_Form_Field_Categorygroup
     */
    protected $_categoryGroupRenderer;

    /**
     * @var Fiuze_Bestsellercron_Block_Adminhtml_System_Config_Form_Field_Categoryexcludegroup
     */
    protected $_categoryExcludeGroupRenderer;

    /**
     * @var Fiuze_Bestsellercron_Block_Adminhtml_System_Config_Form_Field_Criteriagroup
     */
    protected $_criteriaGroupRenderer;

    /**
     * @var Fiuze_Bestsellercron_Block_Adminhtml_System_Config_Form_Field_Timeperiodgroup
     */
    protected $_timePeriodGroupRenderer;


    public function __construct() {
        parent::__construct();
        $this->setTemplate('fiuze/bestsellercron/system/config/form/field/array.phtml');
    }

    /**
     * Prepare to render
     */
    protected function _prepareToRender()
    {
        $this->addColumn('category', array(
            'label' => Mage::helper('bestsellercron')->__('Category'),
            'style' => 'width:120px',
            'renderer' => $this->_getCategoryGroupRenderer()->setSelect($this->getArrayRows()),
        ));
        $this->addColumn('criteria', array(
            'label' => Mage::helper('bestsellercron')->__('Criteria'),
            'style' => 'width:120px',
            'renderer' => $this->_getCriteriaGroupRenderer(),
        ));
        $this->addColumn('category_exclude', array(
            'label' => Mage::helper('bestsellercron')->__('Category Exclude'),
            'style' => 'width:120px',
            'renderer' => $this->_getCategoryExcludeGroupRenderer()->setSelect($this->getArrayRows()),
        ));
        $this->addColumn('time_period', array(
            'label' => Mage::helper('bestsellercron')->__('Time Period'),
            'style' => 'width:120px',
            'renderer' => $this->_getTimePeriodGroupRenderer(),
        ));
        $this->addColumn('days_period', array(
            'label' => Mage::helper('bestsellercron')->__('Days Period'),
            'style' => 'width:120px',
            'class' => ' required-entry validate-digits'
        ));
        $this->addColumn('checkbox', array(
            'label' => Mage::helper('bestsellercron')->__('Checkbox'),
            'style' => 'width:120px',
            'renderer' => $this->_getCheckboxGroupRenderer(),//->setSelect($this->getArrayRows()),
        ));
        $this->addColumn('task_schedule', array(
            'label' => Mage::helper('bestsellercron')->__('Task Schedule'),
            'style' => 'width:120px',
            'class' => ' required-entry'
        ));
        $this->addColumn('number_of_products', array(
            'label' => Mage::helper('bestsellercron')->__('Number of products'),
            'style' => 'width:120px',
            'class' => ' required-entry validate-digits'
        ));
        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('adminhtml')->__('Add Item');
    }

    /**
     * Retrieve group column renderer
     *
     * @return Fiuze_Bestsellercron_Block_Adminhtml_System_Config_Form_Field_Checkboxgroup
     */
    protected function _getCheckboxGroupRenderer()
    {
        if (is_null($this->_checkboxGroupRenderer)) {
            $this->_checkboxGroupRenderer = $this->getLayout()->createBlock(
                'bestsellercron/adminhtml_system_config_form_field_checkboxgroup', '',
                array('is_render_to_js_template' => true)
            );
            $this->_checkboxGroupRenderer->setClass('checkbox_group');
            $this->_checkboxGroupRenderer->setExtraParams('style="width:120px"');
        }
        return $this->_checkboxGroupRenderer;
    }

    /**
     * Retrieve group column renderer
     *
     * @return Fiuze_Bestsellercron_Block_Adminhtml_System_Config_Form_Field_Categorygroup
     */
    protected function _getCategoryGroupRenderer()
    {
        if (is_null($this->_categoryGroupRenderer)) {
            $this->_categoryGroupRenderer = $this->getLayout()->createBlock(
                'bestsellercron/adminhtml_system_config_form_field_categorygroup', '',
                array('is_render_to_js_template' => true)
            );
            $this->_categoryGroupRenderer->setClass('category_group_select');
            $this->_categoryGroupRenderer->setExtraParams('style="width:120px"');
        }
        return $this->_categoryGroupRenderer;
    }

    /**
     * Retrieve group column renderer
     *
     * @return Fiuze_Bestsellercron_Block_Adminhtml_System_Config_Form_Field_Categoryexcludegroup
     */
    protected function _getCategoryExcludeGroupRenderer()
    {
        if (is_null($this->_categoryExcludeGroupRenderer)) {
            $this->_categoryExcludeGroupRenderer = $this->getLayout()->createBlock(
                'bestsellercron/adminhtml_system_config_form_field_categoryexcludegroup', '',
                array('is_render_to_js_template' => true)
            );
            $this->_categoryExcludeGroupRenderer->setClass('category_group_select');
            $this->_categoryExcludeGroupRenderer->setExtraParams('style="width:120px"');
        }
        return $this->_categoryExcludeGroupRenderer;
    }

    /**
     * Retrieve group column renderer
     *
     * @return Fiuze_Bestsellercron_Block_Adminhtml_System_Config_Form_Field_Criteriagroup
     */
    protected function _getCriteriaGroupRenderer()
    {
        if (is_null($this->_criteriaGroupRenderer)) {
            $this->_criteriaGroupRenderer = $this->getLayout()->createBlock(
                'bestsellercron/adminhtml_system_config_form_field_criteriagroup', '',
                array('is_render_to_js_template' => true)
            );
            $this->_criteriaGroupRenderer->setClass('category_group_select');
            $this->_criteriaGroupRenderer->setExtraParams('style="width:120px"');
        }
        return $this->_criteriaGroupRenderer;
    }

    /**
     * Retrieve group column renderer
     *
     * @return Fiuze_Bestsellercron_Block_Adminhtml_System_Config_Form_Field_Criteriagroup
     */
    protected function _getTimePeriodGroupRenderer()
    {
        if (is_null($this->_timePeriodGroupRenderer)) {
            $this->_timePeriodGroupRenderer = $this->getLayout()->createBlock(
                'bestsellercron/adminhtml_system_config_form_field_timeperiodgroup', '',
                array('is_render_to_js_template' => true)
            );
            $this->_timePeriodGroupRenderer->setClass('category_group_timeperiod');
            $this->_timePeriodGroupRenderer->setExtraParams('style="width:140px"');
        }
        return $this->_timePeriodGroupRenderer;
    }

    /**
     * Prepare existing row data object
     *
     * @param Varien_Object
     */
    protected function _prepareArrayRow(Varien_Object $row)
    {
        $row->setData(
            'option_extra_attr_' . $this->_getCategoryGroupRenderer()->calcOptionHash($row->getData('category')),
            'selected="selected"'
        );
        $row->setData(
            'option_extra_attr_' . $this->_getCriteriaGroupRenderer()->calcOptionHash($row->getData('criteria')),
            'selected="selected"'
        );
        $row->setData(
            'option_extra_attr_' . $this->_getCategoryExcludeGroupRenderer()->calcOptionHash($row->getData('category_exclude')),
            'selected="selected"'
        );
        $timePeriod =$row->getData('time_period');
        $row->setData(
            'option_extra_attr_' . $this->_getTimePeriodGroupRenderer()->calcOptionHash(0, $timePeriod[0]),
            'selected="selected"'
        );
        $row->setData(
            'option_extra_attr_' . $this->_getTimePeriodGroupRenderer()->calcOptionHash(1, $timePeriod[1]),
            'selected="selected"'
        );
        $row->setData(
            'option_extra_attr_' . $this->_getTimePeriodGroupRenderer()->calcOptionHash(2, $timePeriod[2]),
            'selected="selected"'
        );
    }
}