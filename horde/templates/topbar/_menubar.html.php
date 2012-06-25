<div id="horde-head">
  <div id="horde-logo"><a class="icon" href="<?php echo $this->portalUrl ?>"></a></div>
  <div id="horde-version"><p class="p11 white italic bold shadow"><?php echo $this->h($this->version) ?></p></div>
  <div id="horde-navigation">
<?php echo $this->menu->getTree() ?>
    <div class="clear"></div>
  </div>
  <div id="horde-logout"><a class="icon" href="<?php echo $this->logoutUrl ?>"></a></div>
<?php if ($this->search): ?>
  <div id="horde-search">
    <div id="horde-input">
      <form action="<?php echo $this->searchAction ?>" method="post">
<?php if ($this->searchMenu): ?>
        <div class="horde-fake-input">
          <span id="horde-search-dropdown">
            <span class="iconImg popdownImg"></span>
          </span>
          <input autocomplete="off" id="horde-search-input" type="text" />
        </div>
<?php else: ?>
        <input type="text" id="horde-search-input" name="searchfield" class="formGhost" value="<?php echo _("Search") ?>" />
<?php endif ?>
      </form>
    </div>
    <div id="horde-search-icon"><a class="icon" href=""></a></div>
    <div class="clear"></div>
  </div>
<?php endif ?>
  <div class="clear"></div>
</div>
