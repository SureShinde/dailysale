<?php

class Bronto_News_RouteController extends Mage_Core_Controller_Front_Action
{

    /**
     * Open announcement redirect by item id
     * Example: announcement/route/index/item/id
     */
    public function indexAction()
    {
        $id = $this->getRequest()->getParam('item', 0);

        if ($id) {
            $item = Mage::getModel('bronto_news/item')->load($id, 'item_id');
            if ($item->hasTitle()) {
                $link = $item->markAlertAsRead()->getLink();

                return $this->_redirectUrl($link);
            }
        }

        return $this->_redirectUrl('/');
    }
}
