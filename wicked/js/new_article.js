
Event.observe(window, 'load', function() {

  Event.observe('newEventButton', 'click', function(event) {
    Event.stop(event);

    RedBox.showInline('newArticleForm');
    $('page_title').focus();

  });

});
