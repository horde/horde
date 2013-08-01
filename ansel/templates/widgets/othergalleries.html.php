<!-- OtherGalleries Widget -->
<?php echo $this->render('begin'); ?>
  <div style="display:<?php echo $GLOBALS['prefs']->getValue('show_othergalleries') ? 'block' : 'none' ?>" id="othergalleries">
    <?php echo $this->tree ?>
  </div>
  <!-- Toggle Link -->
  <div class="control"><?php echo $this->toggle_url ?>&nbsp;</a></div>
<?php echo $this->render('end'); ?>
<!--End OtherGalleries Widget -->