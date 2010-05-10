<?php
class Horde_Date_Parser_Locale_Pt_Repeater extends Horde_Date_Parser_Locale_Base_Repeater
{
    public $monthNameScanner = array(
        '/^jan\.?(eiro)?$/' => 'january',
        '/^fev\.?(ereiro)?$/' => 'february',
        '/^mar\.?((c|ç)o)?$/' => 'march',
        '/^abr\.?(il)?$/' => 'april',
        '/^mai\.?o?$/' => 'may',
        '/^jun\.?(ho)?$/' => 'june',
        '/^jul\.?(ho)?$/' => 'july',
        '/^ago\.?(sto)?$/' => 'august',
        '/^set\.?(embro)?$/' => 'september',
        '/^out\.?(ubro)?$/' => 'october',
        '/^nov\.?(embro)?$/' => 'november',
        '/^dez\.?(embro)?$/' => 'december',
    );

    public $dayNameScanner = array(
        '/^se(g(d?(unda?(\s|-)feira))?)?$/' => 'monday',
        '/^te(r([c|ç]a?(\s|-)feira)?)?$/' => 'tuesday',
        '/^qu(a(rta?(\s|-)feira)?)?$/' => 'wednesday',
        '/^qu(i(nta?(\s|-)feira)?)?$/' => 'thursday',
        '/^se(x(ta?(\s|-)feira)?)?$/' => 'friday',
        '/^s[a|á](b(ado)?)?$/' => 'saturday',
        '/^do(m(ingo)?)?$/' => 'sunday',
    );

    public $dayPortionScanner = array(
        '/^manh(a|ã)?$/' => 'morning',
        '/^tarde?$/' => 'afternoon',
        '/^(fim (d(e|a) )?tarde)?$/' => 'evening',
        '/^noite?$/' => 'night',
    );

    public $unitScanner = array(
        '/^(ano(s)?)?$/' => 'year',
        '/^(esta(c|ç)(a|ã)o?!época)$/' => 'season',
        '/^m(e|ê)s?$/' => 'month',
        '/^quinzena?$/' => 'fortnight',
        '/^semana?s?$/' => 'week',
        '/^(fds|fim(\s|(\s|-)de(\s|-))semana)?$/' => 'weekend',
        '/^dia?s?$/' => 'day',
        '/^hora?s?$/' => 'hour',
        '/^minuto?s?$/' => 'minute',
        '/^segundo?s?$/' => 'second',
    );

}

