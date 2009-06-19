</tbody>
</table>

<?php if ($fl->revisionCount() > 100 && !Horde_Util::getFormData('all')): ?>
<div class="options">
 <span>
  <?php printf(_("Only showing the 100 latest revisions. %sShow all revisions?</a> (may take a while)."), Horde::link(Horde_Util::addParameter(Horde::selfUrl(true), 'all', 1))) ?>
 </span>
</div>
<?php endif ?>
