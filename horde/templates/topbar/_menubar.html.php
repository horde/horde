<div id="horde-head">
  <div id="horde-logo"><a class="icon" href="<?php echo $this->portalUrl ?>"></a></div>
  <div id="horde-version"><p class="p11 white italic bold shadow"><?php echo $this->h($this->version) ?></p></div>
  <div id="horde-navigation">
<?php echo $this->menu->getTree() ?>
    <div class="clear"></div>
  </div>
  <div id="horde-logout"><a class="icon" href="<?php echo $this->logoutUrl ?>"></a></div>
  <div id="horde-search">
    <div id="horde-input"><input type="text" name="searchfield" value="Search" /></div>
    <div id="horde-img"><a class="icon" href=""></a></div>
    <div class="clear"></div>
  </div>
  <div class="clear"></div>
</div>
