<?php
/**
 * Copyright 2008-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @package  Date_Parser
 */

/**
 *
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2008-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL
 * @package   Date_Parser
 */
class Horde_Date_Parser_Locale_De_Repeater extends Horde_Date_Parser_Locale_Base_Repeater
{
    public $monthNameScanner = array(
        '/^jan\.?(uar)?$/' => 'january',
        '/^feb\.?(ruar)?$/' => 'february',
        '/^mär\.?(z)?$/' => 'march',
        '/^apr\.?(il)?$/' => 'april',
        '/^mai$/' => 'may',
        '/^jun\.?i?$/' => 'june',
        '/^jul\.?i?$/' => 'july',
        '/^aug\.?(ust)?$/' => 'august',
        '/^sep\.?(t\.?|tember)?$/' => 'september',
        '/^okt\.?(ober)?$/' => 'october',
        '/^nov\.?(ember)?$/' => 'november',
        '/^dez\.?(ember)?$/' => 'december',
    );

    public $dayNameScanner = array(
        '/^mo(n(d?tag)?)?$/' => 'monday',
        '/^di(e(nst?tag)?)?$/' => 'tuesday',
        '/^mi(t(t?woch)?)?$/' => 'wednesday',
        '/^do(n(n?erstag)?)?$/' => 'thursday',
        '/^fr(e(itag)?)?$/' => 'friday',
        '/^sa(m(stag)?)?$/' => 'saturday',
        '/^so(n(ntag)?)?$/' => 'sunday',
    );

    public $dayPortionScanner = array(
        '/^vormittags?$/' => 'morning',
        '/^(morgens?|früh)$/' => 'morning',
        '/^nachmittags?$/' => 'afternoon',
        '/^abends?$/' => 'evening',
        '/^nachts?$/' => 'night',
        '/^n8s?$/' => 'night',
    );

    public $unitScanner = array(
        '/^jahre?$/' => 'year',
        //'/^seasons?$/' => 'season', ???
        '/^monate?$/' => 'month',
        //'/^fortnights?$/' => 'fortnight', ??
        '/^wochen?$/' => 'week',
        '/^wochenenden?$/' => 'weekend',
        '/^tage?$/' => 'day',
        '/^stunden?$/' => 'hour',
        '/^minuten?$/' => 'minute',
        '/^sekunden?$/' => 'second',
    );

}
