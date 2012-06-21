<?php
/**
 * IMP smartmobile view.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('imp', array(
    'impmode' => Horde_Registry::VIEW_SMARTMOBILE
));

$view = new Horde_View(array(
    'templatePath' => IMP_TEMPLATES . '/smartmobile'
));
$view->addHelper('Text');

/* Initialize the IMP_Imap_Tree object. By default, only show INBOX, special
 * mailboxes, and polled mailboxes. */
$imptree = $injector->getInstance('IMP_Imap_Tree');
$imptree->setIteratorFilter(Imp_Imap_Tree::FLIST_POLLED);
$view->tree = $imptree->createTree('smobile_folders', array(
    'poll_info' => true,
    'render_type' => 'IMP_Tree_Jquerymobile'
))->getTree(true);

$imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();
if ($view->allowFolders = $imp_imap->access(IMP_Imap::ACCESS_FOLDERS)) {
    $view->options = IMP::flistSelect(array(
        'heading' => _("This message to"),
        'optgroup' => true,
        'inc_tasklists' => true,
        'inc_notepads' => true,
        'new_mbox' => true
    ));
}

$view->portal = $registry->getServiceLink('portal', 'horde')->setRaw(false);
$view->logout = $registry->getServiceLink('logout')->setRaw(false);
$view->canSearch = $imp_imap->access(IMP_Imap::ACCESS_SEARCH);
$view->canSpam = !empty($conf['spam']['reporting']);
$view->canInnocent = !empty($conf['notspam']['reporting']);

if ($view->canCompose = IMP::canCompose()) {
    /* Setting up identities. */
    $identity = $injector->getInstance('IMP_Identity');
    $view->identities = array();
    foreach ($identity->getSelectList() as $id => $from) {
        $view->identities[] = array(
            'label' => $from,
            'sel' => ($id == $identity->getDefault()),
            'val' => $id
        );
    }

    $view->composeCache = $injector->getInstance('IMP_Factory_Compose')->create()->getCacheId();
    $view->composeLink = $registry->getServiceLink('ajax', 'imp');
    $view->composeLink->pathInfo = 'addAttachment';
}

require IMP_TEMPLATES . '/smartmobile/javascript_defs.php';
$notification->notify(array('listeners' => 'status'));

$page_output->addScriptFile('smartmobile.js');
$page_output->addScriptFile('json2.js', 'horde');

$page_output->header(array(
    'smartmobileinit' => array(
        '$.mobile.page.prototype.options.addBackBtn = true;'
    ),
    'title' => _("Mobile Mail"),
    'view' => $registry::VIEW_SMARTMOBILE
));

echo $view->render('folders');
echo $view->render('mailbox');
echo $view->render('message');
if (IMP::canCompose()) {
    echo $view->render('compose');
}
if ($imp_imap->access(IMP_Imap::ACCESS_SEARCH)) {
    echo $view->render('search');
}
echo $view->render('confirm');
if ($imp_imap->access(IMP_Imap::ACCESS_FOLDERS)) {
    echo $view->render('target');
}
$page_output->footer();
