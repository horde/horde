<?php
/**
 * Date
 */
class Horde_Form_Type_Date extends Horde_Form_Type {

    var $_format = '%a %d %B';

    public function isValid($var, $vars, $value, &$message)
    {
        $valid = true;

        if ($var->required) {
            $valid = strlen(trim($value)) > 0;

            if (!$valid) {
                $message = Horde_Model_Translation::t("This field is required.");
            }
        }

        return $valid;
    }

    public static function getAgo($timestamp)
    {
        if ($timestamp === null) {
            return '';
        }

        $diffdays = Date_Calc::dateDiff(date('j', $timestamp),
                                        date('n', $timestamp),
                                        date('Y', $timestamp),
                                        date('j'), date('n'), date('Y'));

        /* An error occured. */
        if ($diffdays == -1) {
            return;
        }

        $ago = $diffdays * Date_Calc::compareDates(date('j', $timestamp),
                                                   date('n', $timestamp),
                                                   date('Y', $timestamp),
                                                   date('j'), date('n'),
                                                   date('Y'));
        if ($ago < -1) {
            return sprintf(Horde_Model_Translation::t(" (%s days ago)"), $diffdays);
        } elseif ($ago == -1) {
            return Horde_Model_Translation::t(" (yesterday)");
        } elseif ($ago == 0) {
            return Horde_Model_Translation::t(" (today)");
        } elseif ($ago == 1) {
            return Horde_Model_Translation::t(" (tomorrow)");
        } else {
            return sprintf(Horde_Model_Translation::t(" (in %s days)"), $diffdays);
        }
    }

    public function getFormattedTime($timestamp, $format = null, $showago = true)
    {
        if (empty($format)) {
            $format = $this->_format;
        }
        if (!empty($timestamp)) {
            return strftime($format, $timestamp) . ($showago ? self::getAgo($timestamp) : '');
        } else {
            return '';
        }
    }

}
