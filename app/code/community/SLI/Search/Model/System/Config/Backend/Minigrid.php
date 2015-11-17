<?php

/**
 * Copyright (c) 2015 S.L.I. Systems, Inc. (www.sli-systems.com) - All Rights
 * Reserved
 * This file is part of Learning Search Connect.
 * Learning Search Connect is distributed under a limited and restricted
 * license - please visit www.sli-systems.com/LSC for full license details.
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
 * Minigrid backend model
 * Serializes and unserializes the grid data to
 * the config data
 *
 * @package    SLI
 * @subpackage Search
 */
class SLI_Search_Model_System_Config_Backend_Minigrid extends Mage_Core_Model_Config_Data
{
    /**
     * In the event of a minigrid with file, get the community tmp location of the
     * image file that was uploaded by the font minigrid
     *
     * @return string|false
     */
    protected function _getTmpFileNames()
    {
        if (isset($_FILES['groups']['tmp_name']) && is_array($_FILES['groups']['tmp_name'])) {
            if (isset($_FILES['groups']['tmp_name']["{$this->getGroupId()}"])) {
                $field = $_FILES['groups']['tmp_name']["{$this->getGroupId()}"]['fields'][$this->getField()];
                if (isset($field['value'])) {
                    return $field['value'];
                }
            }
        }
        return false;
    }

    /**
     * In the event that a file was uploaded,
     * this array will contain the filenames as they appear
     * on the uploaded file.
     *
     * @return array
     */
    protected function _getFileNames()
    {
        $groups = $this->getData('groups');
        $values = $groups["{$this->getGroupId()}"]['fields'][$this->getField()]['value'];

        return $values;
    }

    /**
     * Serialize
     */
    protected function _beforeSave()
    {
        parent::_beforeSave();
        $groups = $this->getData('groups');
        $values = $groups["{$this->getGroupId()}"]['fields'][$this->getField()]['value'];

        if (is_array($values)) {
            $this->setValue(serialize(array_values($values)));
        } else {
            $this->setValue(serialize($values));
        }
    }

    /**
     * Unserialize
     */
    protected function _afterLoad()
    {
        parent::_afterLoad();
        $this->setValue(unserialize($this->getValue()));
    }
}
