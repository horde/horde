<?php
/**
 * The Horde_Nls:: class provides Native Language Support. This includes
 * common methods for handling language detection and selection, timezones,
 * and hostname->country lookups.
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Jon Parise <jon@horde.org>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Nls
 */
class Horde_Nls
{
    /**
     * Config values.
     *
     * @var array
     */
    static public $config = array();

    /**
     * Cached values.
     *
     * @var array
     */
    static protected $_cache = array();

    /**
     * Selects the most preferred language for the current client session.
     *
     * @return string  The selected language abbreviation.
     */
    static public function select()
    {
        $lang = Horde_Util::getFormData('new_lang');

        /* First, check if language pref is locked and, if so, set it to its
         * value */
        if (isset($GLOBALS['prefs']) &&
            $GLOBALS['prefs']->isLocked('language')) {
            $language = $GLOBALS['prefs']->getValue('language');
        /* Check if the user selected a language from the login screen */
        } elseif (!empty($lang) && self::isValid($lang)) {
            $language = $lang;
        /* Check if we have a language set in the session */
        } elseif (isset($_SESSION['horde_language'])) {
            $language = $_SESSION['horde_language'];
        /* Use site-wide default, if one is defined */
        } elseif (!empty(self::$config['defaults']['language'])) {
            $language = self::$config['defaults']['language'];
        /* Try browser-accepted languages. */
        } elseif (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            /* The browser supplies a list, so return the first valid one. */
            $browser_langs = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
            foreach ($browser_langs as $lang) {
                /* Strip quality value for language */
                if (($pos = strpos($lang, ';')) !== false) {
                    $lang = substr($lang, 0, $pos);
                }
                $lang = self::_map(trim($lang));
                if (self::isValid($lang)) {
                    $language = $lang;
                    break;
                }

                /* In case there's no full match, save our best guess. Try
                 * ll_LL, followed by just ll. */
                if (!isset($partial_lang)) {
                    $ll_LL = Horde_String::lower(substr($lang, 0, 2)) . '_' . Horde_String::upper(substr($lang, 0, 2));
                    if (self::isValid($ll_LL)) {
                        $partial_lang = $ll_LL;
                    } else {
                        $ll = self::_map(substr($lang, 0, 2));
                        if (self::isValid($ll))  {
                            $partial_lang = $ll;
                        }
                    }
                }
            }
        }

        if (!isset($language)) {
            $language = isset($partial_lang)
                ? $partial_lang
                /* No dice auto-detecting, default to US English. */
                : 'en_US';
        }

        return basename($language);
    }

    /**
     * Sets the language.
     *
     * @param string $lang  The language abbreviation.
     *
     * @throws Horde_Exception
     */
    static public function setLanguage($lang = null)
    {
        Horde::loadConfiguration('nls.php', null, 'horde');

        if (empty($lang) || !self::isValid($lang)) {
            $lang = self::select();
        }

        $_SESSION['horde_language'] = $lang;

        if (isset($GLOBALS['language'])) {
            if ($GLOBALS['language'] == $lang) {
                return;
            } elseif (isset($GLOBALS['registry'])) {
                $GLOBALS['registry']->clearCache();
            }
        }
        $GLOBALS['language'] = $lang;

        /* First try language with the current charset. */
        $lang_charset = $lang . '.' . self::getCharset();
        if ($lang_charset != setlocale(LC_ALL, $lang_charset)) {
            /* Next try language with its default charset. */
            $charset = empty(self::$config['charsets'][$lang])
                ? 'ISO-8859-1'
                : self::$config['charsets'][$lang];
            $lang_charset = $lang . '.' . $charset;
            self::_cachedCharset(0, $charset);
            if ($lang_charset != setlocale(LC_ALL, $lang_charset)) {
                /* At last try language solely. */
                $lang_charset = $lang;
                setlocale(LC_ALL, $lang_charset);
            }
        }

        @putenv('LC_ALL=' . $lang_charset);
        @putenv('LANG=' . $lang_charset);
        @putenv('LANGUAGE=' . $lang_charset);
    }

