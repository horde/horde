<div id="galleryHeader" class="header">
  <?php if ($this->numTiles): ?>
    <span class="rightFloat">
      <?php echo ($this->numTiles > $this->perpage
        ? sprintf(_("%d to %d of %d Items"), $this->pagestart, $this->pageend, $this->numTiles)
        : sprintf(ngettext("%d Item", "%d Items", $this->numTiles), $this->numTiles)) ?>
    </span>
  <?php endif; ?>
  <?php echo Ansel::getBreadCrumbs($this->gallery) ?>
</div>
<div class="gallery-desc" id="galleryDescription"><?php echo $this->gallery_desc ?></div>

<!-- Start Gallery Grid -->
<table width="100%" cellspacing="0">
 <tr>
  <td style="vertical-align:top;width:<?php echo ($this->view->countWidgets() ? "75%" : "100%") ?>;">
    <?php if (empty($this->view->api) && $this->option_select && $this->numTiles): ?>
      <form name="gallery" action="<?php echo Horde::selfUrl(true, true, true) ?>" method="get">
      <?php echo Horde_Util::formInput() ?>
      <input type="hidden" name="actionID" value="" />
      <input type="hidden" name="gallery" value="<?php echo $this->gallery->id ?>" />
      <input type="hidden" name="page" value="<?php echo $this->page ?>" />
      <input type="hidden" name="year" value="<?php echo (empty($this->view->year) ? 0 : $this->view->year) ?>" />
      <input type="hidden" name="month" value="<?php echo (empty($this->view->month) ? 0 : $this->view->month) ?>" />
      <input type="hidden" name="day" value="<?php echo (empty($this->view->day) ? 0 : $this->view->day) ?>" />
    <?php endif; ?>
    <?php if (!empty($this->option_select) && $this->numTiles): ?>
      <table class="anselActions" cellspacing="0" width="100%">
        <tr>
          <td>
            <span class="widget"><?php echo _("Select") ?>:</span>
            <?php echo $this->contentTag('a', _("All"), array('title' => _("Select All"), 'id' => 'anselgallery_select_all')) ?>
            <?php echo $this->contentTag('a', _("None"), array('title' => _("Select None"), 'id' => 'anselgallery_select_none')) ?>
          </td>
          <td class="rightAlign">
            <?php if ($this->option_delete || $this->option_move || $this->option_copy) echo _("Actions: ") ?>
            <?php if ($GLOBALS['conf']['gallery']['downloadzip']): ?>
              <?php echo $this->contentTag('a', _("Download selected photos"), array('class' => 'widget', 'id' => 'anselgallery_download')) ?> |
            <?php endif; ?>
            <?php if ($this->option_edit): ?>
              <?php echo $this->contentTag('a', _("Edit Dates"), array('title' => _("Edit Dates"), 'class' => 'widget', 'id' => 'anselgallery_editdates')) ?> |
            <?php endif; ?>
            <?php if ($this->option_delete): ?>
              <?php echo $this->contentTag('a', _("Delete"), array('title' => _("Delete"), 'class' => 'widget', 'id' => 'anselgallery_delete')) ?>
            <?php endif; ?>
            <?php if ($this->option_move): ?>
              | <?php echo $this->contentTag('a', _("Move"), array('title' => _("Move"), 'class' => 'widget', 'id' => 'anselgallery_move')) ?>
            <?php endif; ?>
            <?php if ($this->option_copy): ?>
              | <?php echo $this->contentTag('a', _("Copy"), array('title' => _("Copy"), 'class' => 'widget', 'id' => 'anselgallery_copy')) ?>
            <?php endif; ?>
            <select name="new_gallery">
              <option value="-1"><?php echo _("Selected photos to") ?></option>
              <?php echo Ansel::selectGalleries(array('perm' => Horde_Perms::EDIT)) ?>
            </select>
          </td>
        </tr>
      </table>
    <?php endif; ?>
    <?php if (!$this->numTiles): ?>
      <div class="text"><em><?php echo _("There are no photos in this gallery.") ?></em></div>
    <?php else: ?>
      <table width="100%" style="background-color:<?php echo $this->style->background ?>;">
        <tbody><tr>
          <td colspan="<?php echo $this->tilesperrow ?>"><?php echo $this->pager->render() ?></td>
        </tr>
        <tr>
       <?php
       $count = 0;
       foreach ($this->children as $child) {
           echo '<td width="' . $this->cellwidth . '%" class="ansel-tile">'
               . $child->getTile($this->gallery, $this->style, false, $this->view->getParams()) . '</td>';
           if (!(++$count % $this->tilesperrow)) {
                echo '</tr><tr>';
           }
       }
       while ($count % $this->tilesperrow) {
          echo '<td width="' . $this->cellwidth . '%" valign="top">&nbsp;</td>';
          $count++;
       }?>
       </tr>
       <tr><td colspan="<?php echo $this->tilesperrow ?>"><?php echo $this->pager->render() ?></td></tr>
      </tbody></table>
     <?php endif; ?>
     <?php if (!empty($this->option_select) && $this->numTiles): ?>
       </form>
     <?php endif; ?>
   </td>
   <td class="anselWidgets">
     <?php $this->view->renderWidgets() ?>
   </td>
 </tr>
</table>
