<?php

/**
 * Copyright (c) 2015 S.L.I. Systems, Inc. (www.sli-systems.com) - All Rights
 * Reserved
 * This file is part of Learning Search Connect.
 * Learning Search Connect is distributed under a limited and restricted
 * license � please visit www.sli-systems.com/LSC for full license details.
 *
 * THIS CODE AND INFORMATION ARE PROVIDED "AS IS" WITHOUT WARRANTY OF ANY
 * KIND, EITHER EXPRESSED OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND/OR FITNESS FOR A
 * PARTICULAR PURPOSE. TO THE MAXIMUM EXTENT PERMITTED BY APPLICABLE LAW, IN NO
 * EVENT WILL SLI BE LIABLE TO YOU OR ANY OTHER PARTY FOR ANY GENERAL, DIRECT,
 * INDIRECT, SPECIAL, INCIDENTAL OR CONSEQUENTIAL LOSS OR DAMAGES OF ANY
 * CHARACTER ARISING OUT OF THE USE OF THE CODE AND/OR THE LICENSE INCLUDING
 * BUT NOT LIMITED TO PERSONAL INJURY, LOSS OF DATA, LOSS OF PROFITS, LOSS OF
 * ASSIGNMENTS, DATA OR OUTPUT FROM THE SERVICE BEING RENDERED INACCURATE,
 * FAILURE OF CODE, SERVER DOWN TIME, DAMAGES FOR LOSS OF GOODWILL, BUSINESS
 * INTERRUPTION, COMPUTER FAILURE OR MALFUNCTION, OR ANY AND ALL OTHER DAMAGES
 * OR LOSSES OF WHATEVER NATURE, EVEN IF SLI HAS BEEN INFORMED OF THE
 * POSSIBILITY OF SUCH DAMAGES.
 */

/**
 * Class XmlWriter
 *
 * @package    SLI
 * @subpackage Search
 */
class SLI_Search_Helper_XmlWriter extends \XMLWriter
{
    /**
     * Open a feed for writing.
     *
     * @param        $filename
     * @param string $startElement
     */
    public function openFeed($filename, $startElement = 'catalog')
    {
        $this->openUri($filename);
        $this->startDocument('1.0', 'UTF-8');
        $this->setIndent(true);
        $this->setIndentString(' ');
        // start catalog
        $this->startElement($startElement);
        @chmod($filename, 0666);
    }

    /**
     * Write a value of multiple types to a xml at a fixed level.
     *
     * @param mixed $value - array, bool or string
     * @param int   $level
     */
    public function writeValue($value, $level)
    {
        if (is_array($value)) {
            foreach ($value as $v) {
                $this->startElement('value_' . $level);
                $this->writeValue($v, $level + 1);
                $this->endElement();
            }
        } elseif (is_bool($value)) {
            $this->text($value ? 'true' : 'false');
        } elseif (is_string($value)) {
            $this->text($value);
        } elseif (is_numeric($value)) {
            $this->text($value);
        } else {
            // TODO deal with others.
        }
    }

    /**
     * Write a key value pair as XML.
     *
     * @param string $name
     * @param mixed  $value - array, bool or string.
     */
    public function writeNode($name, $value)
    {
        if (null === $value) {
            // no point writing an empty node
            return;
        }

        $this->startElement($name);
        $this->writeValue($value, 1);
        $this->endElement();
    }

    /**
     * @param array $attributes
     */
    public function writeAttributes(array $attributes)
    {
        foreach ($attributes as $attributeKey => $attributeValues) {
            $this->startElement('attribute');

            $this->startElement('key');
            $this->text($attributeKey);
            $this->endElement();

            foreach ($attributeValues as $attributeValueKey => $attributeValue) {
                $this->startElement('attributeValue');
                $this->startElement('key');
                $this->text($attributeValueKey);
                $this->endElement();

                $this->startElement('value');
                $this->text($attributeValue);
                $this->endElement();
                $this->endElement();
            }
            $this->endElement();
        }
    }

    /**
     *
     */
    public function closeFeed()
    {
        // main tag
        $this->endElement();

        $this->endDocument();
        $this->flush();
    }
}
