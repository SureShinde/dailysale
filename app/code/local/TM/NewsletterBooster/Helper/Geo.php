<?php
include_once("MaxMind/GeoIP/geoip.php");
include_once("MaxMind/GeoIP/geoipcity.php");
include_once("MaxMind/GeoIP/geoipregionvars.php");

class TM_NewsletterBooster_Helper_Geo extends Mage_Core_Helper_Abstract
{
    public function getGeoData($ip)
    {
        $result = array();
        if (!Mage::getStoreConfig('newsletterbooster/geo_ip/enabled')) {
            $result['country_code'] = NULL;
            $result['country_name'] = NULL;
            $result['city'] = NULL;

            return $result;
        }
        $geoipIncluded = true;

        if (!function_exists('geoip_open')) {
            $geoipIncluded = false;
        }

        $remoteAddr = $ip;

        if ($geoipIncluded && '' !== Mage::getStoreConfig('newsletterbooster/geo_ip/city_file')) {
            $filename = Mage::getBaseDir('lib')
                . DS
                . "MaxMind/GeoIP/data/"
                . Mage::getStoreConfig('newsletterbooster/geo_ip/city_file');

            if (is_readable($filename)) {
                $gi = geoip_open($filename, GEOIP_STANDARD);
                $record = geoip_record_by_addr($gi, $remoteAddr);
                $result['country_code'] = $record->country_code;
                $result['country_name'] = $record->country_name;
                $result['city'] = $record->city;
                geoip_close($gi);
            }
        }

        if ($geoipIncluded && '' !== Mage::getStoreConfig('newsletterbooster/geo_ip/region_file')) {
            $filename = Mage::getBaseDir('lib')
                . DS
                . "MaxMind/GeoIP/data/"
                . Mage::getStoreConfig('newsletterbooster/geo_ip/region_file');

            if (is_readable($filename)) {
                $gi = geoip_open($filename, GEOIP_STANDARD);
                //list($countryCode, $regionCode) = geoip_region_by_addr($gi, $remoteAddr);
                $record = geoip_region_by_addr($gi, $remoteAddr);
                $result['country_code'] = $record->country_code;
                $result['country_name'] = $record->country_name;
                $result['city'] = $record->city;

                geoip_close($gi);
            }
        }

        return $result;
    }
}
