<div id="listHeader" class="header">
 <span id="listCounts" class="rightFloat">
   <?php if ($this->_g_perPage < $this->_numGalleries) printf(_("%d to %d of %d Galleries"), $this->_start, $end, $this->_numGalleries); else printf(ngettext("%d Gallery", "%d Galleries", $this->_numGalleries), $this->_numGalleries) ?>
 </span>
 <?php if (empty($this->_params['api'])) echo Ansel::getBreadcrumbs() . ' ' . Horde::url($refresh_link)->link(array('title' =>  _("Refresh List"))) . Horde::img('reload.png', _("Refresh List")) . '</a>' ?>
</div>
<?php if (empty($this->_params['api'])): ?>
<div class="anselActions widget">
  <?php if ($this->_groupby == 'none'): ?>
  <div class="rightFloat">
    <?php echo _("Group by:") . ' '
       . Ansel::getUrlFor('group', array('actionID' => 'groupby', 'groupby' => 'owner'))->link() . _("owner") . '</a> | ' ?>
  </div>
  <?php endif; ?>
  <?php echo _("Sort by:") . ' '
      . $refresh_link->copy()->add('sort', 'name')->link()
      . _("Name") . '</a> | '
      . $refresh_link->copy()->add('sort', 'last_modified')->link()
      . _("Date") . '</a> | '
      . $refresh_link->copy()->add('sort', 'owner')->link()
      . _("Owner") . '</a>';
  if ($this->_sortDir) {
      echo $refresh_link->copy()->add(array('sort' => $this->_sortBy, 'sort_dir' => 0))->link()
          . Horde::img('za.png', _("Ascending"))
          . '</a>';
  } else {
      echo $refresh_link->copy()->add(array('sort' => $this->_sortBy, 'sort_dir' => 1))->link()
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
<table width="<?php echo ($this->countWidgets() ? 73 : 100) ?>%" style="background-color:<?php echo $style->background ?>;">
 <tr>
 <?php
 $cellwidth = round(100 / $tilesperrow);
 $count = 0;
 foreach ($this->_galleryList as $galleryId => $gallery) {
     echo '<td width="' . $width . '%" class="ansel-tile">' . $gallery->getTile(null, $style, false, $this->_params) . '</td>';
     if (!(++$count % $tilesperrow)) {
         if ($count < $this->_numGalleries) {
             echo '</tr><tr>';
         }
     }
 }
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
