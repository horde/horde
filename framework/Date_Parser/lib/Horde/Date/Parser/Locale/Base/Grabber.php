<?php
class Horde_Date_Parser_Locale_Base_Grabber extends Horde_Date_Parser_Tag
{
    /**
     * Regex tokens
     */
    public $scanner = array(
        '/last/' => 'last',
        '/this/' => 'this',
        '/next/' => 'next',
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
        return 'grabber-' . $this->type;
    }

}
