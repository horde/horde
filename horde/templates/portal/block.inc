<td class="headerbox" valign="top" style="width:<?php echo ($width * $colspan) ?>%" rowspan="<?php echo $rowspan ?>" colspan="<?php echo $colspan ?>">
 <div class="blockHeader">
  <span class="rightFloat blockIcons">
   <?php echo $this->getHeaderIcons($row_num, $col_num, true) ?>
  </span>
  <h1 class="header"><?php echo $header ?></h1>
 </div>
 <div id="<?php echo $block_id ?>">
  <?php echo $content ?>
 </div>
</td>
