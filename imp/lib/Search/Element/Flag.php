<?php
/**
 * This class handles flag/keyword search queries.
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
class IMP_Search_Element_Flag extends IMP_Search_Element
{
    /**
     * Allow NOT search on this element?
     *
     * @var boolean
     */
    public $not = false;

    /**
     * Constructor.
     *
     * @param string $name  The flag or keyword name.
     * @param boolean $set  If true, search for messages that have the flag
     *                      set.  If false, search for messages that do not
     *                      have the flag set.
     */
    public function __construct($name, $set = true)
    {
        /* Data element:
         * f = (string) Flag/keyword name.
         * s = (integer) Search for set flag? */
        $this->_data = new stdClass;
        $this->_data->f = $name;
        $this->_data->s = intval($set);
    }

    /**
     * Adds the current query item to the query object.
     *
     * @param Horde_Imap_Client_Search_Query  The query object.
     *
     * @return Horde_Imap_Client_Search_Query  The query object.
     */
    public function createQuery($queryob)
    {
        $queryob->flag($this->_data->f, $this->_data->s);

        return $queryob;
    }

    /**
     * Return search query text representation.
     *
     * @return array  The textual description of this search element.
     */
    public function queryText()
    {
        return sprintf(_("flagged \"%s\""), $GLOBALS['injector']->getInstance('IMP_Imap_Flags')->getLabel($this->_data->f, $this->_data->s));
    }

}
