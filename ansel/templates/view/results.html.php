
<form name="gallery" action="<?php echo Horde::url('view.php', true)?>" method="get">
<?php echo Horde_Util::formInput() ?>
<input type="hidden" name="actionID" value="" />
<input type="hidden" name="view" value="Results" />

<div class="header">
  <?php if ($this->total): ?>
    <span class="rightFloat">
      <?php echo $this->total > $this->perPage ? sprintf(_("%d to %d of %d Items"), $this->pageStart, $this->pageEnd, $this->total) : sprintf(ngettext("%d Item", "%d Items", $this->total), $this->total) ?>
      <?php if (!empty($this->owner)): ?>
        <small><?php echo $this->contentTag('a', _("View All Results"), array('href' => Horde::selfUrl()->add(array('view' => 'Results')), 'title' => _("View Results from All Users"))) ?></small>
      <?php endif; ?>
    </span>
  <?php endif; ?>
  <?php echo $this->h($this->title) . $this->tagTrail ?>
</div>
<table width="100%" cellspacing="0">
  <tr>
    <td style="vertical-align:top;width:75%;">
      <table class="anselActions" width="100%" cellspacing="0">
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

      <?php if (!$this->total): ?>
        <div class="text"><em><?php echo _("There are no photos in this gallery.") ?></em></div>
      <?php else: ?>
        <table width="100%" style="background-color:<?php echo $this->style->background ?>;">
         <tbody>
          <tr><td colspan="<?php echo $this->tilesperrow ?>" valign="top"><?php echo $this->pager ?></td></tr>
          <tr>
          <?php
            $count = 0; foreach ($this->results as $result): ?>
              <td class="ansel-tile" width="<?php echo $this->cellwidth ?>%" valign="top"> <?php echo $result->getTile(null, $this->style, false, $this->params) ?></td>
              <?php if (!(++$count % $this->tilesperrow) && $count < $this->total): ?>
                </tr><tr>
              <?php endif ?>
            <?php endforeach ?>
            <?php while ($count++ % $this->tilesperrow): ?>
              <td>&nbsp;</td>
            <?php endwhile ?>
          </tr>
        </table>
        <?php echo $this->pager->render() ?>
      <?php endif; ?>
    </td>
    <td width="20%" valign="top">
      <div id="anselWidgets">
        <?php if ($GLOBALS['conf']['tags']['relatedtags']): ?>
          <div style="background-color:<?php echo $this->style->background ?>;">
            <h2 class="header tagTitle"><?php echo _("Related Tags") ?></h2>
            <div id="tags"><ul class="horde-tags">
              <?php foreach ($this->rtags as $id => $taginfo): ?>
                <?php if (!empty($this->owner)): ?>
                <?php   $this->taglinks[$id]->add('owner', $this->owner) ?>
                <?php endif ?>
                <li><?php echo $this->contentTag('a', $this->h($taginfo['tag_name']), array('href' => strval($this->taglinks[$id]), 'title' => sprintf(ngettext("%d photo", "%d photos",$taginfo['total']),$taginfo['total']))) ?></li>
              <?php endforeach ?>
            </ul></div>
          </div>
        <?php endif; ?>
      </div>
    </td>
  </tr>
 </tbody>
</table>
</form>
