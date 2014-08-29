<?php
/**
 * File browser form block
 *
 * @author Mihail
 */
class Fiuze_Importproducts_Block_Adminhtml_Importproducts_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * Prepare form action
     *
     * @return Fiuze_Importproducts_Block_Adminhtml_Form
     */
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form(array(
            'id'      => 'edit_form',
            'action'  => $this->getUrl('*/*/import'),
            'method'  => 'post',
            'enctype' => 'multipart/form-data'
        ));


        $fieldset = $form->addFieldset('file_fieldset', array(
            'legend'    => Mage::helper('fiuze_importproducts')->__('File Information'), 'class' => 'fieldset-wide'
        ));

		$this->_addElementTypes($fieldset);

        $fieldset->addField('importfile', 'file', array(
            'name'      => 'importfile',
            'label'     => Mage::helper('fiuze_importproducts')->__('File'),
            'title'     => Mage::helper('fiuze_importproducts')->__('File'),
            'required'  => true,
            'disabled'  => false,
        ));
        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}