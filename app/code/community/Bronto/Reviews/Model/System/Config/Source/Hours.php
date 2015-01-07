<?php

class Bronto_Reviews_Model_System_Config_Source_Hours
{
    private static $_options = array();

    /**
     * Gets the list of hours and labels
     *
     * @return array
     */
    public function toOptionArray()
    {
        if (empty(self::$_options)) {
            foreach (range(0, 23) as $hour) {
                $meridan = 'AM';
                $number = $hour;
                if ($hour == 0) {
                    $number = 12;
                } else if ($hour > 12) {
                    $number = $hour - 12;
                    $meridan = 'PM';
                } else if ($hour == 12) {
                    $meridan = 'PM';
                }
                self::$_options[] = array(
                    'label' => $number . $meridan,
                    'value' => $hour
                );
            }
            array_unshift(self::$_options, array(
                'label' => Mage::helper('bronto_reviews')->__('-- No Adjustment --'),
                'value' => -1
            ));
        }
        return self::$_options;
    }
}
