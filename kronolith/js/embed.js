Event.observe(window, 'load', function() {
    var nodeCount = kronolithNodes.length;
    for (var n = 0; n < nodeCount; n++) {
        var j = kronolithNodes[n];
        $(j).update(kronolith[j]);
    }
});
