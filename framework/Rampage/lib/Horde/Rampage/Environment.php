<?php
class Horde_Rampage_Environment
{
    public function setup()
    {
        set_magic_quotes_runtime(0);
        $this->reverseMagicQuotes();
        $this->reverseRegisterGlobals();
    }

    /**
     * @author Ilia Alshanetsky <ilia@php.net>
     */
    public function reverseMagicQuotes()
    {
        if (get_magic_quotes_gpc()) {
            $input = array(&$_GET, &$_POST, &$_REQUEST, &$_COOKIE, &$_ENV, &$_SERVER);

            while (list($k, $v) = each($input)) {
                foreach ($v as $key => $val) {
                    if (!is_array($val)) {
                        $key = stripslashes($key);
                        $input[$k][$key] = stripslashes($val);
                        continue;
                    }
                    $input[] =& $input[$k][$key];
                }
            }

            unset($input);
        }
    }

    /**
     * Get rid of register_globals variables.
     *
     * @author Richard Heyes
     * @author Stefan Esser
     * @url http://www.phpguru.org/article.php?ne_id=60
     */
    public function reverseRegisterGlobals()
    {
        if (ini_get('register_globals')) {
            // Variables that shouldn't be unset
            $noUnset = array(
                'GLOBALS',
                '_GET',
                '_POST',
                '_COOKIE',
                '_REQUEST',
                '_SERVER',
                '_ENV',
                '_FILES',
            );

            $input = array_merge(
                $_GET,
                $_POST,
                $_COOKIE,
                $_SERVER,
                $_ENV,
                $_FILES,
                isset($_SESSION) ? $_SESSION : array()
            );

            foreach ($input as $k => $v) {
                if (!in_array($k, $noUnset) && isset($GLOBALS[$k])) {
                    unset($GLOBALS[$k]);
                }
            }
        }
    }
}
