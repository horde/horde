<?php
/**
 * Interface to NLS configuration.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Core
 */
class Horde_Registry_Nlsconfig
{
    /**
     * The cached configuration data.
     *
     * @var array
     */
    protected $_config;

    /**
     */
    public function __get($name)
    {
        /* These entries can be cached in the session. */
        $cached = array(
            'curr_charset',
            'curr_default',
            'curr_emails',
            'curr_multibyte',
            'curr_rtl'
        );

        if (in_array($name, $cached) &&
            $GLOBALS['session']->exists('horde', 'nls/' . $name)) {
            return $GLOBALS['session']->get('horde', 'nls/' . $name);
        }

        if (!isset($this->_config)) {
            $this->_config = Horde::loadConfiguration('nls.php', 'horde_nls_config', 'horde');
        }

        switch ($name) {
        case 'aliases':
        case 'charsets':
        case 'encodings':
        case 'emails':
        case 'languages':
        case 'multibyte':
        case 'rtl':
        case 'spelling':
            $ret = isset($this->_config[$name])
                ? $this->_config[$name]
                : array();
            break;

        case 'charsets_sort':
            $ret = $this->charsets;
            natcasesort($ret);
            break;

        case 'curr_charset':
            /* Return charset for the current language. */
            $ret = isset($this->_config['charsets'][$GLOBALS['language']])
                ? $this->_config['charsets'][$GLOBALS['language']]
                : null;
            break;

        case 'curr_default':
            /* The default langauge, as specified by the config file. */
            $ret = isset($this->_config['defaults']['language'])
                ? $this->_config['defaults']['language']
                : null;
            break;

        case 'curr_emails':
            /* Return e-mail charset for the current language. */
            $ret = isset($this->_config['emails'][$GLOBALS['language']])
                ? $this->_config['emails'][$GLOBALS['language']]
                : null;
            break;

        case 'curr_multibyte':
            /* Is the current language charset multibyte? */
            $ret = isset($this->_config['multibyte'][$GLOBALS['registry']->getLanguageCharset()]);
            break;

        case 'curr_rtl':
            /* Is the current language RTL? */
            $ret = isset($this->_config['rtl'][$GLOBALS['language']]);
            break;

        case 'encodings_sort':
            $ret = $this->encodings;
            asort($ret);
            break;

        default:
            $ret = null;
            break;
        }

        if (in_array($name, $cached)) {
            $GLOBALS['session']->set('horde', 'nls/' . $name, $ret);
        }

        return $ret;
    }

    /**
     * Check whether a language string is valid.
     *
     * @param string $lang  The language to check.
     *
     * @return boolean  Whether the language is valid.
     */
    public function validLang($lang)
    {
        if (!$GLOBALS['session']->exists('horde', 'nls/valid_' . $lang)) {
            $GLOBALS['session']->set('horde', 'nls/valid_' . $lang, isset($this->languages[$lang]));
        }

        return $GLOBALS['session']->get('horde', 'nls/valid_' . $lang);
    }

}
