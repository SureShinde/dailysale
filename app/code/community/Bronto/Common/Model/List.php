<?php 
class Bronto_Common_Model_List
{
    private $_helper;
    private $_path;

    public function __construct($params = array())
    {
        $settings = null;
        if (count($params) >= 2) {
            list($module, $settings) = $params;
        } else {
            $module = $params[0];
        }
        if (is_null($settings)) {
            $settings = 'settings';
        }
        $this->_path = "{$module}/{$settings}/exclusion";
        $this->_helper = Mage::helper($module);
    }

    /**
     * Gets an array of Bronto List ids for delivery exclusion
     *
     * @param string $scope
     * @param mixed $scopeId
     * @return array
     */
    public function getExclusionLists($scope = 'default', $scopeId = 0)
    {
        $listIds = $this->_helper->getAdminScopedConfig($this->_path, $scope, $scopeId);
        if (empty($listIds)) {
            return array();
        }
        if (is_string($listIds)) {
            return explode(',', $listIds);
        }
        return $listIds;
    }

    /**
     * @param mixed $storeId
     * @return array
     */
    public function addAdditionalRecipients($storeId)
    {
        $listIds = $this->getExclusionLists('store', $storeId);
        $recipients = array();
        if ($listIds) {
            $listObject = $this->_helper->getApi(null, 'store', $storeId)->getListObject();
            try {
                  $lists = $listObject->readAll(array('id' => $listIds));
                  foreach ($lists->iterate() as $list) {
                      $this->_helper->writeDebug("Excluding list: {$list->name} ({$list->id})");
                      $recipients[] = array(
                          'type' => 'list',
                          'id' => $list->id,
                          'deliveryType' => 'ineligible'
                      );
                  }
            } catch (Exception $e) {
                $this->_helper->writeError("Unable to add exclusion lists: " . $e->getMessage());
            }
        }
        return $recipients;
    }
}
