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
class Horde_Date_Parser_Result
{
    public $span;
    public $tokens = array();

    public function __construct($span, $tokens)
    {
        $this->span = $span;
        $this->tokens = $tokens;
    }

    /**
     * Guess a specific time within the given span
     */
    public function guess()
    {
        if (! $this->span instanceof Horde_Date_Span) {
            return null;
        }

        if ($this->span->width() > 1) {
            return $this->span->begin->add($this->span->width() / 2);
        } else {
            return $this->span->begin;
        }
    }

    public function taggedText()
    {
        $taggedTokens = array_values(array_filter(
            $this->tokens, function ($t) { return $t->tagged(); }
        ));
        return implode(
            ' ', array_map(function ($t) { return $t->word; }, $taggedTokens)
        );
    }

    public function untaggedText()
    {
        $untaggedTokens = array_values(array_filter(
            $this->tokens, function ($t) { return !$t->tagged(); }
        ));
        return implode(
            ' ', array_map(function ($t) { return $t->word; }, $untaggedTokens)
        );
    }

}
