<?php

class TM_SegmentationSuite_Block_Adminhtml_Segments_Edit
    extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();

        $this->_objectId   = 'id';
        $this->_blockGroup = 'segmentationsuite';
        $this->_controller = 'adminhtml_segments';
        $this->_removeButton('save');
        $objId = $this->getRequest()->getParam($this->_objectId);
        $this->_updateButton(
            'delete',
            'label',
            Mage::helper('segmentationsuite')->__('Delete Segment')
        );
        $this->_addButton('saveandcontinue', array(
            'label'   => Mage::helper('segmentationsuite')->__('Save And Continue'),
            'onclick' => 'saveAndContinueEdit()',
            'class'   => 'save',
        ), -100);

        $this->_formScripts[] = "
            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
        ";
        if (!empty($objId)) {
            $url = Mage::helper("adminhtml")->getUrl("*/*/indexSegment");
            $urlSuccess = Mage::helper("adminhtml")->getUrl("*/*/edit", array('id' => $objId));
            $this->_addButton('reindex', array(
                'label'     => Mage::helper('segmentationsuite')->__("Index Segment"),
                'class'   => 'save',
                'onclick'   => "
                function sendRequest(clearSession) {
                    var params = $('edit_form').serialize(true);
                    params['clear_session'] = clearSession;
                    new Ajax.Request('".$url."', {
                        method: 'post',
                        parameters: params,
                        onSuccess: showResponse
                    });
                }
                function showResponse(response) {
                    var response = response.responseText.evalJSON();
                    if (!response.completed) {
                        sendRequest(0);
                        var imageSrc = $('loading_mask_loader').select('img')[0].src;
                        $('loading_mask_loader').innerHTML = '<img src=\'' + imageSrc + '\'/><br/>' + response.message;
                    } else {
                        window.location = '" . $urlSuccess . "'
                    }
                }
                sendRequest(1);",
            ));
        } else {
            $this->_removeButton('delete');
        }
    }

    public function getHeaderText()
    {
        if (Mage::registry('segmentationsuite_segments') && Mage::registry('segmentationsuite_segments')->getId()) {
            return Mage::helper('segmentationsuite')->__("Edit Segment '%s'", $this->htmlEscape(Mage::registry('segmentationsuite_segments')->getSegmentTitle()));
        } else {
            return Mage::helper('segmentationsuite')->__('Add Segment');
        }
    }

}
