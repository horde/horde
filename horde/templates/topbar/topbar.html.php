<div id="horde-head">
  <div id="horde-logo"><a class="icon" href=""></a></div>
  <div id="horde-version"><p class="p11 white italic bold shadow"><?php echo $this->h($this->version) ?></p></div>
  <div id="horde-navigation">
<?php echo $this->menu ?>
    <ul class="horde-dropdown">
      <li><div class="horde-settings horde-icon-settings" onmouseover="this.className = 'horde-settings horde-icon-settings-active'" onmouseout="this.className = 'horde-settings horde-icon-settings'"><a class="icon" href=""></a></div>
        <ul>
          <li><div class="horde-drowdown-str"><a class="horde-mainnavi" href="">Neuer Ordner</a></div></li>
          <li><div class="horde-drowdown-str"><a class="horde-mainnavi" href="">Alle Ordner anzeigen</a></div></li>
          <li><div class="horde-drowdown-str"><a class="horde-mainnavi" href="">Alle öffnen</a></div></li>
          <li class="arrow">
            <div class="horde-drowdown-str"><a class="horde-mainnavi" href="">Alle schließen</a></div>
            <ul>
              <li><div class="horde-drowdown-str"><a class="horde-mainnavi" href="">Neuer Ordner</a></div></li>
              <li><div class="horde-drowdown-str"><a class="horde-mainnavi" href="">Alle Ordner anzeigen</a></div></li>
              <li><div class="horde-drowdown-str"><a class="horde-mainnavi" href="">Alle öffnen</a></div></li>
              <li><div class="horde-drowdown-str"><a class="horde-mainnavi" href="">Alle schließen</a></div></li>
              <li class="last"><div class="horde-drowdown-str"><a class="horde-mainnavi" href="">Liste neu laden</a></div></li>
            </ul>
          </li>
          <li class="last"><div class="horde-drowdown-str"><a class="horde-mainnavi" href="">Liste neu laden</a></div></li>
        </ul>
      </li>
    </ul>
    <div class="clear"></div>
  </div>
  <div id="horde-logout"><a class="icon" href=""></a></div>
  <div id="horde-search">
    <div id="horde-input"><input type="text" name="searchfield" value="Search" /></div>
    <div id="horde-img"><a class="icon" href=""></a></div>
    <div class="clear"></div>
  </div>
  <div class="clear"></div>
</div>
