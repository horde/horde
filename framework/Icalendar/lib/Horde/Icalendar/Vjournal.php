<?php
/**
 * Class representing vJournals.
 *
 * Copyright 2003-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Mike Cochrane <mike@graftonhall.co.nz>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
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
