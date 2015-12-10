<?php

/**
 * About Bronto block
 *
 * @category  Bronto
 * @package   Bronto_Verify
 * @author    Adam Daniels <adam.daniels@atlanticbt.com>
 * @copyright 2013 Adam Daniels
 * @license   http://www.atlanticbt.com/ Atlantic BT
 *
 */
class Bronto_Verify_Block_Adminhtml_System_Config_Installs
    extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{
    /**
     * Description for protected
     *
     * @var string
     * @access protected
     */
    protected $_module = 'bronto_verify';
    protected $_helper;

    /**
     * Array of displayable modules
     *
     * @var array
     */
    protected $_modules = array(
        'bronto_common',
        'bronto_newsletter',
        'bronto_customer',
        'bronto_order',
        'bronto_product',
        'bronto_reviews',
        'bronto_reminder',
        'bronto_email',
        'bronto_coupon',
        'bronto_api',
    );

    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('bronto/verify/installs.phtml');

        $this->_helper = Mage::helper('bronto_verify');
    }

    /**
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $html = $this->_getHeaderHtml($element);
        $html .= $this->toHtml();
        $html .= $this->_getFooterHtml($element);

        return $html;
    }

    /**
     * Get Style for Scope
     *
     * @param $scope
     *
     * @return mixed
     */
    protected function _getScopedStyle($scope)
    {
        $scopeParams = $this->_helper->getScopeParams();

        $styles = array(
            'default' => 'inactive',
            'website' => 'inactive',
            'group'   => 'inactive',
            'store'   => 'inactive',
        );

        switch ($scopeParams['scope']) {
            case 'store':
                $styles['store'] = 'active';
                break;
            case 'group':
                $styles['group'] = 'active';
                $styles['store'] = 'active';
                break;
            case 'website':
                $styles['website'] = 'active';
                $styles['group']   = 'active';
                $styles['store']   = 'active';
                break;
            case 'default':
            default:
                $styles['default'] = 'active';
                $styles['website'] = 'active';
                $styles['group']   = 'active';
                $styles['store']   = 'active';
                break;
        }

        return $styles[$scope];
    }


    /**
     * Get Multi-Dimensional array of all scopes to be included in current scope
     *
     * @return array
     */
    protected function _getScopeAllowedScopes()
    {
        $scopeParams = $this->_helper->getScopeParams();

        // Start Allowed array with Default Config
        $allowed = array(
            'default' => array(
                'label'    => 'Default Config',
                'websites' => array(),
            ),
        );

        // Identify Scope and Scope ID
        $scope   = $scopeParams['scope'];
        $scopeId = $scopeParams[$scope . '_id'];

        // Prep the models
        $store_model       = Mage::getModel('core/store'); //store model
        $store_group_model = Mage::getModel('core/store_group'); //store group model
        $website_model     = Mage::getModel('core/website'); //website model

        // Start Stepping Through
        switch ($scope) {
            case 'store':
                $store_data       = $store_model->load($scopeId);
                $store_group_data = $store_group_model->load($store_data->getGroupId());
                $website_data     = $website_model->load($store_data->getWebsiteId());

                $allowed['default']['websites'][$website_data->getCode()] = array(
                    'id'     => $website_data->getId(),
                    'label'  => $website_data->getName(),
                    'groups' => array(
                        $store_group_data->getName() => array(
                            'id'     => $store_group_data->getId(),
                            'label'  => $store_group_data->getName(),
                            'stores' => array(
                                $store_data->getCode() => array(
                                    'id'    => $store_data->getId(),
                                    'label' => $store_data->getName(),
                                ),
                            ),
                        ),
                    ),
                );

                break;
            case 'group':
                $store_group_data = $store_group_model->load($scopeId);
                $website_data     = $website_model->load($store_group_model->getWebsiteId());

                $stores_data = array();
                foreach ($store_group_data->getStores() as $store) {
                    $stores_data[$store->getCode()] = array(
                        'id'    => $store->getId(),
                        'label' => $store->getName(),
                    );
                }

                $allowed['default']['websites'][$website_data->getCode()] = array(
                    'id'     => $website_data->getId(),
                    'label'  => $website_data->getName(),
                    'groups' => array(
                        $store_group_data->getName() => array(
                            'id'     => $store_group_data->getId(),
                            'label'  => $store_group_data->getName(),
                            'stores' => $stores_data,
                        ),
                    ),
                );

                break;
            case 'website':
                $website_data = $website_model->load($scopeId);

                $groups_data = array();
                foreach ($website_data->getGroups() as $group) {
                    $stores_data = array();
                    foreach ($group->getStores() as $store) {
                        $stores_data[$store->getCode()] = array(
                            'id'    => $store->getId(),
                            'label' => $store->getName(),
                        );
                    }

                    $groups_data[$group->getName()] = array(
                        'id'     => $group->getId(),
                        'label'  => $group->getName(),
                        'stores' => $stores_data,
                    );
                }

                $allowed['default']['websites'][$website_data->getCode()] = array(
                    'id'     => $website_data->getId(),
                    'label'  => $website_data->getName(),
                    'groups' => $groups_data,
                );

                break;
            case 'default':
            default:
                $websites_data = array();
                foreach (Mage::app()->getWebsites() as $website) {
                    $groups_data = array();
                    foreach ($website->getGroups() as $group) {
                        $stores_data = array();
                        foreach ($group->getStores() as $store) {
                            $stores_data[$store->getCode()] = array(
                                'id'    => $store->getId(),
                                'label' => $store->getName(),
                            );
                        }

                        $groups_data[$group->getName()] = array(
                            'id'     => $group->getId(),
                            'label'  => $group->getName(),
                            'stores' => $stores_data,
                        );
                    }

                    $websites_data[$website->getCode()] = array(
                        'id'     => $website->getId(),
                        'label'  => $website->getName(),
                        'groups' => $groups_data,
                    );
                }

                $allowed['default']['websites'] = $websites_data;

                break;
        }

        return $allowed;
    }

    /**
     * Determine if module is enabled for specified scope
     *
     * @param string $module
     * @param string $scope
     * @param int    $scopeId
     *
     * @return string
     */
    public function getIsEnabled($module = 'bronto_common', $scope = 'default', $scopeId = 0)
    {
        $helper          = Mage::helper($module);
        $reflectionClass = new ReflectionClass(get_class($helper));
        $path            = $reflectionClass->getConstant('XML_PATH_ENABLED');

        return ((bool)$helper->getAdminScopedConfig($path, $scope, $scopeId)) ? 'enabled' : 'disabled';
    }

    /**
     * Get Ordered List of Modules and their status for the specified scope
     *
     * @param null   $store
     * @param null   $website
     * @param string $defaultSpacer
     * @param string $websiteSpacer
     * @param string $groupSpacer
     * @param string $storeSpacer
     *
     * @return string
     */
    public function getModulesStatus($store = null, $website = null, $defaultSpacer = '', $websiteSpacer = '', $groupSpacer = '', $storeSpacer = '')
    {
        // Build Scope
        if ($store) {
            $scope   = 'store';
            $scopeId = $store;
        } elseif ($website) {
            $scope   = 'website';
            $scopeId = $website;
        } else {
            $scope   = 'default';
            $scopeId = 0;
        }

        $html = '<ul class="bronto_verify-installs-modules">';

        $html .= '<li>';
        $html .= $defaultSpacer;
        $html .= $websiteSpacer;
        $html .= $groupSpacer;
        $html .= $storeSpacer;
        $html .= '<img src="' . $this->getJsUrl('spacer.gif') . '" class="x-tree-ec-icon x-tree-elbow-minus" onclick="toggleModules(this, \'modules-' . $scope . '-' . $scopeId . '\')">';
        $html .= '<span>Modules</span>';
        $html .= '<ol id="modules-' . $scope . '-' . $scopeId . '">';

        $modules = Mage::helper('bronto_common')->getInstalledModules(true);
        $mTotal  = count($modules);
        $mCount  = 1;
        foreach ($this->_modules as $module) {
            $section = $module;
            if ($module == 'bronto_api') {
                $module = 'bronto_common/api';
            } else if ($module == 'bronto_coupon') {
                $module = 'bronto_common/coupon';
            } else {
                if (!in_array($module, $modules)) {
                    continue;
                }
            }
            $mHelper = Mage::helper($module);
            if ($section == 'bronto_common') {
                $section = 'bronto';
            }
            $url       = $this->getConfigScopeUrl($section, $scope, $scopeId);
            $treeStyle = ($mCount == $mTotal) ? 'x-tree-elbow-end' : 'x-tree-elbow';

            $html .= '<li>';
            $html .= $defaultSpacer;
            $html .= $websiteSpacer;
            $html .= $groupSpacer;
            $html .= $storeSpacer;

            $html .= '<img src="' . $this->getJsUrl('spacer.gif') . '" class="x-tree-ec-icon ' . $treeStyle . '">';
            $status = $this->getIsEnabled($module, $scope, $scopeId);
            //            $html .= '<img src="' . $this->getSkinUrl('bronto/images/InstallStatus' . ucfirst($status) . '.png') . '" title="' . ucfirst($status) . '" class="install-status-icon" /> ';
            $html .= '<img src="' . $this->getJsUrl('spacer.gif') . '" class="installed-module-' . $status . '" title="' . ucfirst($status) . '" /> ';
            $html .= '<a href="' . $url . '" class="installed-module-' . $status . '">' . $mHelper->getName() . '</a>';
            $html .= '</li>';

            $mCount++;
        }

        $html .= '</ol>';
        $html .= '</li>';
        $html .= '</ul>';

        return $html;
    }

    /**
     * Get Scoped URL to specified System Config Section
     *
     * @param $section
     * @param $scope
     * @param $scopeId
     *
     * @return mixed
     */
    public function getConfigScopeUrl($section, $scope, $scopeId)
    {
        return Mage::helper('bronto_common')->getScopeUrl('/system_config/edit/', array('section' => $section, 'scope' => $scope, $scope => $scopeId));
    }
}
