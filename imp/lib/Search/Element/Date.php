<?php
/**
 * This class handles date-related search queries.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Search_Element_Date extends IMP_Search_Element
{
    /* Date types. */
    const DATE_ON = 1;
    const DATE_BEFORE = 2;
    const DATE_SINCE = 3;

    /**
     * Constructor.
     *
     * @param DateTime $date  Date object.
     * @param integer $type   Either:
     * <pre>
     * IMP_Search_Element_Date::DATE_ON
     * IMP_Search_Element_Date::DATE_BEFORE
     * IMP_Search_Element_Date::DATE_SINCE
     * </pre>
     */
    public function __construct(DateTime $date, $type)
    {
        /* Data element:
         * d = (integer) UNIX timestamp.
         * t = (integer) Type: one of the self::DATE_* constants. */
        $this->_data = new stdClass;
        $this->_data->d = $date->format('U');
        $this->_data->t = $type;
    }

    /**
     */
    public function createQuery($mbox, $queryob)
    {
        // Cast to timestamp - see PHP Bug #40171/Horde Bug #9513
        $date = new DateTime('@' . $this->_data->d);
        $queryob->dateSearch($date, ($this->_data->t == self::DATE_ON) ? Horde_Imap_Client_Search_Query::DATE_ON : (($this->_data->t == self::DATE_BEFORE) ? Horde_Imap_Client_Search_Query::DATE_BEFORE : Horde_Imap_Client_Search_Query::DATE_SINCE));

        return $queryob;
    }

    /**
     */
    public function queryText()
    {
        switch ($this->_data->t) {
        case self::DATE_ON:
            $label = _("Date Equals (=)");
            break;

        case self::DATE_BEFORE:
            $label = _("Date Until (<)");
            break;

        case self::DATE_SINCE:
            $label = _("Date Since (>=)");
            break;
        }

        return sprintf("%s '%s'", $label, strftime('%x', $this->_data->d));
    }

}
