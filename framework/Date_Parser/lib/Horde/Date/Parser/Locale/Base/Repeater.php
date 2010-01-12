<?php
class Horde_Date_Parser_Locale_Base_Repeater
{
    public $monthNameScanner = array(
        '/^jan\.?(uary)?$/' => 'january',
        '/^feb\.?(ruary)?$/' => 'february',
        '/^mar\.?(ch)?$/' => 'march',
        '/^apr\.?(il)?$/' => 'april',
        '/^may$/' => 'may',
        '/^jun\.?e?$/' => 'june',
        '/^jul\.?y?$/' => 'july',
        '/^aug\.?(ust)?$/' => 'august',
        '/^sep\.?(t\.?|tember)?$/' => 'september',
        '/^oct\.?(ober)?$/' => 'october',
        '/^nov\.?(ember)?$/' => 'november',
        '/^dec\.?(ember)?$/' => 'december',
    );

    public $dayNameScanner = array(
        '/^m[ou]n(day)?$/' => 'monday',
        '/^t(ue|eu|oo|u|)s(day)?$/' => 'tuesday',
        '/^tue$/' => 'tuesday',
        '/^we(dnes|nds|nns)day$/' => 'wednesday',
        '/^wed$/' => 'wednesday',
        '/^th(urs|ers)day$/' => 'thursday',
        '/^thu$/' => 'thursday',
        '/^fr[iy](day)?$/' => 'friday',
        '/^sat(t?[ue]rday)?$/' => 'saturday',
        '/^su[nm](day)?$/' => 'sunday',
    );

    public $dayPortionScanner = array(
        '/^ams?$/' => 'am',
        '/^pms?$/' => 'pm',
        '/^mornings?$/' => 'morning',
        '/^afternoons?$/' => 'afternoon',
        '/^evenings?$/' => 'evening',
        '/^(night|nite)s?$/' => 'night',
    );

    public $unitScanner = array(
        '/^years?$/' => 'year',
        '/^seasons?$/' => 'season',
        '/^months?$/' => 'month',
        '/^fortnights?$/' => 'fortnight',
        '/^weeks?$/' => 'week',
        '/^weekends?$/' => 'weekend',
        '/^days?$/' => 'day',
        '/^hours?$/' => 'hour',
        '/^minutes?$/' => 'minute',
        '/^seconds?$/' => 'second',
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
