<?php
class TM_NewsletterBooster_Block_Adminhtml_View extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {

        $this->_controller = 'adminhtml_view';
        $this->_blockGroup = 'newsletterbooster';
        $this->_headerText = Mage::helper('newsletterbooster')->__('Subscribers');

        parent::__construct();
        $this->_removeButton('add');

        $url = $this->getUrl('*/*/importGuest');
        $campaignId = $this->getRequest()->getParam('id');
        $params = array(
            '_query' => array(
                'id'  => $campaignId
            )
        );
        $this->_addButton('refresh_data', array(
            'label'     => Mage::helper('newsletterbooster')->__('Import Default Newsletter Guests'),
            'onclick'   =>  "
            function sendRequest(clearSession) {
                new Ajax.Request('".$url."', {
                    method: 'post',
                    parameters: {
                        clear_session: clearSession,
                        campaign:".$campaignId."
                    },
                    onSuccess: showResponse
                    });
                }

            function showResponse(response) {
                var response = response.responseText.evalJSON();

                if (!response.completed) {
                    sendRequest(0);
                } else {
                    window.location = '" . $this->getUrl('*/*/index', $params) . "'
                }
            }
            sendRequest(1);
                            "
        ));
    }
}