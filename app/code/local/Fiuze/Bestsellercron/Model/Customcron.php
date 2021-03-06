<?php
class Fiuze_Bestsellercron_Model_Customcron  extends Mage_Core_Model_Abstract{
    public function runCustomCron(){
        $collection = Mage::getResourceModel('bestsellercron/tasks_collection');
        $current_data = getdate(Mage::getModel('core/date')->timestamp(time()));
        if(strlen($current_data['minutes'])==1){
            $current_data['minutes']='0'.$current_data['minutes'];
        }
        foreach($collection as $task){
            $task_data=$task->getData();
            $step_data = $task_data['step_timestamp'];
            if(!empty($step_data)){
                $cronName = Mage::getModel('bestsellercron/tasks')
                    ->getCollection()
                    ->addFieldToFilter('task_id', $task->getTaskId())
                    ->getFirstItem()
                    ->getData('cronname');

                if(time()>=$task_data['current_timestamp']){
                    Mage::getModel('bestsellercron/taskLogs')
                        ->setDate(date('m/d/Y',$current_data[0]))
                        ->setTime(date("h:i A", $current_data[0]))
                        ->setType('Simple')
                        ->setInternalId($task->getTaskId())
                        ->setCronname($cronName)
                        ->save();

                    Mage::getModel('bestsellercron/cron')->bestSellers($task->getTaskId());
                    $new_stamp = time() + $task->getStepTimestamp() ;
                    $task->setCurrentTimestamp($new_stamp)->save();
                }
            }else{
                if(time()>=$task_data['current_timestamp']){
                    Mage::getModel('bestsellercron/taskLogs')
                        ->setDate(date('m/d/Y',$current_data[0]))
                        ->setTime(date("h:i A", $current_data[0]))
                        ->setType('Configurable')
                        ->setInternalId($task->getTaskId())
                        ->setCronname($cronName)
                        ->save();

                    Mage::getModel('bestsellercron/cron')->bestSellers($task->getTaskId());
                    if($step_data['current_cycle'] == count($step_data['cycle_data'])){
                        $new_stamp = time() + $step_data['cycle_data']['0'] ;
                        $step_data['current_cycle']=0;
                    } else{
                        $new_stamp = time() + $step_data['cycle_data'][$step_data['current_cycle']] ;
                        $step_data['current_cycle']++;
                    }
                    $task->setCurrentTimestamp($new_stamp)->setStepTimestamp(serialize($step_data))->save();
                    $r=$step_data;
                }
            }
        }
    }
}