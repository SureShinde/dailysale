<?php

class Unirgy_Rma_Block_Adminhtml_Rma_View_Tracking extends Mage_Adminhtml_Block_Template
{
    protected function _prepareLayout()
    {
        $onclick = "rmaSubmitAndReloadArea($('rma_tracking_info').parentNode, $('rma_tracking_info'), '".$this->getSubmitUrl()."')";
        $this->setChild('save_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label'   => Mage::helper('udropship')->__('Add'),
                    'class'   => 'save',
                    'onclick' => $onclick
                ))
        );
        $onclick = "rmaSubmitAndReloadArea($('rma_label_form').parentNode, $('rma_label_form'), '".$this->getGenerateUrl()."')";
        $this->setChild('generate_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label'   => Mage::helper('udropship')->__('Generate'),
                    'class'   => 'save',
                    'onclick' => $onclick
                ))
        );
    }

    public function getRma()
    {
        return Mage::registry('current_rma');
    }

    /**
     * Retrieve save url
     *
     * @return string
     */
    public function getSubmitUrl()
    {
        return $this->getUrl('adminhtml/urmaadmin_order_rma/addTrack/', array('rma_id'=>$this->getRma()->getId()));
    }

    public function getGenerateUrl()
    {
        return $this->getUrl('adminhtml/urmaadmin_order_rma/createLabel/', array('rma_id'=>$this->getRma()->getId()));
    }

    /**
     * Retrive save button html
     *
     * @return string
     */
    public function getSaveButtonHtml()
    {
        return $this->getChildHtml('save_button');
    }
    public function getGenerateButtonHtml()
    {
        return $this->getChildHtml('generate_button');
    }

    /**
     * Retrieve remove url
     *
     * @return string
     */
    public function getRemoveUrl($track)
    {
        return $this->getUrl('adminhtml/urmaadmin_order_rma/removeTrack/', array(
            'rma_id' => $this->getRma()->getId(),
            'track_id' => $track->getId()
        ));
    }

    /**
     * Retrieve remove url
     *
     * @return string
     */
    public function getTrackInfoUrl($track)
    {
        return $this->getUrl('adminhtml/urmaadmin_order_rma/viewTrack/', array(
            'rma_id' => $this->getRma()->getId(),
            'track_id' => $track->getId()
        ));
    }

    /**
     * Retrieve
     *
     * @return unknown
     */
    public function getCarriers()
    {
        $carriers = array();
        $carrierInstances = Mage::getSingleton('shipping/config')->getAllCarriers(
            $this->getRma()->getStoreId()
        );
        $carriers['custom'] = Mage::helper('udropship')->__('Custom Value');
        foreach ($carrierInstances as $code => $carrier) {
            if ($carrier->isTrackingAvailable()) {
                $carriers[$code] = $carrier->getConfigData('title');
            }
        }
        return $carriers;
    }

    public function getCarrierTitle($code)
    {
        if ($carrier = Mage::getSingleton('shipping/config')->getCarrierInstance($code)) {
            return $carrier->getConfigData('title');
        }
        else {
            return Mage::helper('udropship')->__('Custom Value');
        }
        return false;
    }
}
