<div class="horde-subnavi<?php if (!empty($rowtree['selected'])) echo ' horde-subnavi-active' ?>"<?php if (!empty($rowtree['style'])) echo ' style="' . $rowtree['style'] . '"' ?>>
 <div class="horde-subnavi-icon <?php echo $rowtree['cssClass'] ?>">
  <a class="icon"></a>
 </div><div<?php if (!empty($rowtree['id'])) echo ' id="' . $rowtree['id'] . '"' ?> class="horde-subnavi-point"><?php echo $rowtree['link'] ?>
 </div>
</div>
