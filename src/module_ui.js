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
    d = new Dialog("Delete this module?",
                    function () { var form = document.forms.details; form["case"].value="delete_module"; form.submit() } )
    d.show()
    var fd = { "case" : "DelModule", "module_id" : tid }
    var d = new Dialog("Remove yourself from the Admin list?", function () { buildSyncForm(fd).submit() })
    d.show()
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

function tryUpdateDetails(details) {
    var async = new asyncPost()
    var fd = new FormData()
    fd.append("case", "UpdateModule")
    fd.append("module_id", details.mid)
    fd.append("module_name", details.Name.input.value)
    fd.append("module_text", details.Text.input.value)
    fd.append("module_date", details.DateStr.input.value)
    fd.append("module_mode", details.Mode.index)
    async.onSuccess = function(r) { 
        document.getElementById("title").innerHTML=details.Name.input.value
        details.Name.original.innerHTML = details.Name.input.value
        details.Text.original.innerHTML = details.Text.input.value
        details.DateStr.original.innerHTML = details.DateStr.input.value
        details.Mode.original.innerHTML = details.Mode.state
        var btn = document.getElementById("edit-details")
        btn.className = "button"
        btn.onclick = editDetails
    }
    async.post(fd)
}
function editDetails() {
    // Label, Input, Value
    var Detail = function(node, title) {
        this.original = node
        this.value = node.innerHTML
        this.node = buildNode("label","",title)
        this.input = document.createElement("input")
        this.input.value = this.value
        this.node.appendChild(this.input)
    }

    var node = document.getElementById("module-details")
    var form = buildNode("div", "dialog-form", "")
    var details = {}
    details.DateStr = new Detail(node.children[1], "Date")
    details.Name = new Detail(node.children[2], "Name")
    details.Text = new Detail(node.children[3], "Text")
    for (i in details)
        form.appendChild(details[i].node)

    details.mid = node.getAttribute("data-mid")
    togBtn = buildNode("a","button","")
    details.Mode = new ToggleText(togBtn, ["Swiss Rounds", "Single Elimination", "Double Elimination"])
    details.Mode.original = node.children[4]
    details.Mode.setState(node.children[4].innerHTML)
    form.appendChild(buildNode("label","","Match Mode"))
    form.lastChild.appendChild(buildNode("div", "lline"))
    form.lastChild.lastChild.appendChild(togBtn)

    if (node.getAttribute("data-owner")) {
        var delBtn = buildNode("a","button","Delete")
        form.appendChild(buildNode("label","","Delete Module?"))
        form.lastChild.appendChild(buildNode("div", "lline"))
        form.lastChild.lastChild.appendChild(delBtn)
        delBtn.onclick = function() { 
            var fd = { "case" : "DelModule", "module_id" : details.mid }
            var delDialog = new Dialog("Delete this module?", function () { buildSyncForm(fd).submit() })
            delDialog.show()
        }
    }
    var d = new Dialog(details.Name.value, 
                       function () { 
                            // disable edit-details until success
                            var btn = document.getElementById("edit-details")
                            btn.onclick = ""
                            btn.className = "button disabled"
                            tryUpdateDetails(details)
                        } )
    d.insert(form)
    d.show()
}

function editTeams() {
    var listBox = buildNode("div", "compact dialog-form")
    var teamList = {}

    var allteams = JSON.parse(document.getElementById("module-teams").getAttribute("data-teams"))
    for (var i=0; i<allteams.length; i++) {
        var node = buildNode("div", "viable item greyed", allteams[i].team_name)
        allteams[i].toggle = new ToggleClass(node, ["viable item greyed", "viable item selected"])
        teamList[allteams[i].team_id] = allteams[i]
        listBox.appendChild(node)
    }

    var midteams = document.getElementById("team-list").children
    for (var i=0; i<midteams.length; i++) {
        var t = teamList[parseInt(midteams[i].getAttribute("data-id"))]
        t.original = midteams[i]
        t.toggle.setIndex(1)
    }

    var d = new Dialog("Participating Teams", function () {
        var tList = document.getElementById("team-list")
        var mid = document.getElementById("module-details").getAttribute("data-mid")
        for (var i=0; i<allteams.length; i++) {
            var fd = new FormData()
            fd.append("team_id", allteams[i].team_id)
            fd.append("module_id", mid)
            if ((! allteams[i].toggle.index) && (allteams[i].original)) {
                var async = new asyncPost()
                fd.append("case","ModuleDelTeam")
                async.post(fd)
                tList.removeChild(allteams[i].original)
            } else if ((allteams[i].toggle.index) && (! allteams[i].original)) {
                var async = new asyncPost()
                fd.append("case","ModuleAddTeam")
                async.post(fd)
                tList.appendChild(buildNode("div", "item", allteams[i].team_name))
                tList.lastChild.setAttribute("data-id", allteams[i].team_id)
            }
        }
    })
    d.insert(listBox)
    d.show()

}
function editSeeds() {

}

function moduleOnLoad() {
    document.getElementById("edit-details").onclick = editDetails;
    document.getElementById("edit-teams").onclick = editTeams;
    document.getElementById("edit-seeds").onclick = editSeeds;
}
