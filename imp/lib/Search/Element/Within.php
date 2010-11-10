<?php
/**
 * This class handles within (date) search queries.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Search_Element_Within extends IMP_Search_Element
{
    /* Interval types. */
    const INTERVAL_DAYS = 1;
    const INTERVAL_MONTHS = 2;
    const INTERVAL_YEARS = 3;

    /**
     * Constructor.
     *
     * @param integer $interval  Interval value.
     * @param integer $type      Interval type. Either:
     * <pre>
     * IMP_Search_Element_Within::INTERVAL_DAYS
     * IMP_Search_Element_Within::INTERVAL_MONTHS
     * IMP_Search_Element_Within::INTERVAL_YEARS
     * </pre>
     * @param boolean $older     Do an older search?
     */
    public function __construct($interval, $type, $older = true)
    {
        /* Data element:
         * o = (integer) Do an older search?
         * t = (integer) Interval type.
         * v = (integer) Interval value. */
        $this->_data = new stdClass;
        $this->_data->o = intval($older);
        $this->_data->t = $type;
        $this->_data->v = $interval;
    }

    /**
     */
    public function createQuery($queryob)
    {
        /* Limited to day granularity because that is the technical
         * limit for IMAP servers without 'WITHIN' extension. */
        $secs = $this->_data->v * 60 * 60 * 24;
        switch ($this->_data->t) {
        case self::INTERVAL_YEARS:
            $secs *= 365;
            break;

        case self::INTERVAL_MONTHS:
            $secs *= 30;
            break;
        }

        $queryob->intervalSearch($secs, $this->_data->o ? Horde_Imap_Client_Search_Query::INTERVAL_OLDER : Horde_Imap_Client_Search_Query::INTERVAL_YOUNGER);

        return $queryob;
    }

    /**
     */
    public function queryText()
    {
        $label = $this->_data->o
            ? _("Older Than")
            : _("Younger Than");

        switch ($this->_data->t) {
        case self::INTERVAL_YEARS:
            $term = _("years");
            break;

        case self::INTERVAL_MONTHS:
            $term = _("months");
            break;

        case self::INTERVAL_DAYS:
            $term = _("days");
            break;
        }

        return sprintf("%s %u %s", $label, $this->_data->v, $term);
    }

}
