<?php

/**
 * @package   Bronto\Common
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Common_Block_Adminhtml_System_Config_Cron extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{
    /**
     * Job code
     *
     * @var string
     */
    protected $_jobCode;

    /**
     * Button widgets
     *
     * @var array
     */
    protected $_buttons = array();

    /**
     * Progress bar
     *
     * @var boolean
     */
    protected $_hasProgressBar = false;

    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('bronto/common/cron.phtml');
    }

    /**
     * Prepare the layout
     *
     * @return Bronto_Common_Block_Adminhtml_System_Config_Cron
     */
    protected function _prepareLayout()
    {
        if ($head = $this->getLayout()->getBlock('head')) {
            $head->addCss('bronto/cron.css');
        }

        return parent::_prepareLayout();
    }

    /**
     * Render the block
     *
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->toHtml();
    }

    /**
     * Get a job schedule collection
     *
     * @return Mage_Cron_Model_Mysql4_Schedule_Collection
     */
    public function getJobSchedule()
    {
        return Mage::getModel('cron/schedule')->getCollection()
            ->addFieldToFilter('job_code', $this->_jobCode)
            ->setPageSize(6)
            ->setCurPage(1)
            ->setOrder('scheduled_at', 'DESC');
    }

    /**
     * Get cron job message
     * Note: Limits to 100 characters
     *
     * @param Mage_Cron_Model_Schedule $job
     *
     * @return string
     */
    public function getTruncatedJobMessages($job)
    {
        return Mage::helper('core/string')->truncate($job->getMessages(), 100);
    }

    /**
     * Get the HTML markup for the button widgets
     *
     * @return string
     */
    public function getButtonsHtml()
    {
        $html = null;
        if ($buttons = $this->getButtons()) {
            foreach ($buttons as $_button) {
                $html .= $_button->toHtml();
            }
        }

        if (!empty($html)) {
            $html = "<p class=\"form-buttons bronto-cron\">{$html}</p>";
        }

        return $html;
    }

    protected function _getProgressComplete($total, $getCount = false, $getBar = true, $getLegend = false)
    {
        $html       = '';
        $percent    = 0;
        $pending    = (int)$this->getProgressBarPending();
        $disabled   = $this->_getProgressDisabled($total, true);
        $suppressed = $this->_getProgressSuppressed($total, true);
        $pending    = $pending - $disabled;

        $count = $total - ($pending + $suppressed + $disabled);

        if ($getCount) {
            return $count;
        }

        if ($total > 0) {
            $percent = round(((float)$count / (float)$total) * 100, 1);
        }

        if ($getBar) {
            if ($count > 0) {
                $html .= "<div class=\"bronto-progress-bar-complete\" style=\"width: {$percent}%\">";
                $html .= ($percent > 4) ? "{$percent}%" : "&nbsp;";
                $html .= "</div>";
            }
        }

        if ($getLegend) {
            $html .= '<div class="bronto-progress-bar-legend-complete">';
            $html .= '<div class="bronto-progress-bar-legend-status"></div>';
            $html .= "<div class=\"bronto-progress-bar-legend-details\">Completed: {$percent}% ({$count}/{$total})</div>";
            $html .= '</div>';
        }

        return $html;
    }

    protected function _getProgressSuppressed($total, $getCount = false, $getBar = true, $getLegend = false)
    {
        $html    = '';
        $percent = 0;
        $count   = (int)$this->getProgressBarSuppressed();

        if ($getCount) {
            return $count;
        }

        if ($total > 0) {
            $percent = round(((float)$count / (float)$total) * 100, 1);
        }

        if ($getBar) {
            if ($count > 0) {
                $html .= "<div class=\"bronto-progress-bar-suppressed\" style=\"width: {$percent}%\">";
                $html .= ($percent > 4) ? "{$percent}%" : "&nbsp;";
                $html .= "</div>";
            }
        }

        if ($getLegend) {
            $html .= '<div class="bronto-progress-bar-legend-suppressed">';
            $html .= '<div class="bronto-progress-bar-legend-status"></div>';
            $html .= "<div class=\"bronto-progress-bar-legend-details\">Suppressed: {$percent}% ({$count}/{$total})</div>";
            $html .= '</div>';
        }

        return $html;
    }

    protected function _getProgressDisabled($total, $getCount = false, $getBar = true, $getLegend = false)
    {
        $html    = '';
        $percent = 0;
        $count   = (int)$this->getProgressBarDisabled();

        if ($getCount) {
            return $count;
        }

        if ($total > 0) {
            $percent = round(((float)$count / (float)$total) * 100, 1);
        }

        if ($getBar) {
            if ($count > 0) {
                $html .= "<div class=\"bronto-progress-bar-disabled\" style=\"width: {$percent}%\">";
                $html .= ($percent > 4) ? "{$percent}%" : "&nbsp;";
                $html .= "</div>";
            }
        }

        if ($getLegend) {
            $html .= '<div class="bronto-progress-bar-legend-disabled">';
            $html .= '<div class="bronto-progress-bar-legend-status"></div>';
            $html .= "<div class=\"bronto-progress-bar-legend-details\">Disabled: {$percent}% ({$count}/{$total})</div>";
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * Get the HTML markup for the progress bar
     *
     * @return string
     */
    public function getProgressBarHtml()
    {
        $total = (int)$this->getProgressBarTotal();

        // Build Status Legend
        $html = '<div class="bronto-progress-bar-legend">';
        $html .= $this->_getProgressComplete($total, false, false, true);
        $html .= $this->_getProgressSuppressed($total, false, false, true);
        $html .= $this->_getProgressDisabled($total, false, false, true);
        $html .= '</div>';

        // Build Progress Bar
        $html .= "<div class=\"bronto-progress-bar\">";
        $html .= $this->_getProgressComplete($total);
        $html .= $this->_getProgressSuppressed($total);
        $html .= $this->_getProgressDisabled($total);
        $html .= '</div>';

        // Add Info Hover
        $html .= '<div class="bronto-help bronto-floater">';
        $html .= '   <ul class="bronto-help-window">';
        $html .= '       <li><strong>Completed</strong> refers to items that have been successfully imported.</li>';
        $html .= '       <li><strong>Suppressed</strong> refers to items that have failed to import and will not be attempted again until all have been reset.</li>';
        $html .= '       <li><strong>Disabled</strong> refers to items that exist in stores where this module is not enabled</li>';
        $html .= '   </ul>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Color code the job status
     *
     * @param string $status
     *
     * @return string
     */
    public function decorateJobStatus($status)
    {
        switch ($status) {
            case Mage_Cron_Model_Schedule::STATUS_SUCCESS:
                $color = 'green';
                break;
            case Mage_Cron_Model_Schedule::STATUS_RUNNING:
                $color = 'yellow';
                break;
            case Mage_Cron_Model_Schedule::STATUS_MISSED:
                $color = 'orange';
                break;
            case Mage_Cron_Model_Schedule::STATUS_ERROR:
                $color = 'red';
                break;
            case Mage_Cron_Model_Schedule::STATUS_PENDING:
            default:
                $color = 'lightgray';
                break;
        }

        return "<span class=\"bar-{$color}\"><span>{$status}</span></span>";
    }

    /**
     * Add button widget
     *
     * @param Mage_Adminhtml_Block_Widget_Button $button
     *
     * @return Bronto_Common_Block_Adminhtml_System_Config_Cron
     */
    public function addButton(Mage_Adminhtml_Block_Widget_Button $button)
    {
        $this->_buttons[] = $button;

        return $this;
    }

    /**
     * Get button widgets
     *
     * @return array
     */
    public function getButtons()
    {
        return $this->_buttons;
    }

    /**
     * Set if we're using a progress bar
     *
     * @param bool $hasProgressBar
     *
     * @return Bronto_Common_Block_Adminhtml_System_Config_Cron
     */
    public function setHasProgressBar($hasProgressBar)
    {
        $this->_hasProgressBar = $hasProgressBar;

        return $this;
    }

    /**
     * Get if we have a progress bar
     *
     * @return boolean
     */
    public function hasProgressBar()
    {
        return (bool)$this->_hasProgressBar;
    }

    /**
     * @return int
     */
    protected function getProgressBarTotal()
    {
        return 0;
    }

    /**
     * @return int
     */
    protected function getProgressBarPending()
    {
        return 0;
    }

    /**
     * @return int
     */
    protected function getProgressBarSuppressed()
    {
        return 0;
    }

    /**
     * @return int
     */
    protected function getProgressBarDisabled()
    {
        return 0;
    }

    /**
     * Determine if should show the cron table
     *
     * @return mixed
     */
    public function showCronTable()
    {
        return true;
    }
}
