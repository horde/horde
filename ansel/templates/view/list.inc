<div id="listHeader" class="header">
 <span id="listCounts" class="rightFloat">
   <?php if ($galleries_perpage < $num_galleries) printf(_("%d to %d of %d Galleries"), $start, $end, $num_galleries); else printf(ngettext("%d Gallery", "%d Galleries", $num_galleries), $num_galleries) ?>
 </span>
 <?php if (empty($this->_params['api'])) echo Ansel::getBreadcrumbs() . ' ' . Horde::link(Horde::applicationUrl($refresh_link), _("Refresh List")) . Horde::img('reload.png', _("Refresh List"), null, $registry->getImageDir('horde')) . '</a>' ?>
</div>
<?php if (empty($this->_params['api'])): ?>
<div class="anselActions widget">
  <?php if ($groupby == 'none'): ?>
  <div class="rightFloat">
    <?php echo _("Group by:") . ' '
       . Horde::link(Ansel::getUrlFor('group', array('actionID' => 'groupby', 'groupby' => 'owner')))
       . _("owner") . '</a> | '
       . Horde::link(Ansel::getUrlFor('group', array('actionID' => 'groupby', 'groupby' => 'category'))) . _("category") . '</a>'; ?>
  </div>
  <?php endif; ?>
  <?php echo _("Sort by:") . ' '
      . Horde::link(Horde::applicationUrl(Horde_Util::addParameter($refresh_link, 'sort', 'name')))
      . _("Name") . '</a> | '
      . Horde::link(Horde::applicationUrl(Horde_Util::addParameter($refresh_link, 'sort', 'last_modified')))
      . _("Date") . '</a> | '
      . Horde::link(Horde::applicationUrl(Horde_Util::addParameter($refresh_link, 'sort', 'owner')))
      . _("Owner") . '</a>';
  if ($sortdir) {
      echo Horde::link(Horde::applicationUrl(Horde_Util::addParameter($refresh_link, array('sort' => $sortby, 'sort_dir' => 0))))
          . Horde::img('za.png', _("Ascending"), '', $registry->getImageDir('horde'))
          . '</a>';
  } else {
      echo Horde::link(Horde::applicationUrl(Horde_Util::addParameter($refresh_link, array('sort' => $sortby, 'sort_dir' => 1))))
          . Horde::img('az.png', _("Descending"), '', $registry->getImageDir('horde'))
          . '</a>';
   }
 ?>
</div>
<?php endif; ?>
<?php if ($this->countWidgets()): ?>
<div style="float:right;width:25%;"><?php echo $this->renderWidgets() ?></div>
<div style="float:right:width:73%;">
<?php endif; ?>
<?php echo $pager->render() ?>
<table width="<?php echo ($this->countWidgets() ? 73 : 100) ?>%" style="background-color:<?php echo $style['background'] ?>;">
 <tr>
 <?php
 $tilesperrow = $prefs->getValue('tilesperrow');
 $cellwidth = round(100 / $tilesperrow);
 $count = 0;
 foreach ($gallerylist as $galleryId => $gallery) {
     echo '<td width="' . $width . '%" class="ansel-tile">' . $gallery->getTile(null, $style['name'], false, $this->_params) . '</td>';
     if (!(++$count % $prefs->getValue('tilesperrow'))) {
         if ($count < $num_galleries) {
             echo '</tr><tr>';
         }
     }
 }
 $tilesperrow = $prefs->getValue('tilesperrow');
 while ($count++ % $tilesperrow) {
     echo '<td width="' . $width . '%">&nbsp;</td>';
 } ?>
 </tr>
 <tr>
   <td align="center" colspan="<?php echo $tilesperrow?>">
     <?php echo $pager->render() ?>
   </td>
 </tr>
</table>
<?php if ($this->countWidgets()): ?>
</div>
<?php endif; ?>
