<?php
/**
 * MageWorx
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MageWorx EULA that is bundled with
 * this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.mageworx.com/LICENSE-1.0.html
 *
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@mageworx.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the extension
 * to newer versions in the future. If you wish to customize the extension
 * for your needs please refer to http://www.mageworx.com/ for more information
 * or send an email to sales@mageworx.com
 *
 * @category   MageWorx
 * @package    MageWorx_CustomerCredit
 * @copyright  Copyright (c) 2013 MageWorx (http://www.mageworx.com/)
 * @license    http://www.mageworx.com/LICENSE-1.0.html
 */

/**
 * Customer Credit extension
 *
 * @category   MageWorx
 * @package    MageWorx_CustomerCredit
 * @author     MageWorx Dev Team <dev@mageworx.com>
 */
$installer = $this;
$installer->startSetup();
$BD = Mage::getBaseDir();
$xmlFile = $BD . "/app/code/local/MageWorx/CustomerCredit/etc/config.xml";
if(!file_exists($xmlFile)) return true;
$xml = simplexml_load_file($xmlFile);
$locale = "en_US";
foreach ((array)$xml->global->template->email as $templateCode=>$node) {
    $template = Mage::getModel('adminhtml/email_template');
    $template->loadDefault($templateCode, $locale);
    try {
        $template->setId(NULL)
            ->setTemplateCode((string)$node->label)
            ->setModifiedAt(Mage::getSingleton('core/date')->gmtDate())
            ->setOrigTemplateCode($templateCode)
            ->setAddedAt(Mage::getSingleton('core/date')->gmtDate());
        $template->save();
    }
    catch (Exception $e) {
        Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
    }
}
$installer->endSetup();