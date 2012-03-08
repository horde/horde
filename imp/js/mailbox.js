/**
 * Provides the javascript for the mailbox.php script (standard view).
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

var ImpMailbox = {
    // The following variables are defined in mailbox.php:
    //  unread

    countSelected: function()
    {
        return $('messages').select('[name="indices[]"]').findAll(Form.Element.getValue).size();
    },

    selectRow: function(id, select)
    {
        if (id.readAttribute('id')) {
            [ id ].invoke(select ? 'addClassName' : 'removeClassName', 'selectedRow');
        }
        id.down('INPUT.checkbox').setValue(select);
    },

    submit: function(actID)
    {
        switch (actID) {
        case 'filter_messages':
            // No-op
            break;

        default:
            if (!this.countSelected()) {
                alert(IMP.text.mailbox_submit);
                return;
            }
            break;
        }

        switch (actID) {
        case 'delete_messages':
            if (IMP.conf.pop3 && !confirm(IMP.text.mailbox_delete)) {
                return;
            }
            break;

        case 'spam_report':
            if (!confirm(IMP.text.spam_report)) {
                return;
            }
            break;

        case 'nostpam_report':
            if (!confirm(IMP.text.notspam_report)) {
                return;
            }
            break;
        }

        $('actionID').setValue(actID);
        $('messages').submit();
    },

    selectRange: function(e)
    {
        // elt = checkbox element
        var elt = e.element(),
            tr = elt.up('TR'),
            checked = $F(elt),
            end, start;

        if (this.startrange && e.shiftKey) {
            if (this.startrange != elt) {
                // Dirty trick - use position in page to determine which way
                // to traverse
                if (this.startrange.offsetTop < tr.offsetTop) {
                    start = this.startrange.next();
                    end = tr;
                } else {
                    start = tr;
                    end = this.startrange.previous();
                }

                do {
                    this.selectRow(start, checked);
                    if (start == end) {
                        break;
                    }
                    start = start.next();
                } while (start);
            }
        } else {
            this.selectRow(tr, checked);
            this.cursor = tr;
        }

        this.startrange = tr;
    },

    updateMboxes: function(form)
    {
        var tm1 = $('targetMailbox1'),
            tm2 = $('targetMailbox2');

        if (tm2) {
            tm1.selectedIndex = tm2.selectedIndex = (form == 1)
                ? tm1.selectedIndex
                : tm2.selectedIndex;
        }
    },

    _transfer: function(actID)
    {
        var elt, newMbox, target, tmbox;

        if (this.countSelected()) {
            elt = $('targetMailbox1');
            target = $F(elt);
            tmbox = $('targetMbox');
            tmbox.setValue(target);

            // Check for a mailbox actually being selected.
            if ($(elt[elt.selectedIndex]).hasClassName('flistCreate')) {
                newMbox = prompt(IMP.text.newmbox, '');
                if (newMbox != null && newMbox != '') {
                    $('newMbox').setValue(1);
                    tmbox.setValue(newMbox);
                    this.submit(actID);
                }
            } else if (target.empty()) {
                alert(IMP.text.target_mbox);
            } else if (target.startsWith("notepad\0") ||
                       target.startsWith("tasklist\0")) {
                this.actIDconfirm = actID;
                IMPDialog.display({
                    cancel_text: IMP.text.no,
                    form_id: 'RB_ImpMailboxConfirm',
                    noinput: true,
                    ok_text: IMP.text.yes,
                    text: IMP.text.moveconfirm
                });
            } else {
                this.submit(actID);
            }
        } else {
            alert(IMP.text.mailbox_selectone);
        }
    },

    flagMessages: function(form)
    {
        var f1 = $('flag1'), f2 = $('flag2');

        if ((form == 1 && $F(f1) != "") ||
            (form == 2 && $F(f2) != "")) {
            if (this.countSelected()) {
                $('messages').down('[name=flag]').setValue((form == 1) ? $F(f1) : $F(f2));
                this.submit('flag_messages');
            } else {
                if (form == 1) {
                    f1.selectedIndex = 0;
                } else {
                    f2.selectedIndex = 0;
                }
                alert(IMP.text.mailbox_selectone);
            }
        }
    },

    filterMessages: function(form)
    {
        var f1 = $('filter1'), f2 = $('filter2');

        if ((form == 1 && $F(f1) != "") ||
            (form == 2 && $F(f2) != "")) {
            $('messages').down('[name=filter]').setValue((form == 1) ? $F(f1) : $F(f2));
            this.submit('filter_messages');
        }
    },

    getMessage: function(id, offset)
    {
        if (!offset) {
            return id;
        }

        var mlist = $H(this.messagelist).keys(),
            i = mlist.indexOf(id),
            j = i + offset;

        if (i != -1) {
            if (j >= 0 && j < mlist.length) {
                return mlist[j];
            }
        }

        return '';
    },

    changeHandler: function(e)
    {
        var id = e.element().readAttribute('id');

        if (id) {
            if (id.startsWith('flag')) {
                this.flagMessages(id.substring(4));
            } else if (id.startsWith('filter')) {
                this.filterMessages(id.substring(6));
            } else if (id.startsWith('targetMailbox')) {
                this.updateMboxes(id.substring(13));
            }
        }
    },

    clickHandler: function(e)
    {
        if (e.isRightClick()) {
            return;
        }

        var elt = e.element(), id;

        while (Object.isElement(elt)) {
            if (elt.match('.msgactions A.widget')) {
                if (elt.hasClassName('moveAction')) {
                    this._transfer('move_messages');
                    e.stop();
                } else if (elt.hasClassName('copyAction')) {
                    this._transfer('copy_messages');
                    e.stop();
                } else if (elt.hasClassName('permdeleteAction')) {
                    if (confirm(IMP.text.mailbox_delete)) {
                        this.submit('delete_messages');
                    }
                    e.stop();
                } else if (elt.hasClassName('deleteAction')) {
                    this.submit('delete_messages');
                    e.stop();
                } else if (elt.hasClassName('undeleteAction')) {
                    this.submit('undelete_messages');
                    e.stop();
                } else if (elt.hasClassName('blacklistAction')) {
                    this.submit('blacklist');
                    e.stop();
                } else if (elt.hasClassName('whitelistAction')) {
                    this.submit('whitelist');
                    e.stop();
                } else if (elt.hasClassName('forwardAction')) {
                    this.submit('fwd_digest');
                    e.stop();
                } else if (elt.hasClassName('redirectAction')) {
                    this.submit('redirect_messages');
                    e.stop();
                } else if (elt.hasClassName('spamAction')) {
                    this.submit('spam_report');
                    e.stop();
                } else if (elt.hasClassName('notspamAction')) {
                    this.submit('notspam_report');
                    e.stop();
                } else if (elt.hasClassName('viewAction')) {
                    this.submit('view_messages');
                    e.stop();
                } else if (elt.hasClassName('templateeditAction')) {
                    switch (this.countSelected()) {
                    case 0:
                        alert(IMP.text.mailbox_selectone);
                        break;

                    case 1:
                        this.submit('template_edit');
                        break;

                    default:
                        alert(IMP.text.mailbox_selectonlyone);
                        break;
                    }
                    e.stop();
                }
                return;
            } else if (elt.hasClassName('checkbox')) {
                this.selectRange(e);
                // Fall through to elt.up() call below.
            }

            id = elt.readAttribute('id');
            if (!id) {
                elt = elt.up();
                continue;
            }

            switch (id) {
            case 'checkheader':
            case 'checkAll':
                if (id == 'checkheader') {
                    $('checkAll').checked = !$('checkAll').checked;
                }

                $('messages').select('TABLE.messageList TR[id]').each(function(i, s) {
                    this.selectRow(i, $F('checkAll'));
                }, this);
                return;

            case 'delete_vfolder':
                this.lastclick = elt.readAttribute('href');
                IMPDialog.display({
                    cancel_text: IMP.text.no,
                    form_id: 'RB_ImpMailbox',
                    noinput: true,
                    ok_text: IMP.text.yes,
                    text: IMP.text.mailbox_delete_vfolder
                });
                e.stop();
                return;

            case 'empty_mailbox':
                this.lastclick = elt.readAttribute('href');
                IMPDialog.display({
                    cancel_text: IMP.text.no,
                    form_id: 'RB_ImpMailbox',
                    noinput: true,
                    ok_text: IMP.text.yes,
                    text: IMP.text.mailbox_delete_all
                });
                e.stop();
                return;
            }

            if (elt.match('TH') &&
                elt.up('TABLE.messageList')) {
                document.location.href = elt.down('A').href;
            }

            elt = elt.up();
        }
    },

    keyDownHandler: function(e)
    {
        var elt = e.element(),
            key = e.keyCode,
            loc, search, tmp;

        if (e.altKey || e.ctrlKey) {
            if (!(key == Event.KEY_UP || key == Event.KEY_DOWN)) {
                return;
            }

            if (!this.cursor) {
                this.cursor = elt.up('TABLE.messageList TR');
            }

            if (this.cursor) {
                switch (key) {
                case Event.KEY_UP:
                    tmp = this.cursor.previous();
                    if (!tmp.readAttribute('id')) {
                        tmp = this.cursor.up('TABLE.messageList').previous('TABLE.messageList');
                        if (tmp) {
                            tmp = tmp.select('TR[id]').last();
                        } else {
                            search = 'last';
                        }
                    }
                    this.cursor = tmp;
                    break;

                case Event.KEY_DOWN:
                    tmp = this.cursor.next();
                    if (!tmp) {
                        tmp = this.cursor.up('TABLE.messageList').next('TABLE.messageList');
                        if (tmp) {
                            tmp = tmp.select('TR[id]').first();
                        } else {
                            search = 'first';
                        }
                    }
                    this.cursor = tmp;
                    break;
                }
            } else {
                search = key == Event.KEY_DOWN ? 'first' : 'last';
            }

            if (search) {
                tmp = $('messages').select('TABLE.messageList TR[id]');
                this.cursor = (search == 'first') ? tmp.first() : tmp.last();
            }

            this.cursor.down('TD A.mboxSubject').focus();
            if (e.altKey) {
                this.selectRow(this.cursor, !$F(this.cursor.down('INPUT.checkbox')));
            }
        } else if (key == 32 && this.cursor) {
            this.selectRow(this.cursor, !$F(this.cursor.down('INPUT.checkbox')));
        } else if (!e.shiftKey) {
            if (key == Event.KEY_LEFT && $('prev')) {
                loc = $('prev');
            } else if (key == Event.KEY_RIGHT && $('next')) {
                loc = $('next');
            }

            if (loc) {
                document.location.href = loc.readAttribute('href');
            }
            return;
        } else {
            return;
        }

        e.stop();
    },

    submitHandler: function(e)
    {
        if (e.element().hasClassName('navbarselect')) {
            e.stop();
        }
    }

};

document.observe('dom:loaded', function() {
    var im = ImpMailbox;

    document.observe('click', im.clickHandler.bindAsEventListener(im));
    document.observe('keydown', im.keyDownHandler.bindAsEventListener(im));
    document.observe('submit', im.submitHandler.bindAsEventListener(im));

    if (Prototype.Browser.IE) {
        $('flag1', 'filter1', 'targetMailbox1', 'flag2', 'filter2', 'targetMailbox2').compact().invoke('observe', 'change', im.changeHandler.bindAsEventListener(im));
    } else {
        document.observe('change', im.changeHandler.bindAsEventListener(im));
    }

    if (window.fluid) {
        try {
            window.fluid.setDockBadge(ImpMailbox.unread);
        } catch (e) {}
    }
});

document.observe('IMPDialog:onClick', function(e) {
    switch (e.element().identify()) {
    case 'RB_ImpMailbox':
        window.location = this.lastclick;
        break;

    case 'RB_ImpMailboxConfirm':
        this.submit(this.actIDconfirm);
        break;
    }
}.bindAsEventListener(ImpMailbox));
