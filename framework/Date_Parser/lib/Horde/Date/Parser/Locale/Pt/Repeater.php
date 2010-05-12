<?php
class Horde_Date_Parser_Locale_Pt_Repeater extends Horde_Date_Parser_Locale_Base_Repeater
{
    public $monthNameScanner = array(
        '/\bjan(\.|eiro)?\b/' => 'january',
        '/\bfev(\.|ereiro)?\b/' => 'february',
        '/\bmar(\.|((c|\x87)o))?\b/' => 'march',
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
        '/\bse(g(d?(unda?(\s|-)feira))?)?\b/' => 'monday',
        '/\bte(r([c|\x87]a?(\s|-)feira)?)?\b/' => 'tuesday',
        '/\bqu(a(rta?(\s|-)feira)?)?\b/' => 'wednesday',
        '/\bqu(i(nta?(\s|-)feira)?)?\b/' => 'thursday',
        '/\bse(x(ta?(\s|-)feira)?)?\b/' => 'friday',
        '/\bs[a|\xe1](b(ado)?)?\b/' => 'saturday',
        '/\bdo(m(ingo)?)?\b/' => 'sunday',
    );

    public $dayPortionScanner = array(
        '/\b(\d*)\s?ams?\b/' => 'am',
        '/\b(\d*)\s?pms?\b/' => 'pm',
        '/\bmanh(a|\xe3)\b/' => 'morning',
        '/\btarde\b/' => 'afternoon',
        '/\b(fim\s(d(e|a)\s)tarde)\b/' => 'evening',
        '/\bnoite\b/' => 'night',
    );

    public $unitScanner = array(
        '/\bano(s)?\b/' => 'year',
        '/\b(esta(c|\x87)(a|\xe3)o|(e|\xe9)poca)\b/' => 'season',
        '/\bm(e|\xea)s\b/' => 'month',
        '/\bquinzena\b/' => 'fortnight',
        '/\bsemana(s)?\b/' => 'week',
        '/\b(fds|fim(\s|(\s|-)de(\s|-))semana)?\b/' => 'weekend',
        '/\bdia(s)?\b/' => 'day',
        '/\bhora(s)?\b/' => 'hour',
        '/\bminuto(s)?\b/' => 'minute',
        '/\bsegundo(s)?\b/' => 'second',
    );

}

