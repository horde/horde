<?php
/**
 * Class representing vJournals.
 *
 * Copyright 2003-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Mike Cochrane <mike@graftonhall.co.nz>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Icalendar
 */
class Horde_Icalendar_Vjournal extends Horde_Icalendar {

    /**
     * The component type of this class.
     *
     * @var string
     */
    public $type = 'vJournal';

    /**
     * TODO
     *
     * @return TODO
     */
    public function exportvCalendar()
    {
        return $this->_exportvData('VJOURNAL');
    }

}
