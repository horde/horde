<?php
/**
 * This class handles flag/keyword search queries.
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
     */
    public function createQuery($mbox, $queryob)
    {
        $queryob->flag($this->_data->f, $this->_data->s);

        return $queryob;
    }

    /**
     */
    public function queryText()
    {
        $imp_flags = $GLOBALS['injector']->getInstance('IMP_Flags');

        return ($tmp = $imp_flags[$this->_data->f])
            ? sprintf(_("flagged \"%s\""), $tmp->getLabel($this->_data->s))
            : '';
    }

}
