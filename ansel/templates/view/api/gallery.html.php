<div class="header" id="galleryHeader">
  <?php if ($this->numTiles): ?>
    <span class="rightFloat">
      <?php echo ($this->numTiles > $this->perpage
        ? sprintf(_("%d to %d of %d Items"), $this->pagestart, $this->pageend, $this->numTiles)
        : sprintf(ngettext("%d Item", "%d Items", $this->numTiles), $this->numTiles)) ?>
    </span> <?php echo Ansel::getBreadCrumbs($this->gallery) ?>
  <?php endif; ?>
</div>
<div class="gallery-desc" id="galleryDescription"><?php echo $this->gallery_desc ?></div>

<!-- Start Gallery Grid -->
<table width="100%" cellspacing="0">
 <tr>
  <td style="vertical-align:top;width:100%">
    <?php if (!$this->numTiles): ?>
      <div class="text"><em><?php echo _("There are no photos in this gallery.") ?></em></div>
    <?php else: ?>
      <table width="100%" style="background-color:<?php echo $this->view->style->background ?>;">
        <tr>
          <td colspan="<?php echo $this->tilesperrow ?>"><?php echo $this->pager->render() ?></td>
        </tr>
        <tr>
       <?php
       $count = 0;
       foreach ($this->children as $child) {
           echo '<td width="' . $this->cellwidth . '%" class="ansel-tile">'
               . $child->getTile($this->gallery, $this->view->style, false, $this->view->getParams()) . '</td>';
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
      </table>
     <?php endif; ?>
   </td>
 </tr>
</table>