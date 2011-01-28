<?php
/**
 * Copyright 2005-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Jason M. Felice <jason.m.felice@gmail.com>
 */
require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('hermes');

$vars = Horde_Variables::getDefaultVariables();

switch ($vars->get('formname')) {
case 'hermes_form_deliverable':
    $form = new Hermes_Form_Deliverable($vars);
    $form->validate($vars);
    if ($form->isValid()) {
        try {
            $form->getInfo($vars, $info);
            if (!empty($info['deliverable_id'])) {
                $info['id'] = $info['deliverable_id'];
                if (empty($info['parent'])) {
                    $origdeliv = $GLOBALS['injector']->getInstance('Hermes_Driver')->getDeliverableByID($info['id']);
                    $info['parent'] = $origdeliv['parent'];
                }
            }
            $res = $GLOBALS['injector']->getInstance('Hermes_Driver')->updateDeliverable($info);
            $notification->push(_("Deliverable saved successfully."), 'horde.success');
            $vars = new Horde_Variables(array('client_id' => $vars->get('client_id')));
        } catch (Exception $e) {
            $notification->push(sprintf(_("Error saving deliverable: %s"), $res->getMessage()), 'horde.error');
        }
    }
    break;

case 'deletedeliverable':
    try {
        $res = $GLOBALS['injector']->getInstance('Hermes_Driver')->deleteDeliverable($vars->get('delete'));
        $notification->push(_("Deliverable successfully deleted."), 'horde.success');
    } catch (Exception $e) {
        $notification->push(sprintf(_("Error deleting deliverable: %s"), $res->getMessage()), 'horde.error');
    }
    break;
}

$title = _("Deliverables");
require $registry->get('templates', 'horde') . '/common-header.inc';
require HERMES_TEMPLATES . '/menu.inc';

$renderer = new Horde_Form_Renderer();

if (!$vars->exists('deliverable_id') && !$vars->exists('new')) {
    $clientSelector = new Hermes_Form_Deliverable_ClientSelector($vars);
    $clientSelector->renderActive($renderer, $vars, 'deliverables.php', 'post');
}

if ($vars->exists('deliverable_id') || $vars->exists('new')) {
    if ($vars->exists('deliverable_id')) {
        $deliverable = $GLOBALS['injector']->getInstance('Hermes_Driver')->getDeliverableByID($vars->get('deliverable_id'));
        foreach ($deliverable as $name => $value) {
            $vars->set($name, $value);
        }
    }
    $form = new Hermes_Form_Deliverable($vars);
    $form->renderActive($renderer, $vars, 'deliverables.php', 'post');
} elseif ($vars->exists('client_id')) {
    $clients = Hermes::listClients();
    $clientname = $clients[$vars->get('client_id')];

    $deliverables = $GLOBALS['injector']->getInstance('Hermes_Driver')->listDeliverables(array('client_id' => $vars->get('client_id')));
    $tree = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Tree')->create('deliverables', 'Javascript');
    $tree->setOption(array('class'       => 'item',
                           'alternate'   => true));

    foreach ($deliverables as $deliverable) {
        $params = array();
        $params['url'] = Horde::url('deliverables.php');
        $params['url'] = Horde_Util::addParameter($params['url'], array('deliverable_id' => $deliverable['id'], 'client_id' => $vars->get('client_id')));
        $params['title'] = sprintf(_("Edit %s"), $deliverable['name']);

        $newdeliv = '&nbsp;' . Horde::link(Horde_Util::addParameter(Horde::url('deliverables.php'), array('new' => 1, 'parent' => $deliverable['id'], 'client_id' => $vars->get('client_id'))), _("New Sub-deliverable")) . Horde::img('newdeliverable.png', _("New Sub-deliverable")) . '</a>';

        $deldeliv = '&nbsp;' . Horde::link(Horde_Util::addParameter(Horde::url('deliverables.php'), array('formname' => 'deletedeliverable', 'delete' => $deliverable['id'], 'client_id' => $vars->get('client_id'))), _("Delete This Deliverable")) . Horde::img('delete.png', _("Delete This Deliverable"), '') . '</a>';

        /* Calculate the node's depth. */
        $depth = 0;
        $iterator = $deliverable;
        while (!empty($iterator['parent'])) {
            $depth++;
            $iterator = $deliverables[$iterator['parent']];
        }

        $tree->addNode($deliverable['id'], $deliverable['parent'],
                       $deliverable['name'], $depth, true, $params,
                       array($newdeliv, $deldeliv), array());
    }

    require HERMES_TEMPLATES . '/deliverables/list.inc';
}

require $registry->get('templates', 'horde') . '/common-footer.inc';
