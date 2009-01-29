<?php
class Horde_Date_Parser_Locale_Base_Separator extends Horde_Date_Parser_Tag
{
    public $commaScanner = array(
        '/^,$/' => 'comma',
    );

    public $slashOrDashScanner = array(
        '/^-$/' => 'dash',
        '/^\/$/' => 'slash',
    );

    public $atScanner = array(
        '/^(at|@)$/' => 'at',
    );

    public $inScanner = array(
        '/^in$/' => 'in',
    );

    public function scan($tokens)
    {
        foreach ($tokens as &$token) {
            if ($t = $this->scanForCommas($token)) {
                $token->tag($t);
            } elseif ($t = $this->scanForSlashOrDash($token)) {
                $token->tag($t);
            } elseif ($t = $this->scanForAt($token)) {
                $token->tag($t);
            } elseif ($t = $this->scanForIn($token)) {
                $token->tag($t);
            }
        }
        return $tokens;
    }

    public function scanForCommas($token)
    {
        foreach ($this->commaScanner as $scannerItem => $scannerTag) {
            if (preg_match($scannerItem, $token->word)) {
                /* FIXME */
                return new Horde_Date_Parser_Locale_Base_SeparatorComma($scannerTag);
            }
        }
    }

    public function scanForSlashOrDash($token)
    {
        foreach ($this->slashOrDashScanner as $scannerItem => $scannerTag) {
            if (preg_match($scannerItem, $token->word)) {
                /* FIXME */
                return new Horde_Date_Parser_Locale_Base_SeparatorSlashOrDash($scannerTag);
            }
        }
    }

    public function scanForAt($token)
    {
        foreach ($this->atScanner as $scannerItem => $scannerTag) {
            if (preg_match($scannerItem, $token->word)) {
                /* FIXME */
                return new Horde_Date_Parser_Locale_Base_SeparatorAt($scannerTag);
            }
        }
    }

    public function scanForIn($token)
    {
        foreach ($this->inScanner as $scannerItem => $scannerTag) {
            if (preg_match($scannerItem, $token->word)) {
                /* FIXME */
                return new Horde_Date_Parser_Locale_Base_SeparatorIn($scannerTag);
            }
        }
    }

    public function __toString()
    {
        return 'separator';
    }

}

class Horde_Date_Parser_Locale_Base_SeparatorComma extends Horde_Date_Parser_Locale_Base_Separator
{
    public function __toString()
    {
        return parent::__toString() . '-comma';
    }

}

class Horde_Date_Parser_Locale_Base_SeparatorSlashOrDash extends Horde_Date_Parser_Locale_Base_Separator
{
    public function __toString()
    {
        return parent::__toString() . '-slashordash-' . $this->type;
    }

}

class Horde_Date_Parser_Locale_Base_SeparatorAt extends Horde_Date_Parser_Locale_Base_Separator
{
    public function __toString()
    {
        return parent::__toString() . '-at';
    }

}

class Horde_Date_Parser_Locale_Base_SeparatorIn extends Horde_Date_Parser_Locale_Base_Separator
{
    public function __toString()
    {
        return parent::__toString() . '-in';
    }

}
