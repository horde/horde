<?php
/**
 * $Horde: luxor/symbol.php,v 1.6 2009/06/10 19:54:42 chuck Exp $
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author Jan Schneider <jan@horde.org>
 * @author Mike Cochrane <mike@graftonhall.co.nz>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('luxor');

$ident = Horde_Util::getFormData('i');

// Change source if the symbol isn't from the current source.
$symbolSource = $index->getSourceBySymbol($ident);
if (!$symbolSource) {
    throw new Horde_Exception(_("Symbol not found"));
}
if ($symbolSource != $sourceid) {
    $source = $sources[$symbolSource];
    $index = Luxor_Driver::factory($symbolSource);
}

$declarations = $index->getIndex($ident);
$sorted = array();
foreach ($declarations as $decl) {
    $sorted[$decl['declaration']][] = array('filename' => $decl['filename'],
                                            'line' => $decl['line']);
}

$ds = array();
foreach ($sorted as $type => $locations) {
    $d = array();
    $d['title'] = sprintf(_("Declared as a %s"), $type);
    $d['files'] = array();

    Horde_Array::arraySort($locations, 'filename', 0, false);
    foreach ($locations as $loc) {
        $href = Luxor::url($loc['filename'], array(), 'l' . $loc['line']);
        $d['files'][] = '<a href="' . $href . '">' . $loc['filename'] . ' ' . sprintf(_("Line %s"), $loc['line']) . '</a>';
    }
    $ds[] = $d;
}

$references = $index->getReference($ident);
Horde_Array::arraySort($references, 'filename', 0, false);

$curfile = '';
$rs = array();
$r = array();
foreach ($references as $info) {
    if ($curfile != $info['filename']) {
        if ($r) {
            $rs[] = $r;
        }

        $curfile = $info['filename'];

        $r = array();
        $r['file'] = '<a href="' . Luxor::url($info['filename']) . '">' . htmlspecialchars($info['filename']) . '</a>';
        $r['lines'] = array();
    }

    $r['lines'][] = '<a href="' . Luxor::url($info['filename'], array(), 'l' . $info['line']) . '">' . sprintf(_("Line %s"), $info['line']) . '</a>';
}
if ($r) {
    $rs[] = $r;
}

$title = sprintf(_("%s :: Symbol \"%s\""), $source['name'], $index->symname($ident));
require LUXOR_TEMPLATES . '/common-header.inc';
require LUXOR_TEMPLATES . '/menu.inc';

$view = new Horde_View(array('templatePath' => LUXOR_TEMPLATES));
$view->addHelper('Text');
$view->title = $title;
$view->declarations = $ds;
$view->references = $rs;
echo $view->render('symbol.html.php');

require $registry->get('templates', 'horde') . '/common-footer.inc';
