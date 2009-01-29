<?php
class Horde_Date_Parser_Locale_Base_Repeater extends Horde_Date_Parser_Tag
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
        '/^jan\.?(uary)?$/' => 'january',
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
                $token->tag($t);
            } elseif ($t = $this->scanForDayNames($token)) {
                $token->tag($t);
            } elseif ($t = $this->scanForDayPortions($token)) {
                $token->tag($t);
            } elseif ($t = $this->scanForTimes($token, $options)) {
                $token->tag($t);
            } elseif ($t = $this->scanForUnits($token)) {
                $token->tag($t);
            }
        }
        return $tokens;
    }

    public function scanForMonthNames($token)
    {
        foreach ($this->monthNameScanner as $scannerItem => $scannerTag) {
            if (preg_match($scannerItem, $token->word)) {
                return new Horde_Date_Parser_Locale_Base_Repeater_MonthName($scannerTag);
            }
        }
    }

    public function scanForDayNames($token)
    {
        foreach ($this->dayNameScanner as $scannerItem => $scannerTag) {
            if (preg_match($scannerItem, $token->word)) {
                return new Horde_Date_Parser_Locale_Base_Repeater_DayName($scannerTag);
            }
        }
    }

    public function scanForDayPortions($token)
    {
        foreach ($this->dayPortionScanner as $scannerItem => $scannerTag) {
            if (preg_match($scannerItem, $token->word)) {
                return new Horde_Date_Parser_Locale_Base_Repeater_DayPortion($scannerTag);
            }
        }
    }

    public function scanForTimes($token, $options)
    {
        if (preg_match($this->timeRegex, $token->word)) {
            return new Horde_Date_Parser_Locale_Base_Repeater_Time($token->word, $options);
        }
    }

    public function scanForUnits($token)
    {
        foreach ($this->uniScanner as $scannerItem => $scannerTag) {
            if (preg_match($scannerItem, $token->word)) {
                $class = 'Horde_Date_Parser_Locale_Base_Repeater_' . ucfirst($scannerTag);
                return new $class($scannerTag);
            }
        }
    }

    /*
  def <=>(other)
    width <=> other.width
  end
    */

    /**
     * returns the width (in seconds or months) of this repeatable.
     */
    public function width()
    {
        throw new Horde_Date_Parser_Exception('Repeatable#width must be overridden in subclasses');
    }

    /**
     * returns the next occurance of this repeatable.
     */
    public function next($pointer)
    {
        if (is_null($this->now)) {
            throw new Horde_Date_Parser_Exception('Start point must be set before calling next()');
        }

        if (!in_array($pointer, array('future', 'none', 'past'))) {
            throw new Horde_Date_Parser_Exception("First argument 'pointer' must be one of 'past', 'future', 'none'");
        }
    }

    public function this($pointer)
    {
        if (is_null($this->now)) {
            throw new Horde_Date_Parser_Exception('Start point must be set before calling this()');
        }

        if (!in_array($pointer, array('future', 'none', 'past'))) {
            throw new Horde_Date_Parser_Exception("First argument 'pointer' must be one of 'past', 'future', 'none'");
        }
    }

    public function __toString()
    {
        return 'repeater';
    }

}