    /**
     * Sets the gettext domain.
     *
     * @param string $app        The application name.
     * @param string $directory  The directory where the application's
     *                           LC_MESSAGES directory resides.
     * @param string $charset    The charset.
     */
    static public function setTextdomain($app, $directory, $charset)
    {
        bindtextdomain($app, $directory);
        textdomain($app);

        /* The existence of this function depends on the platform. */
        if (function_exists('bind_textdomain_codeset')) {
            self::_cachedCharset(0, bind_textdomain_codeset($app, $charset));
        }

        if (!headers_sent()) {
            header('Content-Type: text/html; charset=' . $charset);
        }
    }

    /**
     * Sets the language and reloads the whole NLS environment.
     *
     * When setting the language, the gettext catalogs have to be reloaded
     * too, charsets have to be updated etc. This method takes care of all
     * this.
     *
     * @param string $language  The new language.
     * @param string $app       The application for reloading the gettext
     *                          catalog. The current application if empty.
     */
    static public function setLanguageEnvironment($language = null, $app = null)
    {
        if (empty($app)) {
            $app = $GLOBALS['registry']->getApp();
        }
        self::setLanguage($language);
        self::setTextdomain(
            $app,
            $GLOBALS['registry']->get('fileroot', $app) . '/locale',
            self::getCharset()
        );
        Horde_String::setDefaultCharset(self::getCharset());
    }

    /**
     * Determines whether the supplied language is valid.
     *
     * @param string $language  The abbreviated name of the language.
     *
     * @return boolean  True if the language is valid, false if it's not
     *                  valid or unknown.
     */
    static public function isValid($language)
    {
        return !empty(self::$config['languages'][$language]);
    }

    /**
     * Maps languages with common two-letter codes (such as nl) to the
     * full gettext code (in this case, nl_NL). Returns the language
     * unmodified if it isn't an alias.
     *
     * @param string $language  The language code to map.
     *
     * @return string  The mapped language code.
     */
    static protected function _map($language)
    {
        // Translate the $language to get broader matches.
        // (eg. de-DE should match de_DE)
        $trans_lang = str_replace('-', '_', $language);
        $lang_parts = explode('_', $trans_lang);
        $trans_lang = Horde_String::lower($lang_parts[0]);
        if (isset($lang_parts[1])) {
            $trans_lang .= '_' . Horde_String::upper($lang_parts[1]);
        }

        // See if we get a match for this
        if (!empty(self::$config['aliases'][$trans_lang])) {
            return self::$config['aliases'][$trans_lang];
        }

        // If we get that far down, the language cannot be found.
        // Return $trans_lang.
        return $trans_lang;
    }

    /**
     * Returns the charset for the current language.
     *
     * @param boolean $original  If true returns the original charset of the
     *                           translation, the actually used one otherwise.
     *
     * @return string  The character set that should be used with the current
     *                 locale settings.
     */
    static public function getCharset($original = false)
    {
        /* Get cached results. */
        $cacheKey = intval($original);
        $charset = self::_cachedCharset($cacheKey);
        if (!is_null($charset)) {
            return $charset;
        }

        if ($original) {
            $charset = empty(self::$config['charsets'][$GLOBALS['language']])
                ? 'ISO-8859-1'
                : self::$config['charsets'][$GLOBALS['language']];
        } else {
            $browser = new Horde_Browser();
            if ($browser->hasFeature('utf') &&
                (Horde_Util::extensionExists('iconv') ||
                 Horde_Util::extensionExists('mbstring'))) {
                $charset = 'UTF-8';
            }
        }

        if (is_null($charset)) {
            $charset = self::getExternalCharset();
        }

        self::_cachedCharset($cacheKey, $charset);

        return $charset;
    }

    /**
     * Returns the current charset of the environment
     *
     * @return string  The character set that should be used with the current
     *                 locale settings.
     */
    static public function getExternalCharset()
    {
        /* Get cached results. */
        $charset = self::_cachedCharset(2);
        if (!is_null($charset)) {
            return $charset;
        }

        $lang_charset = setlocale(LC_ALL, 0);
        if (strpos($lang_charset, ';') === false &&
            strpos($lang_charset, '/') === false) {
            $lang_charset = explode('.', $lang_charset);
            if ((count($lang_charset) == 2) && !empty($lang_charset[1])) {
                self::_cachedCharset(2, $lang_charset[1]);
                return $lang_charset[1];
            }
        }

        return empty(self::$config['charsets'][$GLOBALS['language']])
            ? 'ISO-8859-1'
            : self::$config['charsets'][$GLOBALS['language']];
    }

