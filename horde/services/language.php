<?php
/**
 * Script to set the new language.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Marko Djukic <marko@oblo.com>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('horde');

/* Set the language. */
$session['horde:language'] = $registry->preferredLanguage(Horde_Util::getForm('new_lang'));
$prefs->setValue('language', $sessoin['horde:language']);

/* Update apps language */
foreach ($registry->listApps() as $app) {
    if ($registry->hasAppMethod($app, 'changeLanguage')) {
        $registry->callAppMethod($app, 'changeLanguage');
    }
}

/* Redirect to the url or login page if none given. */
$url = Horde_Util::getFormData('url');
$url = empty($url)
    ? Horde::url('index.php', true)
    : $url;

$url->redirect();
