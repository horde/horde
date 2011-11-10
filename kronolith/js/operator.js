var kronolith = {
    description: "Imports events into Kronolith",
    shortDescriptions: "Kronolith",
    scope: {
        semantic: {
            vevent: "vevent"
        }
    },
    doActionAll: function(a, b) {
        console.log(a, b);
        debugger;
    },
    doAction: function(e) {
        console.log(e);
        debugger;
    }
};

SemanticActions.add("kronolith", kronolith);
