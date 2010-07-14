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

require_once dirname(__FILE__) . '/lib/base.php';

require_once 'Horde/Lock.php';

$meta_params = array(
		     "Project-Id-Version" => @$_SESSION['babel']['language'],
		     "Report-Msgid-Bugs-To" => "support@scopserv.com",
		     "POT-Creation-Date" => "",
		     "PO-Revision-Date" => "",
		     "Last-Translator" => "",
		     "Language-Team" => "",
		     "MIME-Version" => "1.0",
		     "Content-Type" => "text/plain; charset=utf-8",
		     "Content-Transfer-Encoding" => "8bit",
		     "Plural-Forms" => "nplurals=2; plural=(n > 1);");

$app      = Horde_Util::getFormData('module');
$editmode = Horde_Util::getFormData('editmode', 0);
$cstring  = Horde_Util::getFormData('cstring');
$page     = Horde_Util::getFormData('page', 0);
$filter   = Horde_Util::getFormData('filter');
$search   = Horde_Util::getFormData('search');

if ($app) {
    /* Render the page. */
    Babel::RB_init();
}

/* Render the page. */
require BABEL_TEMPLATES . '/common-header.inc';

if ($app) {
    if ($editmode) {
	Babel::RB_start(10);
    } else {
	Babel::RB_start(60);
    }
}

echo $template->fetch(BABEL_TEMPLATES . '/layout.html');

$app = Horde_Util::getFormData('module');
$show = 'edit';
$vars = Horde_Variables::getDefaultVariables();

if ($app) {
    $napp = ($app == 'horde') ? '' : $app;
    $pofile = HORDE_BASE . '/' . $napp . '/po/' . $lang . '.po';
    $po = new File_Gettext_PO();
    $po->load($pofile);

    // Set Scope
    $lockscope = sprintf("babel-%s-%s", $app, $lang);

    // Initialize Horde_Lock class
    $locks = $injector->getInstance('Horde_Lock');

//    $curlocks = $locks->getLocks($lockscope);
//    var_dump($curlocks);
}

//

$f_cancel = Horde_Util::getFormData('cancel');
$f_save = Horde_Util::getFormData('submit');

if (($f_save || $f_cancel) && $cstring) {
    if ($curlock = $locks->getLocks(md5($cstring), $lockscope)) {
	foreach($curlock as $lid => $linfo) {
	    if ($linfo['lock_scope'] == md5($cstring)) {
		$locks->clearLock($lid);
	    }
	}
    }
}

if ($f_save && $cstring) {

    $decstr = $po->encstr[$cstring];
    $msgstr = Horde_Util::getFormData('msgstr');
    $comments = trim($po->comments[$decstr]);

    $phpformat = Horde_Util::getFormData('phpformat');
    $fuzzy = Horde_Util::getFormData('fuzzy');

    $status = $po->status[$decstr];
    foreach($status as $k => $v) {
	if ($v == 'untranslated' && !empty($msgstr)) {
	    unset($status[$k]);
	}

	if ($v == 'php-format' && !$phpformat) {
	    unset($status[$k]);
	}

	if ($v == 'fuzzy' && !$fuzzy) {
	    unset($status[$k]);
	}
    }

    if (!in_array('php-format', $status) && $phpformat) {
	$status[] = 'php-format';
    }

    if (!in_array('fuzzy', $status) && $fuzzy) {
	$status[] = 'fuzzy';
    }

    $status = array_unique($status);
    $po->status[$decstr] = $status;

    $status = '';
    if (preg_match('/(#,.*)$/', $comments, $m)) {
	$status = $m[1];
    }

    if (count($po->status[$decstr])) {
	$newstatus = "#, " . implode(', ', $po->status[$decstr]);
    } else {
	$newstatus = "";
    }

    $newcomments = str_replace($status, $newstatus, $comments);

    $po->comments[$decstr] = $newcomments;
    $po->strings[$decstr] = Translate_Display::convert_string($msgstr);
    $po->save($pofile);
}

