<?php
/**
 * Copyright 2008-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @package  Date_Parser
 */

/**
 *
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2008-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL
 * @package   Date_Parser
 */
class Horde_Date_Parser_Locale_Pt_Separator extends Horde_Date_Parser_Locale_Base_Separator
{

    public $commaScanner = array(
        '/^,$/' => 'comma',
    );

    public $slashOrDashScanner = array(
        '/^-$/' => 'dash',
        '/^\/$/' => 'slash',
    );

    public $atScanner = array(
        '/^(em|@|de)$/' => 'at',
		'/^(as|ao)$/' => 'at',
		'/^(at|@)$/' => 'at',
    );


    public $inScanner = array(
        '/^no$/' => 'in',
		'/^in$/' => 'in',
    );

    public function scan($tokens)
    {
        foreach ($tokens as &$token) {
            if ($t = $this->scanForCommas($token)) {
                $token->tag('separator_comma', $t);
            } elseif ($t = $this->scanForSlashOrDash($token)) {
                $token->tag('separator_slash_or_dash', $t);
            } elseif ($t = $this->scanForAt($token)) {
                $token->tag('separator_at', $t);
            } elseif ($t = $this->scanForIn($token)) {
                $token->tag('separator_in', $t);
            }
        }
        return $tokens;
    }

    public function scanForCommas($token)
    {
        foreach ($this->commaScanner as $scannerItem => $scannerTag) {
            if (preg_match($scannerItem, $token->word)) {
                return $scannerTag;
            }
        }
    }

    public function scanForSlashOrDash($token)
    {
        foreach ($this->slashOrDashScanner as $scannerItem => $scannerTag) {
            if (preg_match($scannerItem, $token->word)) {
                return $scannerTag;
            }
        }
    }

    public function scanForAt($token)
    {
        foreach ($this->atScanner as $scannerItem => $scannerTag) {
            if (preg_match($scannerItem, $token->word)) {
                return $scannerTag;
            }
        }
    }

    public function scanForIn($token)
    {
        foreach ($this->inScanner as $scannerItem => $scannerTag) {
            if (preg_match($scannerItem, $token->word)) {
                return $scannerTag;
            }
        }
    }
}
