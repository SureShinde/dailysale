<?php
class Fiuze_Bestsellercron_Model_Customcron  extends Mage_Core_Model_Abstract{
    public function runCustomCron(){
        Mage::log('_______runcron_condenser_______',null,'bestseller_cron.log');
        $collection = Mage::getResourceModel('bestsellercron/tasks_collection');
        foreach($collection as $task){
            $task_data=$task->getData();
            if(!$step_data=unserialize($task_data['step_timestamp'])){

                if(time()>=$task_data['current_timestamp']){
                    Mage::log('runcron_simple',null,'bestseller_cron.log');

                    Mage::getModel('bestsellercron/cron')->bestSellers($task->getTaskId());
                    $new_stamp = time() + $task->getStepTimestamp() - 5;
                    $task->setCurrentTimestamp($new_stamp)->save();
                }
            }else{

                if(time()>=$task_data['current_timestamp']){
                    Mage::log('runcron_config',null,'bestseller_cron.log');

                    Mage::getModel('bestsellercron/cron')->bestSellers($task->getTaskId());
                    if($step_data['current_cycle'] == count($step_data['cycle_data'])){
                        $new_stamp = time() + $step_data['cycle_data']['0'] - 5;
                        $step_data['current_cycle']=0;
                    } else{
                        $new_stamp = time() + $step_data['cycle_data'][$step_data['current_cycle']] - 5;
                        $step_data['current_cycle']++;
                    }
                    $task->setCurrentTimestamp($new_stamp)->setStepTimestamp(serialize($step_data))->save();
                    $r=$step_data;
                }
            }
        }
    }
}