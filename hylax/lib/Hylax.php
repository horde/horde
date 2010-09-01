<?php
/**
 * The Hylax:: class providing some support functions to the Hylax
 * module.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Marko Djukic <marko@oblo.com>
 * @package Hylax
 */
class Hylax {

    function getBaseFolders()
    {
        return array('inbox'   => _("Inbox"),
                     'outbox'  => _("Outbox"));
                     //'sent'    => _("Sent"),
                     //'pending' => _("Pending"));
    }

    function getStates()
    {
        return array('D' => _("Done"),
                     'F' => _("Failed"),
                     'S' => _("Sending"),
                     'W' => _("Waiting"));
    }

    function getStatCols()
    {
        return array('job_id', 'time', 'state', 'owner', 'number', 'pages', 'tot_pages', 'dials', 'duration', 'status');
    }

    function getVFSPath($folder)
    {
        return '.horde/fax/' . $folder;
    }

    function getImage($fax_id, $page, $preview = false)
    {
        $data = $GLOBALS['hylax_storage']->getFaxData($fax_id);

        /* Get the image. */
        require_once HYLAX_BASE . '/lib/Image.php';
        $image = new Hylax_Image();
        $image->loadData($data);
        $image->getImage($page, $preview);
    }

    function getPDF($fax_id)
    {
        $data = $GLOBALS['hylax_storage']->getFaxData($fax_id);

        /* Get the pdf. */
        require_once HYLAX_BASE . '/lib/Image.php';
        $image = new Hylax_Image();
        $image->loadData($data);
        $image->getPDF();
    }

    function printFax($fax_id)
    {
        $data = $GLOBALS['hylax_storage']->getFaxData($fax_id);

        $command = $GLOBALS['conf']['fax']['print'];
        $descriptorspec = array(0 => array("pipe", "r"),
                                1 => array("pipe", "w"),
                                2 => array("pipe", "w"));

        /* Set up the process. */
        $process = proc_open($command, $descriptorspec, $pipes);
        if (!is_resource($process)) {
            return PEAR::raiseError('fail');
        }

        fwrite($pipes[0], $data);
        fclose($pipes[0]);

        $output = '';
        while (!feof($pipes[1])) {
            $output .= fgets($pipes[1], 1024);
        }
        fclose($pipes[1]);

        $stderr = '';
        while (!feof($pipes[2])) {
            $stderr .= fgets($pipes[2], 1024);
        }
        fclose($pipes[2]);

        proc_close($process);

        if ($stderr) {
            return PEAR::raiseError($stderr);
        }

        return true;
    }

    function getPages($fax_id, $num_pages)
    {
        $pages = array();
        $params = array('fax_id'  => $fax_id,
                        'preview' => 1);

        /* Set the params for the popup to view the full size pages. */
        Horde::addScriptFile('popup.js', 'horde');
        $popup_w = 620;
        $popup_h = 860;

        for ($i = 0; $i < $num_pages; $i++) {
            $params['page'] = $i;
            $url = Horde_Util::addParameter('img.php', $params);
            $img = Horde::img($url, sprintf(_("View page %s"), $i+1), '', $GLOBALS['registry']->get('webroot'));

            $full_url = Horde::url(Horde_Util::addParameter('img.php', array('fax_id' => $fax_id, 'page' => $i)));

            $pages[] = Horde::link('', sprintf(_("View page %s"), $i+1), '', '', "popup('$full_url', $popup_w, $popup_h); return false;") . $img . '</a>';
        }
        return $pages;
    }

    function getMenu($returnType = 'object')
    {
        global $registry;

        $menu = new Horde_Menu();

        $menu->addArray(array('url' => Horde::url('summary.php'),
                              'text' => _("Summary"),
                              'icon' => 'fax.png',
                              'icon_path' => Horde_Themes::img()));

        $menu->addArray(array('url' => Horde::url('folder.php'),
                              'text' => _("Folders"),
                              'icon' => 'folder.png',
                              'icon_path' => Horde_Themes::img()));

        $menu->addArray(array('url' => Horde::url('compose.php'),
                              'text' => _("Compose"),
                              'icon' => 'compose.png',
                              'icon_path' => Horde_Themes::img()));

        if ($returnType == 'object') {
            return $menu;
        } else {
            return $menu->render();
        }
    }

}
