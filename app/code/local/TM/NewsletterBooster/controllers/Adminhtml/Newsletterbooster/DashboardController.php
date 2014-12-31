<?php

class TM_NewsletterBooster_Adminhtml_Newsletterbooster_DashboardController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->_title($this->__('NewsletterBooster Dashboard'));

        $this->loadLayout();
        $this->_setActiveMenu('templates_master/newsletterbooster/dashboard');
        $this->_addBreadcrumb(
            Mage::helper('newsletterbooster')->__('NewsletterBooster Dashboard'),
            Mage::helper('newsletterbooster')->__('NewsletterBooster Dashboard')
        );
        $this->renderLayout();
    }

    /**
     * Gets most viewed products list
     *
     */
    public function productsViewedAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * Gets latest customers list
     *
     */
    public function customersNewestAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * Gets the list of most active customers
     *
     */
    public function customersMostAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function ajaxBlockAction()
    {
        $output   = '';
        $blockTab = $this->getRequest()->getParam('block');
        if (in_array($blockTab, array('tab_orders', 'tab_amounts', 'totals'))) {
            $output = $this->getLayout()->createBlock('adminhtml/dashboard_' . $blockTab)->toHtml();
        }
        $this->getResponse()->setBody($output);
        return;
    }

    public function tunnelAction()
    {
        $httpClient = new Varien_Http_Client();
        $gaData = $this->getRequest()->getParam('ga');
        $gaHash = $this->getRequest()->getParam('h');
        if ($gaData && $gaHash) {
            $newHash = Mage::helper('newsletterbooster/dashboard_data')->getChartDataHash($gaData);
            if ($newHash == $gaHash) {
                if ($params = unserialize(base64_decode(urldecode($gaData)))) {
                    $response = $httpClient->setUri(Mage_Adminhtml_Block_Dashboard_Graph::API_URL)
                            ->setParameterGet($params)
                            ->setConfig(array('timeout' => 5))
                            ->request('GET');

                    $headers = $response->getHeaders();

                    $this->getResponse()
                        ->setHeader('Content-type', $headers['Content-type'])
                        ->setBody($response->getBody());
                }
            }
        }
    }
}
