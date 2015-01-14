<?php
/**
 * Data Helper
 *
 * @category   Fiuze
 * @package    Fiuze_Deals
 * @author     Alena Tsareva <alena.tsareva@webinse.com>
 */
class Fiuze_Deals_Controller_Router extends Mage_Core_Controller_Varien_Router_Abstract
{
    public function initControllerRouters($observer)
    {
        $front = $observer->getEvent()->getFront();
        $blowout = new Fiuze_Deals_Controller_Router();
        $front->addRouter('blowout', $blowout);
    }

    public function match(Zend_Controller_Request_Http $request)
    {
        if (!Mage::app()->isInstalled()) {
            Mage::app()->getFrontController()->getResponse()
                ->setRedirect(Mage::getUrl('install'))
                ->sendResponse();
            exit;
        }

        $helper = Mage::helper('fiuze_deals');
        $route = $helper->getRoute();

        /*  redirect if store was changed  */
        $helper->ifStoreChangedRedirect();

        $identifier = $request->getPathInfo();

        if (substr(str_replace("/", "", $identifier), 0, strlen($route)) != $route) {
            return false;
        }

        $identifier = substr_replace($request->getPathInfo(), '', 0, strlen("/" . $route . "/"));
        $identifier = str_replace('.html', '', $identifier);
        $identifier = str_replace('.htm', '', $identifier);

        if ($identifier == '') {
            $request->setModuleName('fiuze_deals')
                ->setControllerName('index')
                ->setActionName('index');
            return true;
        }

        return false;
    }
}