//

/* Set up the template fields. */
$template->set('menu', Babel::getMenu()->render());

Horde::startBuffer();
$notification->notify(array('listeners' => 'status'));
$template->set('notify', Horde::endBuffer());

/* Create upload form */
$form = new Horde_Form($vars, _("View Translation"), $show);

if (!$app) {
    $form->setButtons(_("View"));
    $form->addVariable(_("Module"), 'module', 'enum', true, false, null, array(Babel::listApps(), true));
    $form->addVariable('', '', 'spacer', true);

    $renderer_params = array();
    $renderer = new Horde_Form_Renderer($renderer_params);
    $renderer->setAttrColumnWidth('20%');

    $form->renderActive($renderer, $vars, Horde::selfURL(), 'post');
} else {

    if (Babel::hasPermission('view', 'tabs', Horde_Perms::EDIT)) {
	$hmenu_desc = _("Edit Header");
	$url = Horde::applicationUrl('edit.php');
	$url = Horde_Util::addParameter($url, array('module' => $app,
					      'url'    => 'view'));

	$hmenu = Horde::link($url, $hmenu_desc, 'menuitem', null);
	$hmenu .= Horde::img('edit.png', null, $hmenu_desc) . '&nbsp;' . $hmenu_desc . '</a>&nbsp;';
    } else {
	$hmenu = '';
    }

    Translate_Display::header(_("Meta Informations"), $hmenu);
    echo '<table border=0 width=100% style="border: solid 1px black" cellpadding=0 cellspacing=0>';
    $i = 0;
    foreach($po->meta as $k => $v) {
	echo '<tr><td class="control" width=30%>';
	echo $k;
	echo '</td><td class="item' . ($i++ % 2) . '">';
	echo htmlentities($v);
	echo '</td><tr>';
    }
    echo '</table>';
    Translate_Display::info();

    Translate_Display::header(_("Statistic"));

    $report = Translate::stats($app, $lang);

    echo '<table width="100%" align="center" border="0" cellspacing="0" cellpadding="0">';
    echo '<tr class="control">';
    echo '<td class="control" style="border-bottom: 1px solid #999;"><b>' . _("Language") . '</b></td>';
    echo '<td width="5%"><b>' . _("Locale") . '</b></td>';
    echo '<td width="15%"><b>' . _("Status") . '</b></td>';
    echo '<td valign="bottom" style="width: 80px;"><b>' . _("Translated") . '</b></td>';
    echo '<td valign="bottom" style="width: 80px;"><b>' . _("Fuzzy") . '</b></td>';
    echo '<td valign="bottom" style="width: 80px;"><b>' . _("Untranslated") . '</b></td>';
    echo '<td valign="bottom" style="width: 80px;"><b>' . _("Obsolete") . '</b></td>';
    echo '</tr>';

    echo "\n<tr class=\"item" . ($i++ % 2) . "\">";
    echo "\n\t<td>" . Horde_Nls::$config['languages'][$lang] . "</td>";
    echo "\n\t<td>" . $lang . "</td>";
    echo "\n\t<td>" . Translate_Display::create_bargraph(@$report[$lang][2], @$report[$lang][0]) . "</td>";
    echo "\n\t<td>" . @$report[$lang][2] . "</td>";
    echo "\n\t<td>" . @$report[$lang] [3] . "</td>";
    echo "\n\t<td>" . @$report[$lang][4] . "</td>";
    echo "\n\t<td>" . @$report[$lang][5] . "</td>";
    echo "\t</tr>";

    echo '</table>';
    Translate_Display::info();

    $filter_html = '';
    $filter_html .=  '<form action="' . Horde::applicationUrl('view.php') . '" method="post" name="edit" id="edit">';
    $filter_html .= '<span class="smallheader">';
    $filter_html .= Horde::img('edit.png') . '&nbsp;';
    $filter_html .= '<b>' . _("Filter: ") . '</b>';
    $filter_html .= '[&nbsp;';
    if (!$filter) {
	$hmenu_desc = '<b>' . _("All") . '</b>';
    } else {
	$hmenu_desc = _("All");
    }
    $url = Horde::applicationUrl('view.php');
    $url = Horde_Util::addParameter($url, array('module' => $app));
    $filter_html .= Horde::link($url, _("Edit Mode"), 'menuitem', null). '&nbsp;' . $hmenu_desc . '</a>&nbsp;';
    $filter_html .= '|&nbsp;';

    if ($filter == 'translated') {
	$hmenu_desc = '<b>' . _("Translated") . '</b>';
    } else {
	$hmenu_desc = _("Translated");
    }
    $url = Horde::applicationUrl('view.php');
    $url = Horde_Util::addParameter($url, array('module' => $app, 'filter' => 'translated'));
    $filter_html .= Horde::link($url, $hmenu_desc, 'menuitem', null). '&nbsp;' . $hmenu_desc . '</a>&nbsp;';
    $filter_html .= '|&nbsp;';


    if ($filter == 'fuzzy') {
	$hmenu_desc = '<b>' . _("Fuzzy") . '</b>';
    } else {
	$hmenu_desc = _("Fuzzy");
    }
    $url = Horde::applicationUrl('view.php');
    $url = Horde_Util::addParameter($url, array('module' => $app, 'filter' => 'fuzzy'));
    $filter_html .= Horde::link($url, $hmenu_desc, 'menuitem', null). '&nbsp;' . $hmenu_desc . '</a>&nbsp;';
    $filter_html .= '|&nbsp;';

    if ($filter == 'untranslated') {
	$hmenu_desc = '<b>' . _("Untranslated") . '</b>';
    } else {
	$hmenu_desc = _("Untranslated");
    }
    $url = Horde::applicationUrl('view.php');
    $url = Horde_Util::addParameter($url, array('module' => $app, 'filter' => 'untranslated'));
    $filter_html .= Horde::link($url, $hmenu_desc, 'menuitem', null). '&nbsp;' . $hmenu_desc . '</a>&nbsp;';
    $filter_html .= ']&nbsp;';

    $filter_html .= '<input type="hidden" name="module" value="' . $app . '">';
    $filter_html .= '<input type="hidden" name="page" value="' . $page . '">';
    $filter_html .= '<input type="hidden" name="filter" value="' . $filter . '">';
    $filter_html .= '<input type="text" name="search" value="' . $search . '">';
    $filter_html .= '<input type="submit" name="filter_btn" value="' . _("Search") . '">';
    $filter_html .= '</span>';
    $filter_html .= '</form>';

    $perpage = 100;

    foreach($po->strings as $msgid => $msgstr) {
	if ($filter && !in_array($filter, $po->status[$msgid])) {
	    unset($po->strings[$msgid]);
	    unset($po->status[$msgid]);
	    unset($po->ref[$msgid]);
	}
	if ($search && !preg_match(';' . $search . ';i', $msgid)) {
	    unset($po->strings[$msgid]);
	    unset($po->status[$msgid]);
	    unset($po->ref[$msgid]);
	}
    }

    $numitem = count($po->strings);
    // Set list min/max values
    $min = $page * $perpage;
    while ($min > $numitem) {
	$page--;
	$min = $page * $perpage;
    }
    $max = $min + $perpage;

    // Start start/end items (according to current page)
    $start = ($page * $perpage) + 1;
    $end = min($numitem, $start + $perpage - 1);

    $cntstr = 0;

    $pageinf = '&nbsp;<span class="smallheader">[' . sprintf(_("%s to %s of %s"), $start, $end, $numitem) . ']</span>';
    Translate_Display::header(_("Translations") . $pageinf, $filter_html);

    foreach($po->strings as $msgid => $msgstr) {

	$cntstr++;

	if ($start && $cntstr < $start) {
	    continue;
	}

	if ($end && $cntstr > $end) {
	    break;
	}

	if ($filter && !in_array($filter, $po->status[$msgid])) {
	    continue;
	}

	$encstr = base64_encode($msgid);

	$bgcolor = '1px #000000';
	if (in_array('fuzzy', $po->status[$msgid])) {
	    $bgcolor = '3px #FFFF00';
	}

	if (in_array('untranslated', $po->status[$msgid])) {
	    $bgcolor = '3px #FF0000';
	}

	$locked = false;
	if ($curlock = $locks->getLocks(md5($encstr), $lockscope)) {
	    foreach($curlock as $lid => $linfo) {
		if ($linfo['lock_scope'] == md5($encstr)) {
		    $bgcolor = '3px #FF00FF';
		    $locked = $linfo['lock_owner'];
		}
	    }
	}

	if ($editmode && $cstring == $encstr) {

	    // Lock the current item for 5 minutes
	    $locks->setLock($GLOBALS['registry']->getAuth(), md5($encstr), $lockscope, 300);

	    echo '<form action="' . Horde::applicationUrl('view.php') . "#" . md5($encstr) . '" method="post" name="edit" id="edit">';
	    echo '<input type="hidden" name="module" value="' . $app . '">';
	    echo '<input type="hidden" name="page" value="' . $page . '">';
	    echo '<input type="hidden" name="filter" value="' . $filter . '">';
	    echo '<input type="hidden" name="search" value="' . $search . '">';
	    echo '<input type="hidden" name="cstring" value="' . $encstr . '">';
	}


	?>
<a name="<?php echo md5($encstr) ?>">
<table border=0 width=100% style="border: solid <?php echo $bgcolor ?>;">
<tr>
 <td valign=top  class="control" style="height: 18px; border-bottom: 1px solid #999;"><b>MSGID</b></td>
 <td valign=top  class="control"  style="height: 18px; border-bottom: 1px solid #999;"><b>REFERENCES</b></td>
 <td valign=top  class="control"  style="height: 18px; border-bottom: 1px solid #999;"><b>STATUS</b></td>
</tr>
<tr><td valign=top  class="item0">
  <?php echo Translate_Display::display_string($msgid) ?><br />&nbsp;
</td>
<td valign=top  rowspan=3 width=30%>
<table border=0 width=100% cellspacing=0 cellpadding=0>

<?php
	  $ref = array();
	foreach($po->ref[$msgid] as $k => $v) {
	    if (preg_match('/(.*):(.*)/', $v, $m)) {
		$sfile = $m[1];
		$sline = $m[2];

		if (Babel::hasPermission('viewsource', 'tabs', Horde_Perms::EDIT)) {
		    $surl = Horde::applicationUrl('viewsource.php');
		    $surl = Horde_Util::addParameter($surl, array('module' => $app,
							    'file'   => $sfile,
							    'line'   => $sline));

		    $onclick = "viewwindow=window.open('". $surl . "', 'viewsource', 'toolbar=no,location=no,status=yes,scrollbars=yes,resizable=yes,width=650,height=350,left=100,top=100'); if(window.focus) { viewwindow.focus()} ; return false;";

		    $surl = Horde::link('#', $sline, null, null, $onclick);
		    $surl .= $sline . '</a>';
		    $surl = str_replace('&amp;', '&', $surl);
		} else {
		    $surl = $sline;
		}

		$ref[$sfile][] = $surl;
	    }
	}

	$i = 0;
	foreach($ref as $k => $v) {
	    echo sprintf("<tr class=item%s><td>%s</td><td align=right>[ %s ]</td></tr>", ($i++ %2), $k, implode(' | ', $v));
	}
	?>
</table>
</td>
<td valign=top  rowspan=3 width=10%>
<?php
	  if ($editmode && $cstring == $encstr) {
	      if (in_array('php-format', $po->status[$msgid])) {
		  echo '<input type="checkbox" checked name="phpformat">' . ' php-format<br>';
	      } else {
		  echo '<input type="checkbox" name="phpformat">' . ' php-format<br>';
	      }
	      if (in_array('fuzzy', $po->status[$msgid])) {
		  echo '<input type="checkbox" checked name="fuzzy">' . ' fuzzy<br>';
	      } else {
		  echo '<input type="checkbox" name="fuzzy">' . ' fuzzy<br>';
	      }
	  } else {
	      echo implode('<br />', $po->status[$msgid]);
	  }
	?>

</td>
</tr>
<tr>
  <td valign=top class="control"  style="height: 18px; border-bottom: 1px solid #999;">
  <table border="0" width="100%" cellspacing="0" cellpadding="0">
  <tr>
    <td><b>MSGSTR</b></td>
    <td align="right">
<?php
	  if ($locked) {
	      echo Horde::img('locked.png') . '&nbsp;' . sprintf(_("Locked by %s"), $locked);
	  } else {
	      if (Babel::hasPermission('view', 'tabs', Horde_Perms::EDIT)) {
		  if (!$editmode || $cstring != $encstr) {
		      $surl = Horde::applicationUrl('view.php');
		      $surl = Horde_Util::addParameter($surl, array('module' => $app, 'cstring' => $encstr, 'editmode' => 1, 'page' => $page, 'filter' => $filter, 'search' => $search));
		      $surl .= "#" . md5($encstr);

		      echo Horde::link($surl, _("Edit Translation")) . Horde::img('babel.png') . '&nbsp;' ._("Edit Translation") . "</a>";
		  } elseif ($editmode && $cstring == $encstr) {
		      echo '<input type="submit" class="button" name="submit" value="' . _("Save") . '">';
		      echo '&nbsp;';
		      echo '<input type="submit" class="button" name="cancel" value="' . _("Cancel") . '">';
		  }
	      }
	  }
?></td>
  </tr>
  </table>
</tr>
<tr><td valign=top  class="item0">
<?php
	  if ($editmode && $cstring == $encstr) {
	      echo '<textarea style="width:100%; height:100%" name="msgstr">' . Translate_Display::display_string($msgstr) . '</textarea><br />';
	  } else {
	      if (in_array('fuzzy', $po->status[$msgid]) && preg_match('/#-#-#-#-#/', $msgstr)) {
		  preg_match_all('/(#-#-#-#-#\s+(.*?)\s+#-#-#-#-#(\n)*(.*))+/', Translate_Display::display_string($msgstr), $matches,  PREG_SET_ORDER);
		  foreach($matches as $m) {
		      echo sprintf("%s - %s<br />", $m[4], $m[2]);
		  }
	      } else {
		  echo Translate_Display::display_string($msgstr) . "<br />";
	      }
	  }
?>&nbsp;
</td>
</tr>
</table>
<p />
<?php
	  flush();

	if ($editmode && $cstring == $encstr) {
	    echo '</form>';
	}

	//	print "STAT " . implode(', ', $po->status[$msgid]) . "<br>";
    }
}

?>
<!-- START PAGER -->
<?php if (isset($numitem) && $numitem > $perpage): ?>
<table width="100%" class="item box">
<tr><td>
<?php
  $viewurl = Horde::applicationUrl('view.php');
$viewurl = Horde_Util::addParameter($viewurl, array('editmode' => $editmode,
					      'module' => $app,
					      'filter' => $filter,
					      'search' => $search));
 $pager = new Horde_Core_Ui_Pager('page', $vars, array('num' => $numitem, 'url' => $viewurl, 'page_count' => 10, 'perpage' => $perpage));
 echo $pager->render($page, $numitem, $viewurl);
?>
</td></tr></table>
<?php endif; ?>
<!-- END PAGER -->
<?php
if ($app) {
    Babel::RB_close();
}

require $registry->get('templates', 'horde') . '/common-footer.inc';
