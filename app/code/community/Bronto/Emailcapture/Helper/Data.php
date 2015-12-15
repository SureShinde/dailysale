<?php

/**
 * @package   Bronto_Emailcapture
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Emailcapture_Helper_Data extends Bronto_Reminder_Helper_Data
{
    const XML_PATH_COOKIE_TTL     = 'bronto_reminder/settings/cookie_ttl';
    const XML_PATH_FIELD_SELECTOR = 'bronto_reminder/settings/field_selector';

    /**
     * Module Human Readable Name
     */
    protected $_name = 'Bronto Email Capture';

    /**
     * Get Human Readable Name
     *
     * @return string
     */
    public function getName()
    {
        return $this->__($this->_name);
    }

    /**
     * Get Cookie TTL Config Value
     *
     * @param boolean $converted
     *
     * @return mixed
     */
    public function getCookieTtl($converted = true)
    {
        $days = $this->getAdminScopedConfig(self::XML_PATH_COOKIE_TTL);

        // Convert never expire to ~10 years worth of days
        if ('-1' == $days) {
            $days = 3650;
        }

        // Convert days to seconds if desired
        if ($converted) {
            $days = $days * 86400;
        }

        // Return ttl
        return $days;
    }

    /**
     * Get CSS Selector for Email Capture Fields
     *
     * @return mixed
     */
    public function getFieldSelector()
    {
        return $this->getAdminScopedConfig(self::XML_PATH_FIELD_SELECTOR);
    }
}