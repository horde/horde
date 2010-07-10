<?php
class Horde_Date_Parser_Locale_Pt_Repeater extends Horde_Date_Parser_Locale_Base_Repeater
{

    public $monthNameScanner = array(
        '/^jan(eiro)?$/' => 'january',
        '/^fev(reiro)?$/' => 'february',
        '/^mar(co)?$/' => 'march',
        '/^abr(il)?$/' => 'april',
        '/^mai(o)?$/' => 'may',
        '/^jun(ho)?$/' => 'june',     
        '/^jul(ho)?$/' => 'july',
		'/^ago(sto)?$/' => 'august',
        '/^set(embro)?$/' => 'september',
        '/^out(ubro)?$/' => 'october',
        '/^nov(embro)?$/' => 'november',
        '/^dez(embro)?$/' => 'december',
    );

/*
        '/^jan(\.|eiro)?$/' => 'january',
        '/^fev(\.|ereiro)?$/' => 'february',
        '/^mar(\.|(co))?$/' => 'march',
        '/^abr(\.|(il))?$/' => 'april',
        '/^mai(\.|o)?$/' => 'may',
        '/^jun(\.|ho)?$/' => 'june',
        '/^jul(\.|ho)?$/' => 'july',
        '/^ago(\.|sto)?$/' => 'august',
        '/^set(\.|embro)?$/' => 'september',
        '/^out(\.|ubro)?$/' => 'october',
        '/^nov(\.|embro)?$/' => 'november',
        '/^dez(\.|embro)?$/' => 'december',

*/

    public $dayNameScanner = array(
		'/^seg$/' => 'monday',
		'/^ter$/' => 'tuesday',
		'/^qua$/' => 'wednesday',
		'/^qui$/' => 'thursday',
        '/^sex$/' => 'friday',
		'/^sab$/' => 'saturday',
		'/^dom$/' => 'sunday',
		'/^segunda$/' => 'monday',
		'/^terca$/' => 'tuesday',
		'/^quarta$/' => 'wednesday',
        '/^quinta$/' => 'thursday',
		'/^sexta$/' => 'friday',
        '/^sab(ado)?$/' => 'saturday',
        '/^dom(ingo)?$/' => 'sunday',
    );

/*
        '/^seg((unda)?(\s|\-)feira)?$/' => 'monday',
        '/^ter(([cç]a)?(\s|\-)feira)?$/' => 'tuesday',
     	'/^qua((rta)?(\s|\-)feira)?$/' => 'wednesday',
        '/^qui((nta)?([ \-]feira)?)?$/' => 'thursday',
        '/^quinta-feira$/' => 'thursday',
		'/^sex((ta)?(\s|\-)feira)?$/' => 'friday',

*/
// scalar timeSignifiers?
    public $dayPortionScanner = array(
        '/^(\d*)\s?ams?$/' => 'am',
        '/^(\d*)\s?pms?$/' => 'pm',
        '/^(?:de|na|a|durante\s+a) (manh[aã]|madrugada)$/' => 'morning',
        '/^(?:de|na|a|durante\s+a) tarde$/' => 'afternoon',
        '/^((fim\s(d[ea]\s)tarde)|anoitecer)$/' => 'evening',
        '/^noite$/' => 'night',
    );

    public $unitScanner = array(
        '/^anos?$/' => 'year',
        '/^(estacao|epoca)$/' => 'season',
        '/^mes$/' => 'month',
        '/^quinzena$/' => 'fortnight',
        '/^semanas?$/' => 'week',
        '/^(fds|fim( |( |\-)de( |\-))semana)?$/' => 'weekend',
        '/^dias?$/' => 'day',
        '/^horas?$/' => 'hour',
        '/^minutos?$/' => 'minute',
        '/^segundos?$/' => 'second',
    );

    public $timeRegex = '/^\d{1,2}(:?\d{2})?([\.:]?\d{2})?$/';

    public function scan($tokens, $options)
    {
        foreach ($tokens as &$token) {
            if ($t = $this->scanForMonthNames($token)) {
                $token->tag('repeater_month_name', $t);
            } elseif ($t = $this->scanForDayNames($token)) {
                $token->tag('repeater_day_name', $t);
            } elseif ($t = $this->scanForDayPortions($token)) {
                $token->tag('repeater_day_portion', $t);
            } elseif ($t = $this->scanForTimes($token, $options)) {
                $token->tag('repeater_time', $t);
            } elseif ($t = $this->scanForUnits($token)) {
                $token->tag(strtolower(str_replace('Horde_Date_', '', get_class($t))), $t);
            }
        }
        return $tokens;
    }

    public function scanForMonthNames($token)
    {
        foreach ($this->monthNameScanner as $scannerItem => $scannerTag) {
            if (preg_match($scannerItem, $token->word)) {
                return new Horde_Date_Repeater_MonthName($scannerTag);
            }
        }
    }

    public function scanForDayNames($token)
    {
        foreach ($this->dayNameScanner as $scannerItem => $scannerTag) {
            if (preg_match($scannerItem, $token->word)) {
                return new Horde_Date_Repeater_DayName($scannerTag);
            }
        }
    }

    public function scanForDayPortions($token)
    {
        foreach ($this->dayPortionScanner as $scannerItem => $scannerTag) {
            if (preg_match($scannerItem, $token->word)) {
                return new Horde_Date_Repeater_DayPortion($scannerTag);
            }
        }
    }

    public function scanForTimes($token, $options)
    {
        if (preg_match($this->timeRegex, $token->word)) {
            return new Horde_Date_Repeater_Time($token->word, $options);
        }
    }

    public function scanForUnits($token)
    {
        foreach ($this->unitScanner as $scannerItem => $scannerTag) {
            if (preg_match($scannerItem, $token->word)) {
                $class = 'Horde_Date_Repeater_' . ucfirst($scannerTag);
                return new $class($scannerTag);
            }
        }
    }

}
