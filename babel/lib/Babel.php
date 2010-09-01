<?php
/**
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Joel Vandal <joel@scopserv.com>
 * @package Babel
 */

class Babel {

    function callHook($fname, $info) {
	/* Check if an hooks file exist */
	if (file_exists(BABEL_BASE . '/config/hooks.php')) {
	    include_once BABEL_BASE . '/config/hooks.php';

	    $func = '_babel_hook_' . $fname;

	    if (function_exists($func)) {
		$res = call_user_func($func, $info);
	    } else {
		Translate_Display::warning(sprintf(_("Function doesn't exist: %s"), $func));
	    }
	} else {
	    Translate_Display::warning(_("Hook file doesn't exist"));
	}
    }

    function displayLanguage() {
	global $lang, $app;

	if (!isset(Horde_Nls::$config['languages'][$lang])) {
	    return;
	}

	$res = sprintf(_("Language: %s (%s)"), Horde_Nls::$config['languages'][$lang], $lang);
	if ($app) {
	    $res .= '&nbsp; | &nbsp; ' . sprintf(_("Module: %s"), $app);
	}

	return $res;
    }


    function ModuleSelection() {
	$html = '';
	$html .= '<span style="float:right">';
	$html .= '<form action="' . Horde::selfUrl() . '" method="post" name="moduleSelector">';
	$html .= '<select name="module" onchange="moduleSubmit()">';

	$apps = array('ALL' => _("All Applications")) +  Babel::listApps();

	foreach($apps as $app => $desc) {
	    if (!Babel::hasPermission("module:$app")) {
		continue;
	    }

	    if (Horde_Util::getFormData('module') == $app) {
		$html .= '<option class="control" value="' . $app . '" selected>' .  '+ ' . $desc;
	    } else {
		$html .= '<option value="' . $app . '">' . '&#8211; ' . $desc;
	    }
	}

	$html .= '</select>';
	$html .= '</form>';
	$html .= '</span>';

	$html .= '<script language="JavaScript" type="text/javascript">' . "\n";
	$html .= '<!--' . "\n";
	$html .= 'var loading;' . "\n";
	$html .= 'function moduleSubmit()' . "\n";
	$html .= '{' . "\n";
	$html .= 'document.moduleSelector.submit();' . "\n";
	$html .= 'return false;' . "\n";
	$html .= '}' . "\n";
	$html .= '// -->' . "\n";
	$html .= '</script>' . "\n";
	return $html;
    }

    function LanguageSelection() {
	global $app;

	$html = '';
	$html .= '<span style="float:right">';
	$html .= '<form action="' . Horde::selfUrl() . '" method="post" name="languageSelector">';
	$html .= '&nbsp;';
	$html .= '<input type="hidden" name="module" value="' . $app . '">';
	$html .= '<select name="display_language" onchange="languageSubmit()">';

	$tests =  Horde_Nls::$config['languages'];

	// Unset English
	unset($tests['en_US']);

	foreach($tests as $dir => $desc) {
	    if (!Babel::hasPermission("language:$dir")) {
		continue;
	    }

	    if (isset($_SESSION['babel']['language']) && $dir == $_SESSION['babel']['language']) {
		$html .= '<option class="control" value="' . $dir . '" selected>' .  '+ ' . $desc;
	    } else {
		$html .= '<option value="' . $dir . '">' . '&#8211; ' . $desc;
	    }
	}

	$html .= '</select>';
	$html .= '&nbsp;';
	$html .= '</form>';
	$html .= '</span>';

	$html .= '<script language="JavaScript" type="text/javascript">' . "\n";
	$html .= '<!--' . "\n";
	$html .= 'var loading;' . "\n";
	$html .= 'function languageSubmit()' . "\n";
	$html .= '{' . "\n";
	$html .= 'document.languageSelector.submit();' . "\n";
	$html .= 'return false;' . "\n";
	$html .= '}' . "\n";
	$html .= '// -->' . "\n";
	$html .= '</script>' . "\n";
	return $html;
    }

    function listApps($all = false) {
	global $registry;

	$res = array();

	if ($all) {
	    $res['ALL'] = _("All Applications");
	}

	foreach ($registry->applications as $app => $params) {
	    if ($params['status'] == 'heading' || $params['status'] == 'block') {
		continue;
	    }

	    if (isset($params['fileroot']) && !is_dir($params['fileroot'])) {
		continue;
	    }

	    if (preg_match('/_reports$/', $app) || preg_match('/_tools$/', $app)) {
		continue;
	    }

	    if (Babel::hasPermission("module:$app")) {
		$res[$app] = sprintf("%s (%s)", $params['name'], $app);
	    }
	}
	return $res;
    }

