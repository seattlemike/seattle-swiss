function asyncTogTeam(teamId, teamSeed, doCase, cbk) {
    var async = new asyncPost()
    async.onSuccess = cbk
    var fd = new FormData()
    fd.append("case", doCase)
    fd.append("team_id", teamId)
    fd.append("team_seed", teamSeed)
    fd.append("module_id", document.forms.details.module_id.value)
    async.post(fd)
}

function delModule() {
    d = new Dialog("Delete this round?",
                    function () { var form = document.forms.details; form["case"].value="delete_module"; form.submit() } )
    d.show()
}

function togTeamCompeting(button, row) {
    var teamId = row.getAttribute("data-id");
    var input = row.getElementsByTagName('input')[0];
    if (row.getAttribute("data-seed")) { // if we have a seed / are enabled
        button.onclick = "";
        button.innerHTML = "[working...]";
        asyncTogTeam(teamId, 0, "ModuleTeamDel", 
                    function () { 
                        row.setAttribute("data-seed", "")
                        button.innerHTML = "Not Competing"
                        button.className = "button"
                        button.onclick = function () { togTeamCompeting(button, row) }
                        input.value = ""
                        input.disabled = true
                        checkSaveSeeds()
                    })
    } else {
        var newSeed = getMaxSeed()+1;
        button.onclick = "";
        button.innerHTML = "[working...]";
        asyncTogTeam(teamId, newSeed, "ModuleTeamAdd", 
                    function () { 
                        row.setAttribute("data-seed", newSeed);
                        button.innerHTML = "Competing" 
                        button.className = "button selected"
                        button.onclick = function () { togTeamCompeting(button, row) } 
                        input.value = newSeed;
                        input.disabled = false
                        checkSaveSeeds()
                    });
    }
}

function getMaxSeed() {
    var teams = document.querySelectorAll(".module-team")
    var max=0;
    for (var i=0; i<teams.length; i++) {
        if (teams[i].getAttribute("data-seed")) {
            var input = teams[i].getElementsByTagName('input')[0]
            var seed = parseInt(input.value)
            if (!isNaN(seed) && (seed > max))
                max=seed;
        }
    }
    return max;
}

function checkSaveSeeds() {
    // where are original values stored?
    var needSort = false;
    var hasGap = false;
    var teams = document.querySelectorAll(".module-team")
    var saveButton = document.getElementById("save-seeds")
    for (var i=0; (i<teams.length) && !needSort; i++) {
        if (teams[i].getAttribute("data-seed")) {
            var input = teams[i].getElementsByTagName('input')[0]
            if (hasGap || (parseInt(input.value) != i+1) || (parseInt(input.value) != parseInt(teams[i].getAttribute("data-seed"))))
                needSort=true;
        } else {
            hasGap = true;
        }
    }
    if (needSort) {
        saveButton.className = "button selected"
        saveButton.onclick = function () { document.forms.teams.submit() }
    } else {
        saveButton.className = "button disabled"
        saveButton.onclick = ""
    }
}

function saveSeeds() {
    // sort teams
    /*
    var teams = document.querySelectorAll(".module-team")
    var saveButton = document.getElementById("save-seeds")
    var inputs = []
    var newSeeds = []
    for (var i=0; i<teams.length; i++) {
        inputs[i] = teams[i].getElementsByTagName('input')[0]
        if (inputs[i].getAttribute("data-seed") && isNaN(parseInt(inputs[i].value)))
            inputs[i].value = teams[i].getAttribute("data-seed");
    }
    while(teams.length) {
        var idx=0, minSeed=parseInt(inputs[value])
        for (var i=1; i<teams.length; i++) {
            if isNan(parseInt(
        }
        newSeeds.push( teams[idx] )
        delete teams[idx]
        delete inputs[idx]
    }
    */
    // save to db
    document.forms.teams.submit()
}

function moduleOnLoad() {
    var teams = document.querySelectorAll(".module-team")
    for (var i=0; i<teams.length; i++) {
        var button = teams[i].getElementsByTagName('a')[0]
        // closure here to assign appropriately scoped value of teams[i]
        button.onclick = (function (row) { return function () { togTeamCompeting( this, row ) } })(teams[i])
        var input = teams[i].getElementsByTagName('input')[0]
        input.onkeyup = checkSaveSeeds
    }
    document.getElementById("del-btn").onclick = delModule;
}
