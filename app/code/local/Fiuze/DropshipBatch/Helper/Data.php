<?php
/**
 * DropshipBatch
 *
 * @author      Fiuze Team
 * @category    Fiuze
 * @package     DropshipBatch
 * @copyright   Copyright (c) 2016 Fiuze
 */
class Fiuze_DropshipBatch_Helper_Data extends Unirgy_DropshipBatch_Helper_Data
{
    public function __construct()
    {
        ini_set('memory_limit','2048M');
    }
}
