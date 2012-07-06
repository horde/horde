<?php
/**
 * This class handles date-related search queries.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Search_Element_Daterange extends IMP_Search_Element
{
    /**
     * Constructor.
     *
     * @param mixed $begin  Either a DateTime object of the beginning date or
     *                      null (all messages since the beginning of time).
     * @param mixed $end    Either a DateTime object of the ending date or
     *                      null (all messages until the end of time).
     * @param boolean $not  Is this a not search?
     */
    public function __construct($begin, $end, $not = false)
    {
        /* Data element:
         * b = (integer) UNIX timestamp - beginning.
         * e = (integer) UNIX timestamp - ending.
         * n = (integer) Do a NOT search? */
        $this->_data = new stdClass;
        $this->_data->b = ($begin instanceof DateTime)
            ? $begin->format('U')
            : 0;
        $this->_data->e = ($end instanceof DateTime)
            ? $end->format('U')
            : 0;
        $this->_data->n = intval($not);

        /* Flip $begin and $end if $end is earlier than $begin. */
        if ($this->_data->b &&
            $this->_data->e &&
            ($this->_data->b > $this->_data->e)) {
            $tmp = $this->_data->e;
            $this->_data->e = $this->_data->b;
            $this->_data->b = $tmp;
        }
    }

    /**
     */
    public function createQuery($mbox, $queryob)
    {
        if ($this->_data->b == $this->_data->e) {
            $queryob->dateSearch(
                // Cast to timestamp - see PHP Bug #40171/Horde Bug #9513
                new DateTime('@' . ($this->_data->b)),
                Horde_Imap_Client_Search_Query::DATE_ON,
                true,
                $this->_data->n
            );
        } else {
            if (!empty($this->_data->b)) {
                $queryob->dateSearch(
                    new DateTime('@' . ($this->_data->b)),
                    Horde_Imap_Client_Search_Query::DATE_SINCE,
                    true,
                    $this->_data->n
                );
            }
            if (!empty($this->_data->e)) {
                $queryob->dateSearch(
                    new DateTime('@' . ($this->_data->e + 86400)),
                    Horde_Imap_Client_Search_Query::DATE_BEFORE,
                    true,
                    $this->_data->n
                );
            }
        }

        return $queryob;
    }

    /**
     */
    public function queryText()
    {
        if (empty($this->_data->e)) {
            return sprintf(
                _("After '%s'"),
                gmstrftime('%x', $this->_data->b)
            );
        }

        if (empty($this->_data->b)) {
            return sprintf(
                _("Before '%s'"),
                gmstrftime('%x', $this->_data->e)
            );
        }

        if ($this->_data->b == $this->_data->e) {
            return sprintf(
                _("On '%s'"),
                gmstrftime('%x', $this->_data->b)
            );
        }

        return sprintf(
            _("Between '%s' and '%s'"),
            gmstrftime('%x', $this->_data->b),
            gmstrftime('%x', $this->_data->e)
        );
    }

}
