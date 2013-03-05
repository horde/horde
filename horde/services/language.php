<?php
/**
 * Script to set the new language.
 *
 * Copyright 2003-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL-2). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 *
 * @author   Marko Djukic <marko@oblo.com>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl LGPL-2
 * @package  Horde
 */

require_once __DIR__ . '/../lib/Application.php';
Horde_Registry::appInit('horde');

$vars = $injector->getInstance('Horde_Variables');

/* Set the language. */
$session->set('horde', 'language', $registry->preferredLang($vars->new_lang));
$prefs->setValue('language', $session->get('horde', 'language'));

/* Update apps language */
foreach ($registry->listApps() as $app) {
    $registry->callAppMethod($app, 'changeLanguage');
}

/* Redirect to the url or login page if none given. */
$url = isset($vars->url)
    ? Horde::url('index.php', true)
    : Horde::url($vars->url, true);
$url->redirect();
