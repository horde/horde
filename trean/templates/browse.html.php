<div id="bookmarkList">
  <?php if ($view): ?>
  <?php echo $this->view->render() ?>
  <?php else: ?>
  <p><em><?php printf(_("No bookmarks. Drag the %s link to your browser's toolbar to add them easily."), Trean::bookmarkletLink()) ?></em></p>
  <?php endif ?>
</div>
