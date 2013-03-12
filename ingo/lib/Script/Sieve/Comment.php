<?php
/**
 * Copyright 2012-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Mike Cochrane <mike@graftonhall.co.nz>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 *
 * @todo    This and Sieve_If should really extends a Sieve_Block eventually.
 */

/**
 * The Ingo_Script_Sieve_Comment class represents a Sieve comment.
 *
 * @author   Mike Cochrane <mike@graftonhall.co.nz>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */
class Ingo_Script_Sieve_Comment implements Ingo_Script_Item
{
    /**
     */
    protected $_comment;

    /**
     * Constructor.
     *
     * @param string $comment  The comment text.
     */
    public function __construct($comment)
    {
        $this->_comment = $comment;
    }

    /**
     * Returns a script snippet representing this rule and any sub-rules.
     *
     * @return string  A Sieve script snippet.
     */
    public function generate()
    {
        $code = '';
        $lines = preg_split('(\r\n|\n|\r)', $this->_comment);
        foreach ($lines as $line) {
            $line = trim($line);
            if (strlen($line)) {
                $code .= (empty($code) ? '' : "\n") . '# ' . $line;
            }
        }
        return $code;
    }

    /**
     * Checks if the rule parameters are valid.
     *
     * @return boolean|string  True if this rule is valid, an error message
     *                         otherwise.
     */
    public function check()
    {
        return true;
    }

    /**
     * Returns a list of sieve extensions required for this rule and any
     * sub-rules.
     *
     * @return array  A Sieve extension list.
     */
    public function requires()
    {
        return array();
    }
}
