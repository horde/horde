<div data-role="page" id="gallerylist">
  <div data-role="header">
    <h1><?php echo _("My Galleries")?></h1>
    <a rel="external" href="<?php echo $this->portal ?>"><?php echo _("Portal")?></a>
    <?php if ($this->logout): ?>
      <a href="<?php echo $this->logout ?>" rel="external" data-theme="e" data-icon="delete"><?php echo _("Log out") ?></a>
    <?php endif ?>
    <div data-role="navbar" class="ui-bar-a">
      <ul>
        <li><a href="#">User Galleries</a></li>
        <li><a href="#">My Galleries</a></li>
      </ul>
    </div>
  </div>
  <div id="anselgallerylist" data-role="content" class="ui-body">
  </div>
</div>