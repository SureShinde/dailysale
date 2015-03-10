<?php
/**
 * Created by PhpStorm.
 * User: dorik
 * Date: 05.03.15
 * Time: 17:54
 */
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

try {
    $model = Mage::getModel('adminhtml/email_template');
    $model->loadDefault('fiuze_notifylowstock_template', Mage::app()->getLocale()->getLocaleCode());

    $template = Mage::getModel('adminhtml/email_template')
        ->load(null);
    $template->setTemplateSubject('Fiuze_Notifylowstock')
        ->setTemplateCode('Notify Low Stock (default)')
        ->setTemplateText($model->getTemplateText())
        ->setTemplateStyles('')
        ->setModifiedAt(Mage::getSingleton('core/date')->gmtDate())
        ->setAddedAt(Mage::getSingleton('core/date')->gmtDate())
        ->setTemplateType(Mage_Core_Model_Email_Template::TYPE_HTML)
        ->setData('template_variables', Zend_Json::encode($template->getVariablesOptionArray(true)));

    $template->save();
} catch (Exception $ex) {
    Mage::logException($ex);
}


if (Mage::getStoreConfig('bronto_email/settings/enabled')) {
    $templateModel = Mage::getModel('bronto_email/template');
    // Process Templates
    try {
        $templateModel->handleDefaultTemplates();
    } catch (Exception $e) {
        Mage::helper('bronto_email')->writeError($e->getMessage());
    }
}


$installer->endSetup();