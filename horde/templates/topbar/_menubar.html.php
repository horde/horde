<div id="horde-head">
  <div id="horde-logo"><a class="icon" href="<?php echo $this->portalUrl ?>"></a></div>
  <div id="horde-version"><?php echo $this->h($this->version) ?></div>
  <div id="horde-navigation">
<?php echo $this->menu->getTree() ?>
    <div class="clear"></div>
  </div>
<?php if ($this->logoutUrl): ?>
  <div id="horde-logout"><a class="icon" href="<?php echo $this->logoutUrl ?>"></a></div>
<?php endif ?>
<?php if ($this->search): ?>
  <div id="horde-search">
    <form action="<?php echo $this->searchAction ?>" method="post">
<?php if ($this->searchMenu): ?>
      <div class="horde-fake-input">
        <span id="horde-search-dropdown">
          <span class="iconImg popdownImg"></span>
        </span>
        <input autocomplete="off" id="horde-search-input" type="text" />
      </div>
<?php else: ?>
      <input type="text" id="horde-search-input" name="searchfield" class="formGhost" title="<?php echo _("Search") ?>" />
<?php endif ?>
      <input type="image" id="horde-search-icon" src="<?php echo $this->searchIcon ?>" />
    </form>
  </div>
<?php endif ?>
  <div class="clear"></div>
</div>
