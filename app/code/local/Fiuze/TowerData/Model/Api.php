<?php

/**
 * Cron
 *
 * @category    Fiuze
 * @package     Fiuze_Deals
 * @author      Webinse Team <info@webinse.com>
 */
require_once BP.DS.'lib'.DS.'Fiuze'.DS.'TowerDataAPI.php';

class Fiuze_TowerData_Model_Api extends Mage_Core_Model_Abstract{

    public function __construct(){
        parent::__construct();
        $this->setApi(new TowerDataAPI());
    }

    public function callApiMail($email){
        $result = array('success' => false, 'status_desc' => false);
        try
        {
            $response = $this->getApi()->callApi(false, false, $email);
            $email = $response->email;
            if($email->status_code == 45){
                $result['success'] = false;
                $result['status_desc'] = $email->status_desc;
            }else
            if ($email->ok) {
                $result['success'] = true;
                $result['status_desc'] = $email->status_desc;
            }else{
                $result['success'] = false;
                $result['status_desc'] = $email->status_desc;
            }
        }
        catch (Exception $e)
        {
            $result['error'] = $e->getMessage();
        }
        return $result;
    }
}























