<?php

/**
 * @package   Bronto\Common
 * @copyright 2011-2012 Bronto Software, Inc.
 */
interface Bronto_Common_Helper_DataInterface
{
    /**
     * Disable the module in the admin configuration
     *
     * @param string $scope
     * @param int    $scopeId
     * @param bool   $deleteConfig
     *
     * @return mixed
     */
    public function disableModule($scope = 'default', $scopeId = 0, $deleteConfig = false);
}