    /**
     * Returns the value of the specified permission for $userId.
     *
     * @return mixed  Does user have $permission?
     */
    function hasPermission($permission, $filter = null, $perm = null)
    {
	$userId = $GLOBALS['registry']->getAuth();
	$admin = ($userId == 'admin') ? true : false;
    $perms = $GLOBALS['injector']->getInstance('Horde_Perms');

	if ($admin || !$perms->exists('babel:' . $permission)) {
	    return true;
	}

	$allowed = $perms->getPermissions('babel:' . $permission);

	switch ($filter) {
	 case 'tabs':
	    if ($perm) {
		$allowed  = $perms->hasPermission('babel:' . $permission, $GLOBALS['registry']->getAuth(), $perm);
	    }
	    break;
	}
	return $allowed;
    }

    /**
     * Get the module main Menu.
     **/
    function getMenu()
    {
        global $registry;

        $menu = new Horde_Menu();

        $menu->addArray(array('url' => Horde::url('index.php'),
            'text' => _("_General"),
            'icon' => 'list.png'));

        if (Babel::hasPermission('view')) {
            $menu->addArray(array('url' => Horde::url('view.php'),
                'text' => _("_View"),
                'icon' => 'view.png'));
        }

        if (Babel::hasPermission('stats')) {
            $menu->addArray(array('url' => Horde::url('stats.php'),
                'text' => _("_Stats"),
                'icon' => 'extract.png'));
        }

        if (Babel::hasPermission('extract')) {
            $menu->addArray(array('url' => Horde::url('extract.php'),
                'text' => _("_Extract"),
                'icon' => 'extract.png'));
        }

        if (Babel::hasPermission('make')) {
            $menu->addArray(array('url' => Horde::url('make.php'),
                'text' => _("_Make"),
                'icon' => 'make.png'));
        }

        if (Babel::hasPermission('upload')) {
            $menu->addArray(array('url' => Horde::url('upload.php'),
                'text' => _("_Upload"),
                'icon' => 'upload.png'));
        }

        return $menu;
    }

    /**
     * Send an Email.
     **/
    function sendEmail($email, $type = 'html', $attachments = array()) {
	global $client, $scopserv;

	include_once("Mail.php");
	include_once("Mail/mime.php");

	$headers["From"]    = $email['from'];
	$headers["Subject"] = $email['subject'];

	$mime = new Mail_Mime();
	if ($type == 'html') {
	    $mime->setHtmlBody($email['content']);
	} else {
	    $mime->setTxtBody($email['content']);
	}

	if (!empty($attachments)) {
	    foreach ($attachments as $info) {
		$mime->addAttachment($info['file'],
				     $info['type'],
				     $info['name'], false);
	    }
	}

	$body = $mime->get();
	$hdrs = $mime->headers($headers);

	return $GLOBALS['injector']->getInstance('Horde_Mail')->send($email['to'], $hdrs, $body);
    }


    function RB_init() {
	Horde::addScriptFile('effects.js', 'horde');
	Horde::addScriptFile('redbox.js', 'horde');
    }

    function RB_start($secs = 30) {

	$msg = '';
	$msg .= '<table width=100% id="RB_confirm"><tr><td>';
	$msg .= '<b>' . _("Please be patient ...") . '</b>';
	$msg .= '<br />';
	$msg .= '<br />';
	if ($secs < 60) {
	    $msg .= addslashes(sprintf(_("Can take up to %d seconds !"), $secs));
	} else {
	    $min = intval($secs / 60);
	    if ($min == 1) {
		$msg .= addslashes(_("Can take up to 1 minute !"));
	    } else {
		$msg .= addslashes(sprintf(_("Can take up to %d minutes !"), $min));
	    }
	}

	$msg .= '</td><td><img src="themes/graphics/redbox_spinner.gif">';

	$msg .= '</td></tr></table>';
	echo '<script>';
	echo 'RedBox.loading();';
	echo "RedBox.showHtml('$msg');";
	echo '</script>';
	flush();
    }

    function RB_close() {
	echo '<script>';
	echo 'RedBox.close();';
	echo '</script>';
    }

}
