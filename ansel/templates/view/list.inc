<div id="listHeader" class="header">
 <span id="listCounts" class="rightFloat">
   <?php if ($this->_g_perPage < $this->_numGalleries) printf(_("%d to %d of %d Galleries"), $this->_start, $end, $this->_numGalleries); else printf(ngettext("%d Gallery", "%d Galleries", $this->_numGalleries), $this->_numGalleries) ?>
 </span>
 <?php if (empty($this->_params['api'])) echo Ansel::getBreadcrumbs() . ' ' . Horde::link(Horde::applicationUrl($refresh_link), _("Refresh List")) . Horde::img('reload.png', _("Refresh List")) . '</a>' ?>
</div>
<?php if (empty($this->_params['api'])): ?>
<div class="anselActions widget">
  <?php if ($this->_groupby == 'none'): ?>
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
  if ($this->_sortDir) {
      echo Horde::link(Horde::applicationUrl(Horde_Util::addParameter($refresh_link, array('sort' => $this->_sortBy, 'sort_dir' => 0))))
          . Horde::img('za.png', _("Ascending"))
          . '</a>';
  } else {
      echo Horde::link(Horde::applicationUrl(Horde_Util::addParameter($refresh_link, array('sort' => $this->_sortBy, 'sort_dir' => 1))))
          . Horde::img('az.png', _("Descending"))
          . '</a>';
   }
 ?>
</div>
<?php endif; ?>
<?php if ($this->countWidgets()): ?>
<div style="float:right;width:25%;"><?php echo $this->renderWidgets() ?></div>
<div style="float:right:width:73%;">
<?php endif; ?>
<?php echo $this->_pager->render() ?>
<table width="<?php echo ($this->countWidgets() ? 73 : 100) ?>%" style="background-color:<?php echo $style['background'] ?>;">
 <tr>
 <?php
 $tilesperrow = $prefs->getValue('tilesperrow');
 $cellwidth = round(100 / $tilesperrow);
 $count = 0;
 foreach ($this->_galleryList as $galleryId => $gallery) {
     echo '<td width="' . $width . '%" class="ansel-tile">' . $gallery->getTile(null, $style['name'], false, $this->_params) . '</td>';
     if (!(++$count % $prefs->getValue('tilesperrow'))) {
         if ($count < $this->_numGalleries) {
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
     <?php echo $this->_pager->render() ?>
   </td>
 </tr>
</table>
<?php if ($this->countWidgets()): ?>
</div>
<?php endif; ?>
