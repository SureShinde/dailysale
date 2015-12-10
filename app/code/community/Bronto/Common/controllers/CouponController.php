<?php

class Bronto_Common_CouponController extends Mage_Core_Controller_Front_Action
{
    const URL_PARAM = 'redirect_path';
    const MOVE_PERMANENTLY = 301;
    const HEADER_REFERER = 'referer';

    /**
     * Gets the redirected store url
     *
     * @return string
     */
    protected function _parseUrl()
    {
        $helper = Mage::helper('bronto_common/coupon');
        $request = $this->getRequest();
        $url = ltrim($request->getParam(self::URL_PARAM), '/');
        $allParams = $request->getParams();
        foreach ($helper->getParams() as $strippable) {
            unset($allParams[$strippable]);
        }
        unset($allParams[self::URL_PARAM]);
        $store = Mage::app()->getStore();
        return $store->getUrl($url, $allParams);
    }

    /**
     * Gets the referer url
     *
     * @return string
     */
    protected function _parseReferer()
    {
        return $this->getRequest()->getHeader(self::HEADER_REFERER);
    }

    /**
     * Gets parseable params to applicable route methods
     *
     * @return array
     */
    protected function _routes()
    {
        return array(
            self::URL_PARAM => '_parseUrl',
            Bronto_Common_Helper_Coupon::FORCE_PARAM => '_parseReferer'
        );
    }

    /**
     * Handle routes accordingly
     */
    public function indexAction()
    {
        $request = $this->getRequest();
        $helper = Mage::helper('bronto_common/coupon');
        // If enabled, and observer didn't catch it
        if ($helper->isEnabled() && !$helper->isObservingController()) {
            $helper->applyCodeFromRequest($request);
        }

        foreach ($this->_routes() as $param => $method) {
            if ($request->has($param)) {
                return $this->getResponse()
                    ->setRedirect($this->$method(), self::MOVE_PERMANENTLY)
                    ->sendHeaders();
            }
        }
        $this->_redirect('/');
    }
}
