<?php
/**
 * Copyright 2006-2007 Ben Klang <ben@alkaloid.net>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

require_once dirname(__FILE__) . '/lib/Application.php';
$beatnik = Horde_Registry::appInit('beatnik');

require_once BEATNIK_BASE . '/lib/Forms/EditRecord.php';

$domains = array();
if (Horde_Util::getGet('domain') == 'current') {
    $url = Horde::applicationUrl('viewzone.php');
    $domains[] = $_SESSION['beatnik']['curdomain'];
} elseif (Horde_Util::getGet('domain') == 'all') {
    $url = Horde::applicationUrl('listzones.php');
    foreach (Beatnik::needCommit() as $domain) {
        $domains[] = $beatnik->driver->getDomain($domain);
    }
}

foreach ($domains as $domain) {
    $_SESSION['beatnik']['curdomain'] = $domain;
    $vars = new Horde_Variables;

    $vars->set('rectype', 'soa');
    foreach ($domain as $field => $value) {
        $vars->set($field, $value);
    }
    $vars->set('serial', Beatnik::incrementSerial($domain['serial']));

    $form = new EditRecord($vars);
    $form->useToken(false);
    $form->setSubmitted(true);
    if ($form->validate($vars)) {
        $form->getInfo($vars, $info);

        try {
            $result = $beatnik->driver->saveRecord($info);
        } catch (Exception $e) {
            $notification->push($e->getMessage(), 'horde.error');
        }
        $notification->push(sprintf(_('Zone serial for %s incremented.'), $domain['zonename']), 'horde.success');
    } else {
        $notification->push(sprintf(_("Unable to construct valid SOA for %s.  Not incrementing serial."), $domain['zonename']), 'horde.error');
    }
}

$url->redirect();
