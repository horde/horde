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
class Horde_Date_Parser_Locale_Base_Scalar
{
    public $scalarRegex = '/^\d*$/';
    public $dayRegex = '/^\d\d?$/';
    public $monthRegex = '/^\d\d?$/';
    public $yearRegex = '/^([1-9]\d)?\d\d?$/';
    public $timeSignifiers = array('am', 'pm', 'morning', 'afternoon', 'evening', 'night');

    public function scan($tokens)
    {
        foreach ($tokens as $i => &$token) {
            $postToken = isset($tokens[$i + 1]) ? $tokens[$i + 1]->word : null;
            if (!is_null($t = $this->scanForScalars($token, $postToken))) {
                $token->tag('scalar', $t);
            }
            if (!is_null($t = $this->scanForDays($token, $postToken))) {
                $token->tag('scalar_day', $t);
            }
            if (!is_null($t = $this->scanForMonths($token, $postToken))) {
                $token->tag('scalar_month', $t);
            }
            if (!is_null($t = $this->scanForYears($token, $postToken))) {
                $token->tag('scalar_year', $t);
            }
        }
        return $tokens;
    }

    public function scanForScalars($token, $postToken)
    {
        if (preg_match($this->scalarRegex, $token->word)) {
            if (!in_array($postToken, $this->timeSignifiers)) {
                return $token->word;
            }
        }
    }

    public function scanForDays($token, $postToken)
    {
        if (preg_match($this->dayRegex, $token->word)) {
            if ($token->word <= 31 && !in_array($postToken, $this->timeSignifiers)) {
                return $token->word;
            }
        }
    }

    public function scanForMonths($token, $postToken)
    {
        if (preg_match($this->monthRegex, $token->word)) {
            if ($token->word <= 12 && !in_array($postToken, $this->timeSignifiers)) {
                return $token->word;
            }
        }
    }

    public function scanForYears($token, $postToken)
    {
        if (preg_match($this->yearRegex, $token->word)) {
            if (!in_array($postToken, $this->timeSignifiers)) {
                return $token->word;
            }
        }
    }

}
