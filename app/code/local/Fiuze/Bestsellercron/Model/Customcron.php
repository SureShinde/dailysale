<?php
class Fiuze_Bestsellercron_Model_Customcron  extends Mage_Core_Model_Abstract{
    public function runCustomCron(){
        $collection = Mage::getResourceModel('bestsellercron/tasks_collection');
        foreach($collection as $task){
            if(time()>$task->getCurrentTimestamp()){
                Mage::getModel('bestsellercron/cron')->bestSellers($task->getTaskId());
                $new_stamp = $task->getCurrentTimestamp() + $task->getStepTimestamp();
                $task->setCurrentTimestamp($new_stamp)->save();
            }
        }
    }
}