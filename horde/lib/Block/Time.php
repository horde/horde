<?php
/**
 */
class Horde_Block_Time extends Horde_Core_Block
{
    /**
     */
    public $updateable = true;

    /**
     */
    public function __construct($app, $params = array())
    {
        parent::__construct($app, $params);

        $this->_name = _("Current Time");
    }

    /**
     */
    protected function _params()
    {
        return array(
            'time' => array(
                'type' => 'enum',
                'name' => _("Time format"),
                'default' => '24-hour',
                'values' => array(
                    '24-hour' => _("24 Hour Format"),
                    '12-hour' => _("12 Hour Format")
                )
            )
        );
    }

    /**
     */
    protected function _content()
    {
        if (empty($this->_params['time'])) {
            $this->_params['time'] = '24-hour';
        }

        // Set the timezone variable, if available.
        $GLOBALS['registry']->setTimeZone();

        $html = '<div style="font-size:200%; font-weight:bold; text-align:center">' .
            strftime('%A, %B %d, %Y ');
        if ($this->_params['time'] == '24-hour') {
            $html .= strftime('%H:%M');
        } else {
            $html .= strftime('%I:%M %p');
        }
        return $html . '</div>';
    }

}
