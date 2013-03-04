<!-- Header -->
<div id="listHeader" class="header">
  <span id="listCounts" class="rightFloat">
    <?php if ($this->gPerPage < $this->numGalleries): ?>
       <?php echo sprintf(_("%d to %d of %d Galleries"), $this->start, $this->end, $this->numGalleries) ?>
     <?php else: ?>
      <?php sprintf(ngettext("%d Gallery", "%d Galleries", $this->_numGalleries), $this->_numGalleries) ?>
    <?php endif; ?>
  </span>

  <?php if (!empty($this->breadcrumbs)): ?>
    <?php echo $this->breadcrumbs ?><?php echo $this->contentTag('a', Horde::img('reload.png', _("Refresh List")), array('href' => $this->refresh_link, 'title' =>  _("Refresh List"))) ?>
  <?php endif; ?>
</div>

<!-- Actions -->
<?php if (empty($this->params['api'])): ?>
<div class="anselActions">
  <?php if ($this->groupby == 'none'): ?>
    <div class="rightFloat">
      <?php echo _("Group By:") ?> <?php echo $this->contentTag('a', _("Owner"), array('href' => $this->groupbyUrl)); ?> |
    </div>
  <?php endif; ?>
  <?php echo _("Sort by:") ?> <?php echo $this->contentTag('a', _("Name"), array('href' => $this->refresh_link->copy()->add('sort', 'name'))) ?> |
  <?php echo $this->contentTag('a', _("Date"), array('href' => $this->refresh_link->copy()->add('sort', 'last_modified'))) ?> |
  <?php echo $this->contentTag('a', _("Owner"), array('href' => $this->refresh_link->copy()->add('sort', 'owner'))) ?>
  <?php if ($this->sortDir): ?>
    <?php echo $this->contentTag('a', Horde::img('za.png', _("Ascending")), array('href' => $this->refresh_link->copy()->add(array('sort' => $this->sortBy, 'sort_dir' => 0)))) ?>
  <?php else: ?>
    <?php echo $this->contentTag('a', Horde::img('az.png', _("Descending")), array('href' => $this->refresh_link->copy()->add(array('sort' => $this->_sortBy, 'sort_dir' => 1)))) ?>
  <?php endif; ?>
</div>
<?php endif; ?>

<!-- Pager -->
<?php echo $this->pager ?>

<!-- Main Gallery Grid -->
<table width="100%" style="background-color:<?php echo $this->style->background ?>;"><tr>
<?php
$count = 0;
foreach ($this->galleryList as $galleryId => $gallery): ?>
  <td width="<?php echo $this->cellwidth ?>%" class="ansel-tile"><?php echo $gallery->getTile(null, $this->style, false, $this->params) ?></td>
    <?php if (!(++$count % $this->tilesperrow)): ?>
      <?php if ($count < $this->numGalleries): ?>
        </tr><tr>
      <?php endif; ?>
    <?php endif; ?>
<?php endforeach; ?>

<?php while ($count++ % $this->tilesperrow): ?>
  <td width="<?php echo $this->cellwidth ?>%">&nbsp;</td>
<?php endwhile; ?>
 </tr>
 <tr>
   <td align="center" colspan="<?php echo $this->tilesperrow?>">
     <?php echo $this->pager ?>
   </td>
 </tr>
</table>
