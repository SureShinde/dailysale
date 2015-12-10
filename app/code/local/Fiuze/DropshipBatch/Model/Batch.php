<?php
/**
 * Unirgy LLC
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.unirgy.com/LICENSE-M1.txt
 *
 * @category   Unirgy
 * @package    Unirgy_Dropship
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

class Fiuze_DropshipBatch_Model_Batch extends Unirgy_DropshipPo_Model_Batch
{
    protected function _exportOrders($type='export_orders')
    {
        if (!$this->getRowsLog() && !$this->getRowsText()) {
            $this->setBatchStatus('empty')->save();
            return $this;
        }

        $this->setBatchStatus('exporting')->save();

        $this->generateDists($type);

        $defaultEmailSender = Mage::getStoreConfig('udropship/batch/default_email_sender');
        $defaultEmailSubject = Mage::getStoreConfig('udropship/batch/default_email_subject');
        $defaultEmailBody = Mage::getStoreConfig('udropship/batch/default_email_body');
        $defaultExportOrdersFilename = Mage::getStoreConfig("udropship/batch/default_{$type}_filename");

        if ($this->getUseWildcard()) {
            $contentArr = (array)$this->getPerPoRowsText();
            $header = '';
            if (!empty($contentArr['header'])) {
                $header = $contentArr['header'];
            }
            unset($contentArr['header']);
        } else {
            $contentArr = array((string)$this->getRowsText());
        }

        $success = false;
        $error = false;
        foreach ($this->getDistsCollection() as $d) {
            try {
                $d->setDistStatus('exporting')->save();
                $l = $d->getLocation();
                $l = str_replace('{TS}', Mage::app()->getLocale()->storeDate(null, null, true, null)->toString('yyyyMMddHHmmss'), $l);
                if (preg_match('#^mailto:([^?]+)(.*)$#', $l, $m)) {
                    if ($m[2] && $m[2][0]=='?') {
                        $m[2] = substr($m[2], 1);
                    }
                    parse_str($m[2], $p);
                    $filename = isset($p['filename']) ? $p['filename'] : $defaultExportOrdersFilename;
                    $mFrom = isset($p['from']) ? $p['from'] : $defaultEmailSender;
                    $mSubject = isset($p['subject']) ? $p['subject'] : $defaultEmailSubject;
                    $mBody = isset($p['body']) ? $p['body'] : $defaultEmailBody;
                    if ($filename==='' || $filename==='-') {
                        foreach ($contentArr as $poId => $content) {
                            $content = !empty($header) ? $header."\n".$content : $content;
                            Mage::dispatchEvent(
                                "udbatch_{$type}_dist_before",
                                array('batch'=>$this, 'vars'=>array('content'=>&$content))
                            );
                            $mail = new Zend_Mail('utf-8');
                            $mail->addTo($m[1]);
                            $mail->setFrom($mFrom);
                            $mail->setSubject(str_replace('[po_id]', $poId, $mSubject));
                            if (isset($p['cc'])) {
                                foreach ((array)$p['cc'] as $cc) {
                                    $mail->addCc($cc);
                                }
                            }
                            if (isset($p['bcc'])) {
                                foreach ((array)$p['bcc'] as $cc) {
                                    $mail->addBcc($cc);
                                }
                            }
                            $mail->setBodyText($content);
                            Mage::helper('udropship')->addToQueue($mail)->processQueue();
                        }
                    } else {
                        $mail = new Zend_Mail('utf-8');
                        $mail->addTo($m[1]);
                        $mail->setFrom($mFrom);
                        $mail->setSubject(str_replace('[po_ids]', implode(',', array_keys($contentArr)), $mSubject));
                        if (isset($p['cc'])) {
                            foreach ((array)$p['cc'] as $cc) {
                                $mail->addCc($cc);
                            }
                        }
                        if (isset($p['bcc'])) {
                            foreach ((array)$p['bcc'] as $cc) {
                                $mail->addBcc($cc);
                            }
                        }
                        $mail->setBodyText(str_replace('[po_ids]', implode(',', array_keys($contentArr)), $mBody));
                        foreach ($contentArr as $poId => $content) {
                            $content = !empty($header) ? $header."\n".$content : $content;
                            Mage::dispatchEvent(
                                "udbatch_{$type}_dist_before",
                                array('batch'=>$this, 'vars'=>array('content'=>&$content))
                            );
                            $mail->createAttachment($content, Zend_Mime::TYPE_TEXT, Zend_Mime::DISPOSITION_ATTACHMENT, Zend_Mime::ENCODING_BASE64, $this->generatePoFilename($filename, $poId));
                        }
                        Mage::helper('udropship')->addToQueue($mail)->processQueue();
                    }
                } else {
                    if (!($ioAdapter = Mage::getSingleton('udbatch/io')->get($l, $this))) {
                        Mage::throwException(Mage::helper('udbatch')->__("Unsupported destination '%s'", $l));
                    }
                    foreach ($contentArr as $poId => $content) {
                        $content = !empty($header) ? $header."\n".$content : $content;
                        Mage::dispatchEvent(
                            "udbatch_{$type}_dist_before",
                            array('batch'=>$this, 'vars'=>array('content'=>&$content))
                        );
                        $filename = $ioAdapter->getUdbatchGrep() ? $ioAdapter->getUdbatchGrep() : $defaultExportOrdersFilename;
                        $_filename = $this->generatePoFilename($filename, $poId);
                        if (!$ioAdapter->write($_filename, $content)) {
                            $_location = $ioAdapter->createLocationString($_filename, true);
                            Mage::throwException(
                                Mage::helper('udbatch')->__("Could not write to file '%s'", $_location)
                            );
                        }
                    }
                }
                $d->setDistStatus('success')->save();
                $success = true;
            } catch (Exception $e) {
                $d->setDistStatus('error')->setErrorInfo($e->getMessage())->save();
                $error = true;
            }
        }

        $this->setBatchStatus($success && !$error ? 'success' : (!$success && $error ? 'error' : 'partial'))->save();

        Mage::dispatchEvent("udbatch_{$type}_dist_after", array('batch'=>$this));

        try {
            $this->exportUpdatePOsStatus();
        } catch (Exception $e) {
            $this->setErrorInfo("$e")->setBatchStatus('error')->save();
        }

        return $this;
    }
}