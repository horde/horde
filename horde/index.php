<?php
/**
 * Horde redirection script.
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('horde', array('authentication' => 'none', 'nologintasks' => true));

$main_page = Horde_Util::nonInputVar('horde_login_url', Horde_Util::getFormData('url'));

// Break up the requested URL in $main_page and run some sanity checks
// on it to prevent phishing and XSS attacks. If any of the checks
// fail, $main_page will be set to null.
if (!empty($main_page)) {
    // Mute errors in case of unparseable URLs
    $req = @parse_url($main_page);

    // We assume that any valid redirect URL will be in the same
    // cookie domain. This helps prevent rogue off-site Horde installs
    // from mimicking the real server.
    if (isset($req['host'])) {
        $qcookiedom = preg_quote($conf['cookie']['domain']);
        if (!preg_match('/' . $qcookiedom . '$/', $req['host'])) {
            $main_page = null;
        }
    }

    // Protocol whitelist: If the URL is fully qualified ...
    if (isset($req['scheme']) ||
        isset($req['host']) ||
        isset($req['port']) ||
        isset($req['user']) ||
        isset($req['pass'])) {
        // ... make sure it is either http or https.
        $allowed_protocols = array('http', 'https');
        if (empty($req['scheme']) ||
            !in_array($req['scheme'], $allowed_protocols)) {
            $main_page = null;
        }
    }
}

if ($main_page) {
    $main_page = new Horde_Url($main_page);
} else {
    /* Always redirect to login page if there is no incoming URL and nobody
     * is authenticated. */
    if (!$registry->getAuth()) {
        $main_page = Horde::url('login.php', true);
    } else {
        // Search for a user-specified initial application.
        // Force mobile agents to the mobile portal, at least until we have
        // a default mobile app pref.
        if ($browser->isMobile()) {
            Horde::getServiceLink('portal')->redirect();
        }
        
        $initial_app = $prefs->getValue('initial_application');
        if (!empty($initial_app) &&
            ($initial_app != 'horde') &&
            $registry->hasPermission($initial_app)) {
            $main_page = Horde::url($initial_app, true);
        } else {
            /* Next, try the initial horde page if it is something other than
             * index.php or login.php, since that would lead to inifinite
             * loops. */
            if (!empty($registry->applications['horde']['initial_page']) &&
                !in_array($registry->applications['horde']['initial_page'], array('index.php', 'login.php'))) {
                $main_page = Horde::url($registry->applications['horde']['initial_page'], true);
            } else {
                /* Finally, fallback to the portal page. */
                $main_page = Horde::getServiceLink('portal');
            }
        }
    }
}

$main_page->redirect();
