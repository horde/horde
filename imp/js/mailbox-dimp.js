/**
 * mailbox-dimp.js - Template used to format the rows in the message list
 * display.
 *
 * See the documentation of prototypejs::Template for the template format:
 *   http://www.prototypejs.org/api/template
 *
 * Copyright 2005-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

DimpBase.msglist_template_horiz =
'<div class="#{className}" id="#{VP_domid}" style="#{style}">' +
 '<div class="msgStatus sep">' +
  '<div class="iconImg msCheck"></div>' +
  '#{status}' +
 '</div>' +
 '<div class="msgFrom sep">#{from}</div>' +
 '<div class="msgSubject sep" title="#{subjecttitle}">#{subjectdata}#{subject}</div>' +
 '<div class="msgDate sep">#{date}</div>' +
 '<div class="msgSize">#{size}</div>' +
'</div>';

DimpBase.msglist_template_vert =
'<div class="#{className}" id="#{VP_domid}" style="#{style}">' +
 '<div class="iconImg msgStatus">' +
  '<div class="iconImg msCheck"></div>' +
  '#{status}' +
 '</div>' +
 '<div>' +
  '<div class="msgFrom">#{from}</div>' +
  '<div class="msgDate">#{date}</div>' +
 '</div>' +
 '<div>' +
  '<div class="msgSubject" title="#{subjecttitle}">#{subjectdata}#{subject}</div>' +
 '</div>' +
'</div>';
