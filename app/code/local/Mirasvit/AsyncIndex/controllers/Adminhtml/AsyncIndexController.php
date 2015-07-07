<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at http://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   Fast Asynchronous Re-indexing
 * @version   1.1.6
 * @build     363
 * @copyright Copyright (C) 2015 Mirasvit (http://mirasvit.com/)
 */


/**
 * @category Mirasvit
 * @package  Mirasvit_AsyncIndex
 */
class Mirasvit_AsyncIndex_Adminhtml_AsyncIndexController extends Mage_Adminhtml_Controller_Action
{
    public function processAction()
    {
        $control = Mage::getModel('asyncindex/control');
        $control->run();

        $this->_redirect('*/process/list');
    }

    public function clearAction()
    {
        $resource = Mage::getSingleton('core/resource');
        $connection = $resource->getConnection('core_write');

        $connection->delete($resource->getTableName('index/event'));
        $connection->delete($resource->getTableName('mstcore/logger'));

        $this->_getSession()->addSuccess(
            Mage::helper('index')->__('Queue is cleared. Please run full reindex.')
        );

        $this->_redirect('*/process/list');
    }

    public function stateAction()
    {
        $html = Mage::app()->getLayout()->createBlock('asyncindex/adminhtml_panel_stream')
            ->setIsDeveloper($this->getRequest()->getParam('is_developer'))
            ->toHtml();

        $this->getResponse()->setBody($html);
    }
}