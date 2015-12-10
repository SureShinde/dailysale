<?php

/**
 * @package   Bronto\Common
 * @copyright 2011-2012 Bronto Software, Inc.
 */
class Bronto_Common_Helper_Field extends Bronto_Common_Helper_Data
{
    /**
     * @param string $name
     * @param array  $options
     *
     * @return Bronto_Api_Field_Row
     */
    public function getFieldByName($name, $options)
    {
        /* @var $fieldObject Bronto_Api_Field */
        $fieldObject = $this->getApi()->getFieldObject();

        if (!($field = $fieldObject->getFromCache($name))) {
            $field        = $fieldObject->createRow();
            $field->name  = $name;
            $field->label = $options['label'];
            $field->type  = $options['type'];
            if (!empty($options['options'])) {
                $field->options = $options['options'];
            }
            try {
                $field->save();
                $fieldObject->addToCache($name, $field);
            } catch (Exception $e) {
                $this->writeError($e);
            }
        }

        return $field;
    }
}
