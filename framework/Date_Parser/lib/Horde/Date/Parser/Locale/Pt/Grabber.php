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
class Horde_Date_Parser_Locale_Pt_Grabber extends Horde_Date_Parser_Locale_Base_Grabber
{
    /**
     * Regex tokens
     */
    public $scanner = array(
	    '/(passado|[uú]ltim[ao]|anterior)/' => 'last',
		'/n?est[ea]/' => 'this',
		'/(pr[oó]xim[oa]|seguinte)/' => 'next',
        '/last/' => 'last',
		'/this/' => 'this',
		'/next/' => 'next',
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
