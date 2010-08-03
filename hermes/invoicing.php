<?php
/**
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Duck <duck@obala.net>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
$hermes = Horde_Registry::appInit('hermes');

require_once 'Horde/Form.php';
require_once 'Horde/Form/Renderer.php';
require_once 'Horde/Form/Type/tableset.php';

$hours = $hermes->driver->getHours(array('billable' => true,
                                 'submitted' => true));
if (is_a($hours, 'PEAR_Error')) {
    $notification->push($hours->getMessage(), 'horde.error');
    Horde::applicationUrl('time.php')->redirect();
}
if (empty($hours)) {
    $notification->push(_("There is no submitted billable hours."), 'horde.warning');
    Horde::applicationUrl('time.php')->redirect();
}
if (!$registry->hasMethod('invoices/save')) {
    $notification->push(_("Invoicing system is not installed."), 'horde.warning');
    Horde::applicationUrl('time.php')->redirect();
}

$headers = array(
    'client' => _("Client"),
    'employee' => _("Employee"),
    '_type_name' => _("Job Type"),
    'rate' => _("Rate"),
    'hours' => _("Hours"),
    'total' => _("Total"),
    'date' => _("Date"),
    'description' => _("Description"),
    'note' => _("Cost Object")
);

$clients = Hermes::listClients();
$df = $GLOBALS['prefs']->getValue('date_format');

$list = array();
$client_keys = array();
foreach ($hours as $hour) {
    $id = (int)$hour['id'];
    $client_keys[$id] = $hour['client'];
    $list[$id] = array(
        'client' => $clients[$hour['client']],
        'employee' => $hour['employee'],
        '_type_name' => $hour['_type_name'],
        'rate' => $hour['rate'],
        'hours' => $hour['hours'],
        'total' => $hour['rate'] * $hour['hours'],
        'date' => strftime($df, $hour['date']),
        'description' => $hour['description'],
        '_costobject_name' => $hour['_costobject_name'],
    );
}

$title = _("Create invoice");
$vars = Horde_Variables::getDefaultVariables();
$form = new Horde_Form($vars, $title, 'create_invoice');

$type_params = array(array(1 => _("Yes"), 0 => _("No")));
$form->addVariable(_("Combine same clients in one invoice"), 'combine', 'enum', true, false, null, $type_params);
$v = &$form->addVariable(_("Select hours to be invoiced"), 'hours', 'tableset', true, false, false, array($list, $headers));
$v->setDefault(array_keys($list));

if ($form->validate()) {
    $form->getInfo(null, $info);

    $groups = array();
    if ($info['combine']) {
        foreach ($info['hours'] as $id) {
            $client = $client_keys[$id];
            if (isset($groups[$client])) {
                $groups[$client]['hours'][] = $id;
            } else {
                $groups[$client] = array('client' => $client,
                                         'hours' => array($id));
            }
        }
    } else {
        foreach ($info['hours'] as $id) {
            $groups[] = array('client' => $hours[$id]['client'],
                              'hours' => array($id));
        }
    }

    foreach ($groups as $group) {

        $invoice = array();
        $invoice['client'] = array('id' => $group['client']);
        $invoice['invoice'] = array('type' =>    $conf['invoices']['params']['type'],
                                    'status' =>  $conf['invoices']['params']['status'],
                                    'expire' =>  $conf['invoices']['params']['expire'],
                                    'place' =>   $conf['invoices']['params']['place'],
                                    'service' => date('Y-m-d'));

        $invoice['articles'] = array();
        foreach ($group['hours'] as $hour) {
            $invoice['articles'][] = array('name' => $list[$hour]['description'],
                                           'price' => $list[$hour]['rate'],
                                           'qt' => $list[$hour]['hours'],
                                           'discount' => 0);
        }

        $invoice_id = $registry->call('invoices/save', array($invoice));
        if (is_a($invoice_id, 'PEAR_Error')) {
            $notification->push($invoice_id->getMessage(), 'horde.error');
        } else {
            $msg = sprintf(_("Invoice for client %s successfuly created."), $clients[$group['client']]);
            $notification->push($msg, 'horde.success');
        }
    }

    Horde::applicationUrl('time.php')->redirect();
}

require HERMES_TEMPLATES . '/common-header.inc';
require HERMES_TEMPLATES . '/menu.inc';

$renderer = new Horde_Form_Renderer(array('varrenderer_driver' => 'tableset_html'));
$form->renderActive($renderer, null, Horde::applicationUrl('invoicing.php'), 'post');

require $registry->get('templates', 'horde') . '/common-footer.inc';
