document.observe('dom:loaded', function() {
    $('submit').observe('click', function(e) {
        if (!$F('oldpassword')) {
            alert(Passwd.current_pass);
            $('oldpassword').focus();
            e.stop();
            return;
        }
        if (!$F('newpassword0')) {
            alert(Passwd.new_pass);
            $('newpassword0').focus();
            e.stop();
            return;
        }
        if (!$F('newpassword1')) {
            alert(Passwd.verify_pass);
            $('newpassword1').focus();
            e.stop();
            return;
        }
        if ($F('newpassword0') != $F('newpassword1')) {
            alert(Passwd.no_match);
            $('newpassword0').focus();
            e.stop();
            return;
        }
    }.bindAsEventListener());
});
