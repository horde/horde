<?php
/**
 * $Horde: ulaform/index.php,v 1.20 2009-01-06 18:02:20 jan Exp $
 *
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Brent J. Nordquist <bjn@horde.org>
 */

define('ULAFORM_BASE', dirname(__FILE__));
$ulaform_configured = (is_readable(ULAFORM_BASE . '/config/conf.php') &&
                       is_readable(ULAFORM_BASE . '/config/fields.php'));

if (!$ulaform_configured) {
    require ULAFORM_BASE . '/../lib/Test.php';
    Horde_Test::configFilesMissing('Ulaform', ULAFORM_BASE, 'conf.php',
        array('fields.php' => 'This file specifies which fields can be used within Ulaform and any extra parameters that can be set for a field.'));
}

require ULAFORM_BASE . '/forms.php';
