<?php
/**
 * Namespace_ExtensionName_Folder_FileName
 *
 * PHP Version 5.5.9
 *
 * @category  Namespace
 * @package   Namespace_ExtensionName
 * @author    Webinse Team <info@webinse.com>
 * @copyright 2015 Webinse Ltd. (https://www.webinse.com)
 * @license   The Open Software License 3.0
 * @link      http://opensource.org/licenses/OSL-3.0
 *
 */
/**
 * Namespace_ExtensionName_Folder_FileName.
 *
 * Description of this file.
 *
 * @category  Namespace
 * @package   Namespace_ExtensionName
 * @author    Webinse Team <info@webinse.com>
 * @copyright 2015 Webinse Ltd. (https://www.webinse.com)
 * @license   The Open Software License 3.0
 * @link      http://opensource.org/licenses/OSL-3.0
 *
 */
require_once 'Unirgy/DropshipPo/controllers/VendorController.php';


class Fiuze_Dashboard_VendorController extends Unirgy_DropshipPo_VendorController {
    public function dashAction(){
        $this->_renderPage(null, 'dash');
    }

}