    /**
     * Sets or returns the charset used under certain conditions.
     *
     * @param integer $index   The ID of a cache slot. 0 for the UI charset, 1
     *                         for the translation charset and 2 for the
     *                         external charset.
     * @param string $charset  If specified, this charset will be stored in the
     *                         given cache slot. Otherwise the content of the
     *                         specified cache slot will be returned.
     */
    static public function _cachedCharset($index, $charset = null)
    {
        if (is_null($charset)) {
            return isset(self::$_cache['charset'][$index])
                ? self::$_cache['charset'][$index]
                : null;
        } else {
            self::$_cache['charset'][$index] = $charset;
        }
    }

    /**
     * Returns the charset to use for outgoing emails.
     *
     * @return string  The preferred charset for outgoing mails based on
     *                 the user's preferences and the current language.
     */
    static public function getEmailCharset()
    {
        $charset = $GLOBALS['prefs']->getValue('sending_charset');
        if (!empty($charset)) {
            return $charset;
        }

        return isset(self::$config['emails'][$GLOBALS['language']])
            ? self::$config['emails'][$GLOBALS['language']]
            : (isset(self::$config['charsets'][$GLOBALS['language']]) ? self::$config['charsets'][$GLOBALS['language']] : 'ISO-8859-1');
    }

    /**
     * Check to see if character set is valid for htmlspecialchars() calls.
     *
     * @param string $charset  The character set to check.
     *
     * @return boolean  Is charset valid for the current system?
     */
    static public function checkCharset($charset)
    {
        if (is_null($charset) || empty($charset)) {
            return false;
        }

        if (isset(self::$_cache['check'][$charset])) {
            return self::$_cache['check'][$charset];
        } elseif (!isset($check)) {
            $check = array();
        }

        $valid = true;

        ini_set('track_errors', 1);
        @htmlspecialchars('', ENT_COMPAT, $charset);
        if (isset($php_errormsg)) {
            $valid = false;
        }
        ini_restore('track_errors');

        self::$_cache['check'][$charset] = $valid;

        return $valid;
    }

    /**
     * Sets the charset.
     *
     * In general, the applied charset is automatically determined by browser
     * language and browser capabilities and there's no need to manually call
     * setCharset. However for headless (RPC) operations the charset may be
     * set manually to ensure correct character conversion in the backend.
     *
     * @param string $charset  If specified, this charset will be stored in the
     *                         given cache slot.
     * @param integer $index   The ID of a cache slot. 0 for the UI charset, 1
     *                         for the translation charset and 2 for the
     *                         external charset. Defaults to 0: this is the
     *                         charset returned by getCharset and used for
     *                         conversion.
     */
    static public function setCharset($charset, $index = 0)
    {
        self::_cachedCharset($index, $charset);
    }

    /**
     * Sets the charset and reloads the whole NLS environment.
     *
     * When setting the charset, the gettext catalogs have to be reloaded too,
     * to match the new charset, among other things. This method takes care of
     * all this.
     *
     * @param string $charset  The new charset.
     */
    static public function setCharsetEnvironment($charset)
    {
        unset($GLOBALS['language']);
        self::setCharset($charset);
        self::setLanguageEnvironment();
    }

    /**
     * Returns a list of available timezones.
     *
     * @return array  List of timezones.
     */
    static public function getTimezones()
    {
        $timezones = DateTimeZone::listIdentifiers();
        return array_combine($timezones, $timezones);
    }

    /**
     * Sets the current timezone, if available.
     */
    static public function setTimeZone()
    {
        $tz = $GLOBALS['prefs']->getValue('timezone');
        if (!empty($tz)) {
            @date_default_timezone_set($tz);
        }
    }

    /**
     * Get the locale info returned by localeconv(), but cache it, to
     * avoid repeated calls.
     *
     * @return array  The results of localeconv().
     */
    static public function getLocaleInfo()
    {
        if (!isset(self::$_cache['lc_info'])) {
            self::$_cache['lc_info'] = localeconv();
        }

        return self::$_cache['lc_info'];
    }

