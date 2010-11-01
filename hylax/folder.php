<?php
/**
 * The Hylax script to show a summary view.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

require_once dirname(__FILE__) . '/lib/Application.php';
$hylax = Horde_Registry::appInit('hylax');

$folder = strtolower(Horde_Util::getFormData('folder', 'inbox'));
$path = Horde_Util::getFormData('path');
$base_folders = Hylax::getBaseFolders();

/* Get the list of faxes in folder. */
$folder_list = $hylax->storage->listFaxes($folder);

/* Set up URLs which will be used in the list. */
$view_url  = Horde::url('view.php');
$view_img  = Horde::img('view.gif', _("View"), 'align="middle"');

$download_url = Horde_Util::addParameter($view_url, 'action', 'download');
$download_img  = Horde::img('download.gif', _("Download"));

$edit_url = Horde::url('edit.php');
$edit_label = ($folder == 'pending') ? _("Edit") : _("Resend");
$edit_img = Horde::img('edit.gif', $edit_label);

$del_url  = Horde::url('delete.php');
$del_img  = Horde::img('delete-small.gif', _("Delete"));

$params = array('folder' => $folder, 'path' => $path);
$warn_img = Horde::img('alerts/warning.gif', _("Warning"));
$send_url  = Horde::url('send.php');

$print_url  = Horde::url('print.php');
$print_img  = Horde::img('print.gif', _("Print"));

/* Loop through list and set up items. */
$i = 0;
foreach ($folder_list as $key => $value) {
    $params['fax_id'] = $value['fax_id'];

    /* View. */
    $url = Horde_Util::addParameter($view_url, $params);
    $folder_list[$key]['actions'][] = Horde::link($url, _("View")) . $view_img . '</a>';

    /* Download. */
    $url = Horde_Util::addParameter($download_url, $params);
    $folder_list[$key]['actions'][] = Horde::link($url, _("Download")) . $download_img . '</a>';

    /* Delete. */
    // $url = Horde_Util::addParameter($del_url, $params);
    // $folder_list[$key]['actions'][] = Horde::link($url, _("Delete")) . $del_img . '</a>';

    /* Print. */
    $url = Horde_Util::addParameter($print_url, $params);
    $url = Horde_Util::addParameter($url, 'url', Horde::selfUrl(true));
    $folder_list[$key]['actions'][] = Horde::link($url, _("Print")) . $print_img . '</a>';
    $folder_list[$key]['alt_count'] = $i;
    $i = $i ? 0 : 1;

    /* Format date. */
    $folder_list[$key]['fax_created'] = strftime('%d/%m/%Y %H:%M', $value['fax_created']);

    if (empty($value['fax_number']) && $value['fax_type'] != 0) {
        $url = Horde_Util::addParameter($send_url, 'fax_id', $value['fax_id']);
        $folder_list[$key]['fax_number'] = $warn_img . '&nbsp;' . Horde::link($url, _("Insert Number")) . _("Insert Number") . '</a>';
    } elseif (empty($value['fax_number']) && $value['fax_type'] == 0) {
        $folder_list[$key]['fax_number'] = _("unknown");
    }
    $folder_list[$key]['fax_status'] = $hylax->gateway->getStatus($value['job_id']);
}

/* Set up actions. */
$actions = array();
foreach ($base_folders as $key => $value) {
    if ($folder != $key) {
        $url = Horde_Util::addParameter(Horde::url('folder.php'), 'folder', $key);
        $actions[] = Horde::link($url) . $value . '</a>';
    } else {
        $actions[] = $value;
    }
}

/* Set up template. */
$template = $injector->createInstance('Horde_Template');
if ($folder == 'archive') {
    $template->set('folder_name', $path);
} else {
    $template->set('folder_name', $base_folders[$folder]);
}
$template->set('folder', $folder_list, true);
$template->set('actions', $actions);
$template->set('menu', Hylax::getMenu('string'));

Horde::startBuffer();
$notification->notify(array('listeners' => 'status'));
$template->set('notify', Horde::endBuffer());

require HYLAX_TEMPLATES . '/common-header.inc';
echo $template->fetch(HYLAX_TEMPLATES . '/folder/folder.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
