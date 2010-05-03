<?php
/**
 * $Horde: jonah/config/templates.php.dist,v 1.15 2007/10/10 03:49:00 mrubinsk Exp $
 *
 * This file stores the templates used to generate different views of
 * news channels.
 */

$templates['standard'] = array('name' => _("Standard"),
                               'template' => '<table width="100%" cellspacing="0" class="linedRow">
<if:error><tr class="text"><td><em><tag:error /></em></td></tr></if:error>
<if:stories>
<loop:stories>
<tr><td><strong><a target="_blank" href="<tag:stories.story_link />"><tag:story_marker /> <tag:stories.story_title /></a></strong><div style="padding-left:10px"><tag:stories.story_desc /></div></td></tr>
</loop:stories>
</if:stories>

<if:form><tr><td><tag:form /></td></tr></if:form></table>');

$templates['media'] = array('name' => _("Media"),
                               'template' => '<table width="100%" cellspacing="0" class="linedRow">
<if:error><tr class="text"><td><em><tag:error /></em></td></tr></if:error>
<if:stories>
<loop:stories>
<tr><td><strong><a target="_blank" href="<tag:stories.story_link />"><img title="<tag:stories.story_media_description />" style="margin:7px;" src="<tag:stories.story_media_thumbnail_url />" /></a></strong><div style="margin:7px;"><a target="_blank" href="<tag:stories.story_link />"><tag:stories.story_media_title /></a></div></td></tr>
</loop:stories>
</if:stories>

<if:form><tr><td><tag:form /></td></tr></if:form></table>');

$templates['internal'] = array('name' => _("Internal"),
                               'template' => '<table width="100%" cellspacing="0" class="linedRow">
<if:error><tr class="text"><td><em><tag:error /></em></td></tr></if:error>
<if:stories>
<loop:stories>
<tr><td><strong><a href="<tag:stories.story_link />"><tag:story_marker /> <tag:stories.story_title /></a></strong><div style="padding-left:10px"><tag:stories.story_desc /></div></td></tr>
</loop:stories>
</if:stories>

<if:form><tr><td><tag:form /></td></tr></if:form></table>');

$templates['compact'] = array('name' => _("Compact"),
                              'template' => '<table width="100%" cellspacing="1" class="linedRow">
<if:error><tr class="text"><td><em><tag:error /></em></td></tr></if:error>
<if:stories>
<loop:stories>
<tr class="text"><td><a target="_blank" href="<tag:stories.story_link />"><tag:story_marker /> <tag:stories.story_title /></a></td></tr>
</loop:stories>
</if:stories>

<if:form><tr><td><tag:form /></td></tr></if:form></table>');

$templates['ultracompact'] = array('name' => _("Ultracompact"),
                                   'template' => '<if:error><em><tag:error /></em></if:error>
<if:stories>
<loop:stories>
:: <a target="blank" href="<tag:stories.story_link />"><tag:stories.story_title /></a>
</loop:stories>
</if:stories>');