    /**
     * Get the language info returned by nl_langinfo(), but cache it, to
     * avoid repeated calls.
     *
     * @param const $item  The langinfo item to return.
     *
     * @return array  The results of nl_langinfo().
     */
    static public function getLangInfo($item)
    {
        if (!function_exists('nl_langinfo')) {
            return false;
        }

        if (!isset(self::$_cache['nl_info'])) {
            self::$_cache['nl_info'] = array();
        }
        if (!isset(self::$_cache['nl_info'][$item])) {
            self::$_cache['nl_info'][$item] = nl_langinfo($item);
        }
        return self::$_cache['nl_info'][$item];
    }

    /**
     * Get country information from a hostname or IP address.
     *
     * @param string $host           The hostname or IP address.
     * @param Net_DNS_Resolver $dns  A DNS resolver object used to look up the
     *                               hostname.
     *
     * @return mixed  On success, return an array with the following entries:
     *                'code'  =>  Country Code
     *                'name'  =>  Country Name
     *                On failure, return false.
     */
    static public function getCountryByHost($host, $dns = null)
    {
        /* List of generic domains that we know is not in the country TLD
           list. See: http://www.iana.org/gtld/gtld.htm */
        $generic = array(
            'aero', 'biz', 'com', 'coop', 'edu', 'gov', 'info', 'int', 'mil',
            'museum', 'name', 'net', 'org', 'pro'
        );

        $checkHost = $host;
        if (preg_match('/^\d+\.\d+\.\d+\.\d+$/', $host)) {
            if (is_null($dns)) {
                $checkHost = @gethostbyaddr($host);
            } elseif ($response = $dns->query($host, 'PTR')) {
                foreach ($response->answer as $val) {
                    if (isset($val->ptrdname)) {
                        $checkHost = $val->ptrdname;
                        break;
                    }
                }
            }
        }

        /* Get the TLD of the hostname. */
        $pos = strrpos($checkHost, '.');
        if ($pos === false) {
            return false;
        }
        $domain = Horde_String::lower(substr($checkHost, $pos + 1));

        /* Try lookup via TLD first. */
        if (!in_array($domain, $generic)) {
            $name = self::tldLookup($domain);
            if ($name) {
                return array(
                    'code' => $domain,
                    'name' => $name
                );
            }
        }

        /* Try GeoIP lookup next. */
        $geoip = Horde_Nls_Geoip::singleton(!empty($GLOBALS['conf']['geoip']['datafile']) ? $GLOBALS['conf']['geoip']['datafile'] : null);

        return $geoip->getCountryInfo($checkHost);
    }

    /**
     * Do a top level domain (TLD) lookup.
     *
     * @param string $code  A 2-letter country code.
     *
     * @return mixed  The localized country name, or null if not found.
     */
    static public function tldLookup($code)
    {
        if (!isset(self::$_cache['tld'])) {
            include dirname(__FILE__) . '/Nls/Tld.php';
            self::$_cache['tld'] = $tld;
        }

        $code = Horde_String::lower($code);

        return isset(self::$_cache['tld'][$code])
            ? self::$_cache['tld'][$code]
            : null;
    }

    /**
     * Returns a Horde image link to the country flag.
     *
     * @param string $host           The hostname or IP address.
     * @param Net_DNS_Resolver $dns  A DNS resolver object used to look up the
     *                               hostname.
     *
     * @return string  The image URL, or the empty string on error.
     */
    static public function generateFlagImageByHost($host, $dns = null)
    {
        $data = self::getCountryByHost($host, $dns);
        if ($data === false) {
            return '';
        }

        $img = $data['code'] . '.png';
        return file_exists($GLOBALS['registry']->get('themesfs', 'horde') . '/graphics/flags/' . $img)
            ? Horde::img('flags/' . $img, $data['name'], array('title' => $data['name']))
            : '[' . $data['name'] . ']';
    }

    /**
     * Returns either a specific or all ISO-3166 country names.
     *
     * @param string $code  The ISO 3166 country code.
     *
     * @return mixed  If a country code has been requested will return the
     *                corresponding country name. If empty will return an
     *                array of all the country codes and their names.
     */
    static public function getCountryISO($code = null)
    {
        if (!isset(self::$_cache['iso'])) {
            include dirname(__FILE__) . '/Nls/Countries.php';
            self::$_cache['iso'] = $countries;
        }

        if (empty($code)) {
            return self::$_cache['iso'];
        }

        $code = Horde_String::upper($code);

        return isset(self::$_cache['iso'][$code])
            ? self::$_cache['iso'][$code]
            : null;
    }

}
