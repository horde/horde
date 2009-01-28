<?php
class Horde_Date_Parser_Locale_Base_Scalar extends Horde_Date_Parser_Tag
{
    public $scalarRegex = '/^\d*$/';
    public $dayRegex = '/^\d\d?$/';
    public $monthRegex = '/^\d\d?$/';
    public $yearRegex = '/^([1-9]\d)?\d\d?$/';
    public $timeSignifiers = array('am', 'pm', 'morning', 'afternoon', 'evening', 'night');

    public function scan($tokens)
    {
        foreach ($tokens as $i => &$token) {
            $postToken = isset($tokens[$i + 1]) ? $tokens[$i + 1] : null;
            if ($t = $this->scanForScalars($token, $postToken)) {
                $token->tag($t);
            }
            if ($t = $this->scanForDays($token, $postToken)) {
                $token->tag($t);
            }
            if ($t = $this->scanForMonths($token, $postToken)) {
                $token->tag($t);
            }
            if ($t = $this->scanForYears($token, $postToken)) {
                $token->tag($t);
            }
        }
        return $tokens;
    }

    public function scanForScalars($token, $postToken)
    {
        if (preg_match($this->scalarRegex, $token->word)) {
            if (!in_array($postToken, $this->timeSignifiers)) {
                return new self((int)$token->word);
            }
        }
        return null;
    }

    public function scanForDays($token, $postToken)
    {
        if (preg_match($this->dayRegex, $token->word)) {
            if ((int)$token->word <= 31 && !in_array($postToken, $this->timeSignifiers)) {
                return new Horde_Date_Parser_Locale_Base_ScalarDay((int)$token->word);
            }
        }
        return null;
    }

    public function scanForMonths($token, $postToken)
    {
        if (preg_match($this->monthRegex, $token->word)) {
            if ((int)$token->word <= 12 && !in_array($postToken, $this->timeSignifiers)) {
                return new Horde_Date_Parser_Locale_Base_ScalarMonth((int)$token->word);
            }
        }
        return null;
    }

    public function scanForYears($token, $postToken)
    {
        if (preg_match($this->yearRegex, $token->word)) {
            if (!in_array($postToken, $this->timeSignifiers)) {
                return new Horde_Date_Parser_Locale_Base_ScalarYear((int)$token->word);
            }
        }
        return null;
    }

    public function __toString()
    {
        return 'scalar';
    }

}

class Horde_Date_Parser_Locale_Base_ScalarDay extends Horde_Date_Parser_Locale_Base_Scalar
{
    public function __toString()
    {
        return parent::__toString() . '-day-' . $this->type;
    }

}

class Horde_Date_Parser_Locale_Base_ScalarMonth extends Horde_Date_Parser_Locale_Base_Scalar
{
    public function __toString()
    {
        return parent::__toString() . '-month-' . $this->type;
    }

}

class Horde_Date_Parser_Locale_Base_ScalarYear extends Horde_Date_Parser_Locale_Base_Scalar
{
    public function __toString()
    {
        return parent::__toString() . '-year-' . $this->type;
    }

}
