<?php
/**
 * The Horde_Text_Filter_Emails:: class finds email addresses in a block of
 * text and turns them into links.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@curecanti.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Core
 */
class Horde_Core_Text_Filter_Emails extends Horde_Text_Filter_Emails
{
    /**
     * Constructor.
     *
     * @param array $params  Parameters (in addition to base
     *                       Horde_Text_Filter_Emails parameters):
     * <pre>
     * always_mailto - (boolean) If true, a mailto: link is generated always.
     *                 Only if no mail/compose registry API method exists
     *                 otherwise.
     *                 DEFAULT: false
     * callback - (callback) Use this callback instead of the mail/compose
     *            API call.
     *            DEFAULT: Use mail/compose API call.
     * </pre>
     */
    public function __construct(array $params = array())
    {
        $this->_params = array_merge(array(
            'always_mailto' => false,
            'callback' => null
        ), $this->_params, $params);

        parent::__construct($params);
    }

    /**
     * Regular expression callback.
     *
     * @param array $matches  preg_replace_callback() matches.
     *
     * @return string  Replacement string.
     */
    protected function _regexCallback($matches)
    {
        if ($this->_params['always_mailto'] ||
            (!$this->_params['callback'] &&
             !$GLOBALS['registry']->hasMethod('mail/compose'))) {
            return parent::_regexCallback($matches);
        }

        if (!isset($matches[10]) || ($matches[10] === '')) {
            $args = $matches[7];
            $email = $matches[3];
            $args_long = $matches[5];
        } else {
            $args = isset($matches[13]) ? $matches[13] : '';
            $email = $matches[10];
            $args_long = isset($matches[11]) ? $matches[11] : '';
        }

        parse_str($args, $extra);
        try {
            $url = $this->_params['callback']
                ? strval(call_user_func($this->_params['callback'], array('to' => $email), $extra))
                : strval($GLOBALS['registry']->call('mail/compose', array(array('to' => $email), $extra)));
        } catch (Horde_Exception $e) {
            return parent::_regexCallback($matches);
        }

        if (substr($url, 0, 11) == 'javascript:') {
            $href = '#';
            $onclick = ' onclick="' . substr($url, 11) . ';return false;"';
        } else {
            $href = htmlspecialchars($url);
            $onclick = '';
        }

        $class = empty($this->_params['class'])
            ? ''
            : ' class="' . $this->_params['class'] . '"';

        return '<a' . $class .' href="' . $href . '"' . $onclick . '>' .
            htmlspecialchars($email) . htmlspecialchars($args_long) .
            '</a>';
    }

}
