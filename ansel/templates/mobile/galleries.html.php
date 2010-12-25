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
  <div data-role="content" class="ui-body">
      <ul data-role="listview">
          <li>
           <?php echo Horde::img('thumb-error.png')?>
              <h3><a href="#">Gallery One</a></h3>
              <p>Gallery Description</p>
          </li>
          <li>
           <?php echo Horde::img('thumb-error.png')?>
              <h3><a href="#">Gallery Two</a></h3>
              <p>Gallery Description</p>
          </li>
          <li>
           <?php echo Horde::img('thumb-error.png')?>
              <h3><a href="#">Gallery Three</a></h3>
              <p>Gallery Description</p>
          </li>
      </ul>
  </div>
</div>