<?php
/**
 * File browser form block
 *
 * @author Mihail
 */
class Fiuze_Soapapi_Block_Adminhtml_Soapapi_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    /**
     * Prepare form action
     *
     * @return Fiuze_Soapapi_Block_Adminhtml_Container
     */
    public function __construct()
    {
		$this->_objectId = 'id';
        $this->_blockGroup = 'fiuze_soapapi';
        $this->_controller = 'adminhtml_soapapi';
		$this->_mode = 'edit';

        parent::__construct();
	
        $this->addButton(
            'import_file',
            array(
                'label'      => Mage::helper('fiuze_soapapi')->__('Import'),
                'onclick'    => 'importFile()',
            )
        );
		$this->removeButton('save');
		$this->removeButton('back');
		$this->removeButton('reset');

		//$this->setChild('form', $this->getLayout()->createBlock($this->_blockGroup . '/' / $this->_controller . '_' . $this->_mode . '_form'));

        $this->_formScripts[] = "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('page_content') == null) {
                    tinyMCE.execCommand('mceAddControl', false, 'page_content');
                } else {
                    tinyMCE.execCommand('mceRemoveControl', false, 'page_content');
                }
            }

            function importFile(){
                var htmlContent = '<div id=\"messages\"><ul class=\"messages\"><li class=\"message\"><ul><li>Please wait while this process is completed.</span></li></ul></li></ul></div>'
                if(jQuery('.wait-message').length == 0 && jQuery('#importfile').val().length != 0) {
                    jQuery('<p class=\'wait-message\'>Please wait ...</p>').insertBefore('.content-header');
                }
                editForm.submit();
            }
        ";
	}

    /**
     * Retrieve text for header element depending on loaded page
     *
     * @return string
     */
	public function getHeaderText()
	{	
		return Mage::helper('fiuze_soapapi')->__('Import File');
	}

}