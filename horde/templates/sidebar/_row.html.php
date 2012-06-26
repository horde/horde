  <div class="horde-subnavi<?php if (!empty($row['selected'])) echo ' horde-subnavi-active' ?>"<?php if (!empty($row['style'])) echo ' style="' . $row['style'] . '"' ?>>
    <div class="horde-subnavi-icon-1 <?php echo $row['cssClass'] ?>"><a class="icon"></a></div>
    <div<?php if (!empty($row['id'])) echo ' id="' . $row['id'] . '"' ?> class="horde-subnavi-point"><?php echo $row['link'] ?></div>
    <div class="clear"></div>
  </div>
