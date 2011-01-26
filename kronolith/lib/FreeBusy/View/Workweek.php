<?php
/**
 * This class represent a work week of free busy information sets.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @author  Jan Schneider <jan@horde.org>
 * @package Kronolith
 */
class Kronolith_FreeBusy_View_Workweek extends Kronolith_FreeBusy_View_Week
{
   /**
     * This view type
     *
     * @var string
     */
    var $view = 'workweek';

    /**
     * Number of days
     *
     * @var integer
     */
    var $_days = 5;

}
