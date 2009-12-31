<?php
/**
 * Script to set the new language.
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Marko Djukic <marko@oblo.com>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
new Horde_Application();

/* Set the language. */
$_SESSION['horde_language'] = Horde_Nls::select();
$prefs->setValue('language', $_SESSION['horde_language']);

/* Update apps language */
foreach ($registry->listAPIs() as $api) {
    if ($registry->hasAppMethod($api, 'changeLanguage')) {
        $registry->callAppMethod($api, 'changeLanguage');
    }
}

/* Redirect to the url or login page if none given. */
$url = Horde_Util::getFormData('url');
if (empty($url)) {
    $url = Horde::applicationUrl('index.php', true);
}
header('Location: ' . $url);
