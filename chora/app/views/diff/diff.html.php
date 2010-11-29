<div class="diff"<?php if ($id) echo ' id="' . $id . '"' ?>>
 <div class="diff-header"><h4><a href="<?php echo Chora::url('co', $file->queryModulePath(), array('onb' => $r1)) ?>"><?php echo $file->queryModulePath() ?></a></h4></div>
 <!--
 <div class="diff-container">
  <div class="diff-left"><?php echo $this->escape($r1) ?></div>
  <div class="diff-right"><?php echo $this->escape($r2) ?></div>
 </div>
 -->

 <div class="diff-container">
  <div class="diff-left">
   <div class="diff-linenumbers">
    <ol>
    <?php foreach ($leftNumbers as $ln): ?>
     <li><?php echo $ln ? $ln : '&nbsp;' ?></li>
    <?php endforeach ?>
    </ol>
   </div>

   <div class="diff-listing">
    <?php foreach ($leftLines as $leftSection): ?>
    <pre class="diff-<?php echo $leftSection['type'] ?>"><?php foreach ($leftSection['lines'] as $ll):
      echo ($ll ? $this->escape($ll) : '&nbsp;') . "\n";
      endforeach ?></pre>
    <?php endforeach ?>
   </div>
  </div>

  <div class="diff-right">
   <div class="diff-linenumbers">
    <ol>
    <?php foreach ($rightNumbers as $rn): ?>
     <li><?php echo $rn ? $rn : '&nbsp;' ?></li>
    <?php endforeach ?>
    </ol>
   </div>

   <div class="diff-listing">
    <?php foreach ($rightLines as $rightSection): ?>
    <pre class="diff-<?php echo $rightSection['type'] ?>"><?php foreach ($rightSection['lines'] as $rl):
      echo ($rl ? $this->escape($rl) : '&nbsp;') . "\n";
      endforeach ?></pre>
    <?php endforeach ?>
   </div>
  </div>
 </div>
</div>
