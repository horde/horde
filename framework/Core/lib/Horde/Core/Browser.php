<?php
/**
 * The Horde_Core_Browser class extends the base Horde_Browser class by
 * allowing storage of IE version information in order to identify additional
 * browser quirks.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Core
 */
class Horde_Core_Browser extends Horde_Browser
{
    /**
     */
    public function match($userAgent = null, $accept = null)
    {
        parent::match($userAgent, $accept);

        if ($this->isBrowser('msie')) {
            /* IE 6 (pre-SP1) and 5.5 (pre-SP1) have buggy compression.
             * The versions affected are as follows:
             * 6.00.2462.0000  Internet Explorer 6 Public Preview (Beta)
             * 6.00.2479.0006  Internet Explorer 6 Public Preview (Beta) Refresh
             * 6.00.2600.0000  Internet Explorer 6 (Windows XP)
             * 5.50.3825.1300  Internet Explorer 5.5 Developer Preview (Beta)
             * 5.50.4030.2400  Internet Explorer 5.5 & Internet Tools Beta
             * 5.50.4134.0100  Internet Explorer 5.5 for Windows Me (4.90.3000)
             * 5.50.4134.0600  Internet Explorer 5.5
             * 5.50.4308.2900  Internet Explorer 5.5 Advanced Security Privacy Beta
             *
             * See:
             * ====
             * http://support.microsoft.com/kb/164539;
             * http://support.microsoft.com/default.aspx?scid=kb;en-us;Q312496)
             * http://support.microsoft.com/default.aspx?scid=kb;en-us;Q313712
             */
            $ie_vers = $this->getIEVersion();
            $buggy_list = array(
                '6,00,2462,0000', '6,0,2462,0', '6,00,2479,0006',
                '6,0,2479,0006', '6,00,2600,0000', '6,0,2600,0',
                '5,50,3825,1300', '5,50,4030,2400', '5,50,4134,0100',
                '5,50,4134,0600', '5,50,4308,2900'
            );
            if (!is_null($ie_vers) && in_array($ie_vers, $buggy_list)) {
                $this->setQuirk('buggy_compression');
            }
        }
    }

    /**
     * Sets the IE version in the session.
     *
     * @param string $ver  The IE Version string.
     */
    public function setIEVersion($ver)
    {
        $GLOBALS['session']->set('horde', 'ie_version', $ver);
    }

    /**
     * Returns the IE version stored in the session, if available.
     *
     * @return mixed  The IE Version string or null if no string is stored.
     */
    public function getIEVersion()
    {
        return isset($GLOBALS['session'])
            ? $GLOBALS['session']->get('horde', 'ie_version')
            : null;
    }
}
