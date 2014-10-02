<?php

class TM_SegmentationSuite_Block_Adminhtml_Segments extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_controller = 'adminhtml_segments';
        $this->_blockGroup = 'segmentationsuite';
        $this->_headerText = Mage::helper('segmentationsuite')->__('Manage Customer Segments');
        $this->_addButtonLabel = Mage::helper('segmentationsuite')->__('Create Segment');

        parent::__construct();

        $url = Mage::helper("adminhtml")->getUrl("*/*/applyRules");
        $urlSuccess = Mage::helper("adminhtml")->getUrl("*/*/*");
        $this->_addButton('reindex', array(
            'label'     => Mage::helper('segmentationsuite')->__('Index Segments'),
            'onclick'   => "
            function sendRequest(clearSession) {
                new Ajax.Request('".$url."', {
                    method: 'post',
                    parameters: {
                        clear_session: clearSession
                    },
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
            sendRequest(1);
                            ",
        ));
    }
}