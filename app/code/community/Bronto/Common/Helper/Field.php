<?php

/**
 * @package   Bronto\Common
 * @copyright 2011-2012 Bronto Software, Inc.
 */
class Bronto_Common_Helper_Field extends Bronto_Common_Helper_Data
{
    private static $_fieldCache = array();

    /**
     * @param string $name
     * @param array  $options
     *
     * @return Bronto_Api_Field_Row
     */
    public function getFieldByName($name, $options)
    {
        /* @var $fieldObject Bronto_Api_Field */
        $fieldObject = $this->getApi()->transferField();

        if (!array_key_exists($name, self::$_fieldCache)) {
            $field = $fieldObject->getByName($name);
            if (!$field) {
                $field = $fieldObject->createObject()
                    ->withName($name)
                    ->withType($options['type']);
                if (!empty($options['options'])) {
                    $field->withOptions($options['options']);
                }
            }
            if (isset($options['label']) && !empty($options['label'])) {
                $field->withLabel($options['label']);
            }
            try {
                $fieldObject->save($field);
                self::$_fieldCache[$name] = $field;
            } catch (Exception $e) {
                $this->writeError($e);
            }
        }

        return self::$_fieldCache[$name];
    }
}
