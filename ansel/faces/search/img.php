<?php
/**
 * Process an single image (to be called by ajax)
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 */

require_once dirname(__FILE__) . '/../../lib/Application.php';
Horde_Registry::appInit('ansel');

$thumb = Horde_Util::getGet('thumb');
$tmp = Horde::getTempDir();
$path = $tmp . '/search_face_' . ($thumb ? 'thumb_' : '') .  $registry->getAuth() . Ansel_Faces::getExtension();

header('Content-type: image/' . $conf['image']['type']);
readfile($path);
