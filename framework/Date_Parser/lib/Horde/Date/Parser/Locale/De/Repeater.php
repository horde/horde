<?php
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
