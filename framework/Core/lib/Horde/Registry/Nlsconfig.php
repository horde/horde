<?php
/**
 * Interface to NLS configuration.
 *
 * Copyright 2010-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
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
        global $language, $registry, $session;

        /* These entries can be cached in the session. */
        $cached = array(
            'curr_charset',
            'curr_default',
            'curr_emails',
            'curr_multibyte',
            'curr_rtl'
        );

        if (in_array($name, $cached) &&
            $session->exists('horde', 'nls/' . $name)) {
            return $session->get('horde', 'nls/' . $name);
        }

        if (!isset($this->_config)) {
            $this->_config = $registry->loadConfigFile('nls.php', 'horde_nls_config', 'horde')->config['horde_nls_config'];
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
            $ret = isset($this->_config['charsets'][$language])
                ? $this->_config['charsets'][$language]
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
            $ret = isset($this->_config['emails'][$language])
                ? $this->_config['emails'][$language]
                : null;
            break;

        case 'curr_multibyte':
            /* Is the current language charset multibyte? */
            $ret = isset($this->_config['multibyte'][$registry->getLanguageCharset()]);
            break;

        case 'curr_rtl':
            /* Is the current language RTL? */
            $ret = isset($this->_config['rtl'][$language]);
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
            $session->set('horde', 'nls/' . $name, $ret);
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
