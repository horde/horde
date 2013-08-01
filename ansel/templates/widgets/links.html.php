<?php echo $this->render('begin'); ?>
<div class="ansel-widgetlink">
  <?php echo $this->contentTag(
    'a',
    Horde::img('feed.png') . ' ' . ($this->owner ? sprintf(_("Recent photos by %s"), $this->owner) : _("Recent system photos")),
    array('href' => $this->userfeedurl ));
  ?>
</div>
<div class="ansel-widgetlink">
  <?php echo $this->contentTag(
      'a',
      Horde::img('feed.png') . ' ' . sprintf(_("Recent photos in %s"), $this->h($this->galleryname)),
      array('href' => $this->galleryfeedurl));
  ?>
</div>
<div class="ansel-widgetlink ansel-embedlink">
  <?php echo Horde::img('embed.png') . ' ' . _("Embed on external blog") ?><br>
  <textarea readonly="readonly"><?php echo $this->embed ?></textarea>
</div>
<?php echo $this->render('end'); ?>

