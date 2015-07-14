<?php
/**
 * Full Page Cache
 *
 * @author      ahofs
 * @category    Fiuze
 * @package     Fpc
 * @copyright   Copyright (c) 2015 Fiuze
 */
class Fiuze_Fpc_Helper_Data extends Mirasvit_Fpc_Helper_Data
{
    const XML_PATH_IGNORE_PARAMS = 'fpc/cache_rules/ignore_params';

    /**
     * merge configurable ignore params into the class property
     */
    public function __construct()
    {
        $ignoreParams = Mage::getStoreConfig(self::XML_PATH_IGNORE_PARAMS);
        if (empty($ignoreParams)) {
            return;
        }
        $paramsArray = explode(',', $ignoreParams);
        $paramsArray = array_map('trim', $paramsArray);
        $ignoredUrlParams = array_filter(array_unique(array_merge($paramsArray, $this->_ignoredUrlParams)));
        $this->_ignoredUrlParams = $ignoredUrlParams;
    }
}
