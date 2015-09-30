<?php
class Fiuze_Bestsellercron_Model_System_Config_Backend_General extends Mage_Adminhtml_Model_System_Config_Backend_Serialized_Array
{
    protected $_eventPrefix = 'bestsellercron_system_config_backend_general';

    private function getTimeStampByCron($cron){
        //replace '*' to '0'
        $time_arr = explode(' ', trim($cron));
        for($i=count($time_arr)-1;$i>=0;$i--){
            if(strpos($time_arr[$i],'/') OR is_numeric($time_arr[$i])){
                $find_interval = $i;
            }
            if($find_interval>=$i){
                if($time_arr[$i]=='*'){
                    $time_arr[$i]='0';
                }
            }
            if(!is_numeric($time_arr[$i])){
                $separator = $i;
            }
        }
        unset($find_interval);
        //explode schedule on 2 part
        for($i=0;$i<count($time_arr);$i++){
            if($i>=$separator){
                $arr_step[] = $time_arr[$i];
            }else{
                $arr_first_run[] = $time_arr[$i];
            }
        }
        //logic for get next timestamp for run
        $current_date = getdate();
        if(!isset($arr_first_run)){
            $result['timestamp_run'] = time();
        }else{
            //minutes
            if(isset($arr_first_run['0'])){
                $first_run['0']=$arr_first_run['0'];
            }else{
                $first_run['0']=$current_date['minutes'];
            }
            //hours
            if(isset($arr_first_run['1'])){
                $first_run['1']=$arr_first_run['1'];
            }else{
                $first_run['1']=$current_date['hours'];
            }
            //day (mday)
            if(isset($arr_first_run['2'])){
                $first_run['2']=$arr_first_run['2'];
            }else{
                $first_run['2']=$current_date['mday'];
            }
            //mon
            if(isset($arr_first_run['3'])){
                $first_run['3']=$arr_first_run['3'];
            }else{
                $first_run['3']=$current_date['mon'];
            }
            $result['timestamp_run']  = strtotime("{$first_run['0']}.{$first_run['1']} {$first_run['2']}-{$first_run['3']}-{$current_date['year']}");
        }
        //logic for get interval run
        if(isset($arr_step)){
            switch(count($arr_step)){
                case 1:
                    //week
                    $result['step_time'] = 604800;
                    break;
                case 2:
                    //mon
                    $result['step_time'] = 2592000;
                    break;
                case 3:
                    //day
                    $result['step_time'] = 86400;
                    break;
                case 4:
                    //hour
                    $result['step_time'] = 3600;
                    break;
                case 5:
                    //minutes
                    $result['step_time'] = 60;
                    break;
            }
        }else{
            $result['step_time'] = 300;
        }
        if(strpos($arr_step['0'],'/')){
            $factor_step = explode('/', trim($arr_step['0']));
            $result['step_time'] = $factor_step['1'] * $result['step_time'];
        }
        $curr_time = time();
        //correct timestamprun for current timestamp
        while($result['timestamp_run']<$curr_time){
            $result['timestamp_run']+=$result['step_time'];
        }
        return $result;
    }

    protected function _beforeSave(){
        $value = $this->getValue();
        if (is_array($value)) {
            unset($value['__empty']);
        }

        if(count($value)==0){
            return;
        }
            foreach($value as $key => &$item){
            $numberOfProducts = &$item['number_of_products'];
            if($numberOfProducts['checkbox'] == 'on'){
                $numberOfProducts['checkbox'] = 'checked';
            }else{
                $numberOfProducts['checkbox'] = '';
            }
            if($item['checkbox'] == 'on'){
                $item['checkbox'] = 'checked';
            }else{
                $item['checkbox'] = '';
            }
        }
        $collection = Mage::getResourceModel('bestsellercron/tasks_collection');
        foreach ($collection as $task) {
            $task->delete();
        }
        foreach($value as $key => $task){
            $result = $this->getTimeStampByCron($task['task_schedule']);
            Mage::getModel('bestsellercron/tasks')->
                setTaskId($key)->
                setCurrentTimestamp($result['timestamp_run'])->
                setStepTimestamp($result['step_time'])->
                save();
        }
        $this->setValue($value);
        parent::_beforeSave();
    }
}

