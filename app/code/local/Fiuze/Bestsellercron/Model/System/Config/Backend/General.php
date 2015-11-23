<?php

class Fiuze_Bestsellercron_Model_System_Config_Backend_General extends Mage_Adminhtml_Model_System_Config_Backend_Serialized_Array{
    protected $_eventPrefix = 'bestsellercron_system_config_backend_general';

    private function _getTimeStampByCron($cron){
        //replace '*' to '0'
        $time_arr = explode(' ', trim($cron));
        for($i=count($time_arr)-2;$i>=0;$i--){
            if(strpos($time_arr[$i],'/') OR is_numeric($time_arr[$i])){
                $find_interval = $i;
            }
            if(isset($find_interval) AND $find_interval>=$i){
                if($time_arr[$i]=='*'){
                    $time_arr[$i]='0';
                }
            }
            if(strpos($time_arr[$i], ',')){
                $period = $i;
                //imitation normal cron
                //save period line
                $period_data = explode(',', trim($time_arr[$i]));
                sort($period_data);
                $time_arr[$i] = $period_data[0];
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
                $first_run['0']=(int)$arr_first_run['0'];
            }else{
                $first_run['0']=$current_date['minutes'];
            }
            //hours
            if(isset($arr_first_run['1'])){
                $first_run['1']=(int)$arr_first_run['1'];
            }else{
                $first_run['1']=$current_date['hours'];
            }
            //day (mday)
            if(isset($arr_first_run['2'])){
                $first_run['2']=(int)$arr_first_run['2'];
            }else{
                $first_run['2']=$current_date['mday'];
            }
            //mon
            if(isset($arr_first_run['3'])){
                $first_run['3']=(int)$arr_first_run['3'];
            }else{
                $first_run['3']=$current_date['mon'];
            }
            //week
            if($time_arr['4']!='*'){
                $first_run['4']=(int)$time_arr['4'];
            }else{
                $first_run['4']=$current_date['wday'];
            }
            $first_run['4']++;
            switch ($first_run['4']){
                case 1:
                    $first_run['4']='Sun';
                    break;
                case 2:
                    $first_run['4']='Mon';
                    break;
                case 3:
                    $first_run['4']='Tue';
                    break;
                case 4:
                    $first_run['4']='Wed';
                    break;
                case 5:
                    $first_run['4']='Thu';
                    break;
                case 6:
                    $first_run['4']='Fri';
                    break;
                case 7:
                    $first_run['4']='Sat';
                    break;
            }
            $result['timestamp_run']  = strtotime("{$first_run['4']}, {$first_run['1']}.{$first_run['0']} {$first_run['2']}-{$first_run['3']}-{$current_date['year']}");
        }
        //logic for get interval run

        if($period OR $period===0){
            //configurable interval
            $interval_data = array();
            switch(count($arr_first_run)){
                case 1:
                    //minutes
                    $step_time = 60;
                    $factor = 60;
                    break;
                case 2:
                    //hour
                    $factor = 24;
                    $step_time = 3600;
                    break;
                case 3:
                    //day
                    $factor = 30;
                    $step_time = 86400;
                    break;
                case 4:
                    //mon
                    $factor = 12;
                    $step_time = 2592000;
                    break;
                case 5:
                    //week
                    $factor = 7;
                    $step_time = 604800;
                    break;
            }

            for($i = 1; $i < (count($period_data)+1); $i++){
                if($i!=count($period_data)){
                    $interval_data[] = $period_data[$i] - $period_data[$i-1];
                }else{
                    $interval_data[] = $period_data[0] + ($factor-$period_data[$i-1]);
                }
            }


            for($i=0;$i<count($interval_data);$i++){
                $interval_data[$i]=$step_time*$interval_data[$i];
            }
            $serialize_data=array(
                'current_cycle'=>'0',
                'cycle_data'=>$interval_data
            );
            $result['step_time']=serialize($serialize_data);
        } else{
            //logic for get simple interval run
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
            //fix for correct week interval
            if($time_arr['4']!='*'){
                $result['step_time'] = 604800;
            }
            //correct timestamprun for current timestamp
            while($result['timestamp_run']<$curr_time){
                $result['timestamp_run']+=$result['step_time'];
            }
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
            $result = $this->_getTimeStampByCron($task['task_schedule']);
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

