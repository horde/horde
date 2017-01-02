<?php
/**
 * The Horde_Text_Filter_Emoticons:: class finds emoticon strings in a block
 * of text and does a transformation on them.
 *
 * By default, this filter does not do any transformation to the emoticon.
 *
 * Parameters:
 * <pre>
 * entities - (boolean) Use HTML entity versions of the patterns?
 *            DEFAULT: false
 * </pre>
 *
 * Copyright 2003-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Marko Djukic <marko@oblo.com>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Filter
 */
class Horde_Text_Filter_Emoticons extends Horde_Text_Filter_Base
{
    /**
     * Filter parameters.
     *
     * @var array
     */
    protected $_params = array(
        'entities' => false
    );

    /* List complex strings before simpler ones, otherwise for example :((
     * would be matched against :( before :(( is found. */
    protected $_emoticons = array(
        ':/' => 'frustrated', ':-/' => 'frustrated',
        // ':*>' => 'blush',
        ':e' => 'disappointed',
        '=:)$' => 'mrt',
        '#|' => 'hangover', '#-|' => 'hangover',
        ':-@' => 'shout', ':@' => 'shout',
        ':((' => 'bigfrown', ':C' => 'bigfrown',
        ':S' => 'dazed', ':-S' => 'dazed',
        'X@' => 'angry',
        'X(' => 'mad',
        // '>:)' => 'devil', '>:-)' => 'devil',
        // '>:p' => 'deviltongue', '>:-p' => 'deviltongue',
        // '>:p' => 'raspberry', '>:P' => 'raspberry',
        // '&)' => 'punk',
        // '&p' => 'punktongue',
        // '=&)' => 'punkmohawk',
        ':]' => 'grin',
        '#[' => 'hurt', '#(' => 'hurt', '#-[' => 'hurt', '#-(' => 'hurt',
        ':O' => 'embarrassed', ':-O' => 'embarrassed',
        ':[' => 'sad',
        // '>:@' => 'enraged',
        // ':&' => 'annoyed',
        '=(' => 'worried', '=-(' => 'worried',
        ':|=' => 'vampire',
        ':-(' => 'frown', ':(' => 'frown',
        ':D' => 'biggrin', ':-D' => 'biggrin', ':d' => 'biggrin', ':-d' => 'biggrin',
        // '8)' => 'cool',
        // In English, 8PM occurs sufficiently often to specifically
        // search for and exclude
        // '8p(?<![Mm]\s+)' => 'cooltongue', // '8Þ' => 'cooltongue',
        // '8D' => 'coolgrin',
        ':p' => 'tongueout', ':P' => 'tongueout', // ':Þ' => 'tongueout',
        '?:(' => 'confused', '%-(' => 'confused',
        // ':)&' => 'love',
        'O;-)' => 'angelwink',
        ';]' => 'winkgrin',
        ';p' => 'winktongue', ';P' => 'winktongue', // ';Þ' => 'winktongue',
        ':|' => 'indifferent', ':-|' => 'indifferent',
        '!|' => 'tired', '!-I' => 'tired',
        '|I' => 'asleep', '|-I' => 'asleep',
        'O:)' => 'angel', 'O:-)' => 'angel',
        'O;)' => 'angelwink',
        ';-)' => 'wink', ';)' => 'wink',
        ':#)' => 'clown', ':o)' => 'clown',
        ':)' => 'smile', ':-)' => 'smile',
    );

    /**
     * Returns a hash with replace patterns.
     *
     * @return array  Patterns hash.
     */
    public function getPatterns()
    {
        /* Build the patterns. */
        $patterns = array_keys($this->getIcons());
        if ($this->_params['entities']) {
            $patterns = array_map('htmlspecialchars', $patterns);
            $beg_pattern = '(^|\s|<br />|&nbsp;)(';
            $end_pattern = ')(?=\s|<br />|&nbsp;)';
        } else {
            $beg_pattern = '(^|\s)(';
            $end_pattern = ')(?=\s)';
        }
        $patterns = array_map('preg_quote', $patterns);

        /* Check for a smiley either immediately at the start of a line or
         * following a space. Use {} as the preg delimiters as this is not
         * found in any smiley. */
        $regexp = '{' . $beg_pattern . implode('|', $patterns) . $end_pattern . '}';

        return array('regexp_callback' => array(
            $regexp => array($this, 'emoticonReplace')
        ));
    }

    /**
     * Returns the replacement emoticon text.
     *
     * @param array $matches  Matches from preg_replace_callback().
     *
     * @return string  The replacement text.
     */
    public function emoticonReplace($matches)
    {
        return $matches[1] . $this->getIcon($matches[2]) . (empty($matches[3]) ? '' : $matches[3]);
    }

    /**
     * Return the replacement emoticon text.
     *
     * @param string $icon  The emoticon name.
     *
     * @return string  The replacement text.
     */
    public function getIcon($icon)
    {
        return $icon;
    }

    /**
     * Returns a hash with all emoticons and names or the name of a single
     * emoticon.
     *
     * @param string $icon  If set, return the name for that emoticon only.
     *
     * @return array|string  Patterns hash or emoticon name.
     */
    public function getIcons($icon = null)
    {
        return is_null($icon)
            ? $this->_emoticons
            : (isset($this->_emoticons[$icon]) ? $this->_emoticons[$icon] : null);
    }

}
