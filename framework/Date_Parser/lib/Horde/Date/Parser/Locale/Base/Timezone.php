<?php
class Horde_Date_Parser_Locale_Base_Timezone extends Horde_Date_Parser_Tag
{
    public $scanner = array(
        '/[PMCE][DS]T/i' => 'tz',
    );

    public function scan($tokens)
    {
        foreach ($tokens as &$token) {
            if ($t = $this->scanForAll($token)) {
                $token->tag($t);
            }
        }
        return $tokens;
    }

    public function scanForAll($token)
    {
        foreach ($this->scanner as $scannerItem => $scannerTag) {
            if (preg_match($scannerItem, $token->word)) {
                return new self($scannerTag);
            }
        }
    }

    public function __toString()
    {
        return 'timezone';
    }

}
