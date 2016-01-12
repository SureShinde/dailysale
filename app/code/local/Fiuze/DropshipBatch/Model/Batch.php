<?php
/**
 * DropshipBatch
 *
 * @author      Fiuze Team
 * @category    Fiuze
 * @package     DropshipBatch
 * @copyright   Copyright (c) 2016 Fiuze
 */
class Fiuze_DropshipBatch_Model_Batch extends Unirgy_DropshipBatch_Model_Batch
{
    /**
     * @param string $type
     * @return Fiuze_DropshipBatch_Model_Batch
     */
    protected function _exportOrders($type='export_orders')
    {
        if (!$this->getData('rows_log') && !$this->getRowsText()) {
            $this->setData('batch_status', 'empty');
            $this->save();
            return $this;
        }

        $this->setData('batch_status', 'exporting');
        $this->save();
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
        /** @var $distCollection Unirgy_DropshipBatch_Model_Mysql4_Batch_Dist_Collection */
        $distCollection = $this->getDistsCollection();
        /** @var $helper Unirgy_Dropship_Helper_Data */
        $helper = Mage::helper('udropship');
        foreach ($distCollection as $d) {
            /** @var $d Unirgy_DropshipBatch_Model_Batch_Dist */
            try {
                $d->setData('dist_status', 'exporting');
                $d->save();
                $l = $d->getData('location');
                $l = str_replace('{TS}', date('YmdHis'), $l);
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
                            $helper->addToQueue($mail)->processQueue();
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
                        $helper->addToQueue($mail)->processQueue();
                    }
                } else {
                    /** @var $batchIoModel Unirgy_DropshipBatch_Model_Io */
                    $batchIoModel = Mage::getSingleton('udbatch/io');
                    if (!($ioAdapter = $batchIoModel->get($l, $this))) {
                        Mage::throwException($helper->__("Unsupported destination '%s'", $l));
                    }
                    /** @var $ioAdapter Unirgy_DropshipBatch_Model_Io_File */
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
                                $helper->__("Could not write to file '%s'", $_location)
                            );
                        }
                    }
                }
                $d->setData('dist_status', 'success');
                $d->save();
                $success = true;
            } catch (Exception $e) {
                $d->setData('dist_status', 'error');
                $d->setData('error_info', $e->getMessage());
                $d->save();
                $error = true;
            }
        }

        $this->setData('batch_status', $success && !$error ? 'success' : (!$success && $error ? 'error' : 'partial'));
        $this->save();

        Mage::dispatchEvent("udbatch_{$type}_dist_after", array('batch' => $this));

        try {
            $this->exportUpdatePOsStatus();
        } catch (Exception $e) {
            $this->setData('error_info', "$e");
            $this->setData('batch_status', 'error');
            $this->save();
        }

        return $this;
    }

    /**
     * @param string $type
     * @return Fiuze_DropshipBatch_Model_Batch
     */
    protected  function _importOrders($type = 'import_orders')
    {
        $this->setData('batch_status', 'importing');
        $this->save();
        $this->generateDists($type);
        $this->_getResource();

        $success = false;
        $error = false;
        $empty = true;
        /** @var $distCollection Unirgy_DropshipBatch_Model_Mysql4_Batch_Dist_Collection */
        $distCollection = $this->getDistsCollection();
        foreach ($distCollection as $d) {
            $oldRowsText = $this->getRowsText();
            try {
                $d->setDistStatus('importing')->save();
                $l = $d->getLocation();
                if ($io = Mage::getSingleton('udbatch/io')->get($l, $this)) {
                    $text = $io->read($io->getUdbatchGrep());
                } else {
                    $text = @file_get_contents($l);
                }
                if ($text===false || is_null($text)) {
                    Mage::throwException(Mage::helper('udropship')->__("Could not read from file '%s'", $l));
                }
                if ($text=='') {
                    $d->setDistStatus('empty')->save();
                    continue;
                }

                Mage::dispatchEvent("udbatch_{$type}_dist_after", array('batch'=>$this, 'dist'=>$d, 'vars'=>array('content'=>&$text)));

                $this->getAdapter()->import($text);

                if ($io) $this->_performFileAction($io, "batch_{$type}");

                $this->setRowsText((!empty($oldRowsText) ? $oldRowsText."\n" : $oldRowsText) . $text)->save();

                $d->setDistStatus('success')->setErrorInfo(null)->save();
                $success = true;
                $empty = false;
            } catch (Exception $e) {

                $this->setRowsText($oldRowsText);
                $d->setDistStatus('error')->setErrorInfo($e->getMessage())->save();
                $error = true;
                $empty = false;
            }
        }

        $status = $empty ? 'empty' : ($success && !$error ? 'success' : (!$success && $error ? 'error' : 'partial'));
        if (!$error) {
            $this->setErrorInfo(null);
        }
        $this->setBatchStatus($status)->save();

        $this->importUpdatePOsStatus();

        $this->importUpdateVendorTs();

        return $this;
    }
}
