<?php
class Horde_Date_Parser_Locale_Pt_Grabber extends Horde_Date_Parser_Locale_Base_Grabber
{
    /**
     * Regex tokens
     */
    public $scanner = array(
        '/^(passado|[uú]ltim[ao]|anterior)$/' => 'last',			
        '/^n?est[ea]$/' => 'this',					
        '/^(pr[oó]xim[oa]|seguinte)$/' => 'next',	
    );

    public function scan($tokens)
    {
        foreach ($tokens as &$token) {
            if ($t = $this->scanForAll($token)) {
                $token->tag('grabber', $t);
            }
        }
        return $tokens;
    }

    public function scanForAll($token)
    {
        foreach ($this->scanner as $scannerItem => $scannerTag) {
            if (preg_match($scannerItem, $token->word)) {
                return $scannerTag;
            }
        }
    }

}
