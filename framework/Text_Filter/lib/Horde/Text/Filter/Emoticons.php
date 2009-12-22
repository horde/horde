<?php
/**
 * The Horde_Text_Filter_Emoticons:: class finds emoticon strings ( :), etc.)
 * in a block of text and turns them into image links.
 *
 * Parameters:
 * <pre>
 * entities -- If true the html entity versions of the patterns will be used.
 * </pre>
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Marko Djukic <marko@oblo.com>
 * @package Horde_Text
 */
class Horde_Text_Filter_Emoticons extends Horde_Text_Filter
{
    /**
     * Filter parameters.
     *
     * @var array
     */
    protected $_params = array('entities' => false);

    /**
     * The icon path.
     *
     * @var string
     */
    static protected $_iconpath;

    /* List complex strings before simpler ones, otherwise for example :((
     * would be matched against :( before :(( is found. */
    static protected $_icons = array(
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
        $regexp['{' . $beg_pattern . implode('|', $patterns) . $end_pattern . '}e'] = 'Horde_Text_Filter_Emoticons::getImage(\'$2\', \'$1\', \'' . ($this->_params['entities'] ? '$3' : '') . '\')';

        return array('regexp' => $regexp);
    }

    /**
     * Returns the img tag for an emoticon.
     *
     * @see self::getPatterns()
     *
     * @param string $icon     The emoticon.
     * @param string $prefix   A html prefix.
     * @param string $postfix  A html postfix.
     *
     * @return string  HTML code with the image tag and any additional prefix
     *                 or postfix.
     */
    static public function getImage($icon, $prefix, $postfix)
    {
        if (!isset(self::$_iconpath)) {
            self::$_iconpath = $GLOBALS['registry']->getImageDir('horde') . '/emoticons';
        }

        return $prefix . Horde::img(self::getIcons($icon) . '.png', $icon, array('align' => 'middle', 'title' => $icon), self::$_iconpath) . $postfix;
    }

    /**
     * Returns a hash with all emoticons and names or the name of a single
     * emoticon.
     *
     * @param string $icon  If set, return the name for that emoticon only.
     *
     * @return array|string  Patterns hash or icon name.
     */
    static public function getIcons($icon = null)
    {
        return is_null($icon)
            ? self::$_icons
            : (isset(self::$_icons[$icon]) ? self::$_icons[$icon] : null);
    }

}
