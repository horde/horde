<?php
class Horde_Date_Parser_Locale_Pt_Repeater extends Horde_Date_Parser_Locale_Base_Repeater
{
    public $monthNameScanner = array(
        '/\bjan(\.|eiro)?\b/' => 'january',
        '/\bfev(\.|ereiro)?\b/' => 'february',
        '/\bmar(\.|([cç]o))?\b/' => 'march',
        '/\babr(\.|(il))?\b/' => 'april',
        '/\bmai(\.|o)?\b/' => 'may',
        '/\bjun(\.|ho)?\b/' => 'june',
        '/\bjul(\.|ho)?\b/' => 'july',
        '/\bago(\.|sto)?\b/' => 'august',
        '/\bset(\.|embro)?\b/' => 'september',
        '/\bout(\.|ubro)?\b/' => 'october',
        '/\bnov(\.|embro)?\b/' => 'november',
        '/\bdez(\.|embro)?\b/' => 'december',
    );

    public $dayNameScanner = array(
        '/\bseg(d?(unda?(\s|\-)feira))?\b/' => 'monday',
        '/\bter([cç]a?(\s|\-)feira)?\b/' => 'tuesday',
        '/\bqua(rta?(\s|\-)feira)?\b/' => 'wednesday',
        '/\bqui(nta?(\s|\-)feira)?\b/' => 'thursday',
        '/\bsex(ta?(\s|\-)feira)?\b/' => 'friday',
        '/\bs[aá]b(ado)?\b/' => 'saturday',
        '/\bdom(ingo)?\b/' => 'sunday',
    );

    public $dayPortionScanner = array(
        '/\b(\d*)\s?ams?\b/' => 'am',
        '/\b(\d*)\s?pms?\b/' => 'pm',
        '/\bmanh[aã]\b/' => 'morning',
        '/\btarde\b/' => 'afternoon',
        '/\b(fim\s(d[ea]\s)tarde)\b/' => 'evening',
        '/\bnoite\b/' => 'night',
    );

    public $unitScanner = array(
        '/\bano(s)?\b/' => 'year',
        '/\b(esta[cç][aã]o|[eé]poca)\b/' => 'season',
        '/\bm[eê]s\b/' => 'month',
        '/\bquinzena\b/' => 'fortnight',
        '/\bsemana(s)?\b/' => 'week',
        '/\b(fds|fim(\s|(\s|-)de(\s|-))semana)?\b/' => 'weekend',
        '/\bdia(s)?\b/' => 'day',
        '/\bhora(s)?\b/' => 'hour',
        '/\bminuto(s)?\b/' => 'minute',
        '/\bsegundo(s)?\b/' => 'second',
    );

}
