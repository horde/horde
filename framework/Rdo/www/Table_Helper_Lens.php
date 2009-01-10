<?php
/**
 * @package Horde_Rdo
 */
class Horde_Rdo_Table_Helper_Lens extends Horde_Rdo_Lens
{
    /**
     */
    private $date_format = '%x';

    /**
     */
    private $time_format = 'G:i';

    /**
     */
    public function __construct()
    {
        $this->date_format = $GLOBALS['prefs']->getValue('date_format');
        $this->time_format = $GLOBALS['prefs']->getValue('twentyFour') ? 'G:i' : 'g:ia';
    }

    /**
     */
    public function decorate($target)
    {
        if (!is_object($target)) {
            $target = (object)$target;
        }
        return parent::decorate($target);
    }

    /**
     */
    public function __get($key)
    {
        $value = parent::__get($key);
        if ($key == 'updated' || $key == 'created') {
            return strftime($this->date_format, $value) . ' ' . date($this->time_format , $value);
        }
        return htmlspecialchars($value);
    }

}
