<table style="width:100%" cellspacing="0">
<tr class="header">
<td style="width:200px; padding-left:8px; vertical-align:top">
 <h1 class="header">
  <gettext>Command</gettext>
 </h1>
</td>
<td style="padding-left:8px; vertical-align:top">
 <h1 class="header">
  <gettext>Description</gettext>
 </h1>
</td>

<?php 

$cmds = array();

$cmds['view'] = array(
  'desc' => _("View"),
  'text' => _("View all translations."),
  'url'  => Horde::url('view.php')
);

$cmds['download'] = array(
  'desc' => _("Download"),
  'text' => _("Download all current PO files."),
  'url'  => Horde::url('download.php')
);

$cmds['upload'] = array(
  'desc' => _("Upload"),
  'text' => _("Upload new PO files."),
  'url'  => Horde::url('upload.php')
);

$cmds['stats'] = array(
  'desc' => _("Statistics"),
  'text' => _("Get statistics about translations."),
  'url'  => Horde::url('stats.php')
);

$cmds['extract'] = array(
  'desc' => _("Extract"),
  'text' => _("Generate and merge PO files."),
  'url'  => Horde::url('extract.php')
);

$cmds['make'] = array(
  'desc' => _("Make"),
  'text' => _("Build binary MO files from the specified PO files."),
  'url'  => Horde::url('make.php')
);

$cmds['commit'] = array(
  'desc' => _("Commit"),
  'text' => _("Commit translations to the SVN server."),
  'url'  => Horde::url('commit.php')
);

$cmds['reset'] = array(
  'desc' => _("Reset"),
  'text' => _("The reset procedure will delete all PO files from the server for all modules and restore from SVN server."),
  'url'  => Horde::url('reset.php')
);


$i = 0;

foreach($cmds as $cmdid => $cmd) {
    if (Babel::hasPermission($cmdid)) {
	echo '<tr height="50" class="item' . ($i++ % 2) . '">';
	echo '<td align="center"><div style="width: 140px;" onclick="window.location=\'' . $cmd['url'] . '\';" class="button"><a class="smallheader" href="' . $cmd['url'] . '">' . $cmd['desc'] . '</a></div></td>';
	echo '<td>' . $cmd['text'] . '</td>';
	echo '</tr>';
    }
}
?>
</table>
