var KronolithNew = {
    calendarSelectHandler: function(e)
    {
        e.element().previous().setValue(e.memo.toString(Kronolith.conf.date_format));
    }
};

document.observe('Horde_Calendar:select', KronolithNew.calendarSelectHandler.bindAsEventListener(KronolithNew));

