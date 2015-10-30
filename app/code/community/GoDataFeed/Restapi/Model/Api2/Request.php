<?php

/* API2 Request REST API
 *
 * @category GoDataFeed
 * @package GoDataFeed_Restapi
 */

class GoDataFeed_Restapi_Model_Api2_Request extends Mage_Api2_Model_Request {
 
    //function used to convert default response into JSON.
    public function getAcceptTypes() {
        $qualityToTypes = array();
        $orderedTypes = array();
        foreach (preg_split('/,\s*/', $this->getHeader('Accept')) as $definition) {
            $typeWithQ = explode(';', $definition);
            $mimeType = trim(array_shift($typeWithQ));
            // check MIME type validity
            if (!preg_match('~^([0-9a-z*+\-]+)(?:/([0-9a-z*+\-\.]+))?$~i', $mimeType)) {
                continue;
            }
            $quality = '1.0'; // default value for quality
            if ($typeWithQ) {
                $qAndValue = explode('=', $typeWithQ[0]);
                if (2 == count($qAndValue)) {
                    $quality = $qAndValue[1];
                }
            }
            $qualityToTypes[$quality][$mimeType] = true;
        }
        krsort($qualityToTypes);
        foreach ($qualityToTypes as $typeList) {
            $orderedTypes += $typeList;
        }
        return array_keys(array("application/json" => 1));
    }

}

?>