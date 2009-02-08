<?php
class Horde_Date_Parser_Locale_Base_Ordinal
{
    public $ordinalRegex = '/^(\d*)(st|nd|rd|th)$/';
    public $ordinalDayRegex = '/^(\d*)(st|nd|rd|th)$/';

    public function scan($tokens)
    {
        foreach ($tokens as &$token) {
            if (!is_null($t = $this->scanForOrdinals($token))) {
                $token->tag('ordinal', $t);
            }
            if (!is_null($t = $this->scanForDays($token))) {
                $token->tag('ordinal_day', $t);
            }
        }

        return $tokens;
    }

    public function scanForOrdinals($token)
    {
        if (preg_match($this->ordinalRegex, $token->word, $matches)) {
            return (int)$matches[1];
        }
    }

    public function scanForDays($token)
    {
        if (preg_match($this->ordinalDayRegex, $token->word, $matches)) {
            if ($matches[1] <= 31) {
                return (int)$matches[1];
            }
        }
    }

}
