<?php
/**
 * $Id$
 *
 * Copyright 2005 Ben Klang <ben@alkaloid.net>
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 */
@define('SHOUT_BASE', dirname(__FILE__) . '/..');
require_once SHOUT_BASE . '/lib/Dialplan.php';
require_once 'Horde/Variables.php';

$RENDERER = &new Horde_Form_Renderer();

$empty = '';

$vars = &Variables::getDefaultVariables($empty);
$formname = $vars->get('formname');
$context = $vars->get('context');
$extension = $vars->get('extension');
$dialplan = &$shout->getDialplan($context);

$ExtensionDetailsForm = &Horde_Form::singleton('ExtensionDetailsForm', $vars);
$ExtensionDetailsFormValid = $ExtensionDetailsForm->validate($vars, true);

$ExtensionDetailsForm->open($RENDERER, $vars, 'dialplan.php', 'post');
$ExtensionDetailsForm->preserveVarByPost($vars, "section");
$ExtensionDetailsForm->preserve($vars);
require SHOUT_TEMPLATES . '/table-limiter-begin.inc';
$RENDERER->beginActive($ExtensionDetailsForm->getTitle());
$RENDERER->renderFormActive($ExtensionDetailsForm, $vars);
# FIXME Maybe this should be a subclass inheriting from the From/Renderer object
# instead of a simple include?
$i = 0;
require SHOUT_TEMPLATES . '/dialplan/priority-form-begin.inc';
foreach ($dialplan['extensions'][$extension] as $priority => $application) {
    require SHOUT_TEMPLATES . '/dialplan/priority-form-line.inc';
    $i++;
}
require SHOUT_TEMPLATES . '/dialplan/priority-form-end.inc';
$RENDERER->submit('Add Priority');
$RENDERER->submit('Add 5 Priorities');
$RENDERER->submit('Save');
$RENDERER->end();
$ExtensionDetailsForm->close($RENDERER);
require SHOUT_TEMPLATES . '/table-limiter-end.inc';