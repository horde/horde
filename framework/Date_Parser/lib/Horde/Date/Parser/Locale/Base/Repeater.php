<?php
class Horde_Date_Parser_Locale_Base_Repeater
{
    public $monthNameScanner = array(
        '/^jan\.?(uary)?$/i' => 'january',
        '/^feb\.?(ruary)?$/i' => 'february',
        '/^mar\.?(ch)?$/i' => 'march',
        '/^apr\.?(il)?$/i' => 'april',
        '/^may$/i' => 'may',
        '/^jun\.?e?$/i' => 'june',
        '/^jul\.?y?$/i' => 'july',
        '/^aug\.?(ust)?$/i' => 'august',
        '/^sep\.?(t\.?|tember)?$/i' => 'september',
        '/^oct\.?(ober)?$/i' => 'october',
        '/^nov\.?(ember)?$/i' => 'november',
        '/^dec\.?(ember)?$/i' => 'december',
    );

    public $dayNameScanner = array(
        '/^m[ou]n(day)?$/i' => 'monday',
        '/^t(ue|eu|oo|u|)s(day)?$/i' => 'tuesday',
        '/^tue$/i' => 'tuesday',
        '/^we(dnes|nds|nns)day$/i' => 'wednesday',
        '/^wed$/i' => 'wednesday',
        '/^th(urs|ers)day$/i' => 'thursday',
        '/^thu$/i' => 'thursday',
        '/^fr[iy](day)?$/i' => 'friday',
        '/^sat(t?[ue]rday)?$/i' => 'saturday',
        '/^su[nm](day)?$/i' => 'sunday',
    );

    public $dayPortionScanner = array(
        '/^ams?$/i' => 'am',
        '/^pms?$/i' => 'pm',
        '/^mornings?$/i' => 'morning',
        '/^afternoons?$/i' => 'afternoon',
        '/^evenings?$/i' => 'evening',
        '/^(night|nite)s?$/i' => 'night',
    );

    public $unitScanner = array(
        '/^years?$/i' => 'year',
        '/^seasons?$/i' => 'season',
        '/^months?$/i' => 'month',
        '/^fortnights?$/i' => 'fortnight',
        '/^weeks?$/i' => 'week',
        '/^weekends?$/i' => 'weekend',
        '/^days?$/i' => 'day',
        '/^hours?$/i' => 'hour',
        '/^minutes?$/i' => 'minute',
        '/^seconds?$/i' => 'second',
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
                $token->tag(Horde_String::lower(str_replace('Horde_Date_', '', get_class($t))), $t);
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
                $class = 'Horde_Date_Repeater_' . Horde_String::ucfirst($scannerTag);
                return new $class($scannerTag);
            }
        }
    }

}
