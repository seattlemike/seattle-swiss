function tryUpdateDetails(details) {
    var async = new asyncPost()
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
    async.post( { "case" : "UpdateModule", 
                  "module_id" : details.mid,
                  "module_name" : details.Name.input.value,
                  "module_text" : details.Text.input.value,
                  "module_date" : details.DateStr.input.value,
                  "module_mode" : details.Mode.index } )
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

function getTeams() {
    var teams = {}
    var data = JSON.parse(document.getElementById("module-teams").getAttribute("data-teams"))
    for (var i=0; i<data.length; i++) {
        teams[data[i].team_id] = data[i]
    }
    return teams
}

function editTeams() {
    var listBox = buildNode("div", "compact dialog-form")
    var teamList = getTeams()
    
    for (var id in teamList) {
        var node = buildNode("div", "viable item greyed", teamList[id].team_name)
        teamList[id].toggle = new ToggleClass(node, ["viable item greyed", "viable item"])
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

        var teamDelta = []
        for (id in teamList) {
            var t = teamList[id]
            if ((! t.toggle.index) && (t.original)) {
                teamDelta.push({"id":id, "state":0})
                tList.removeChild(t.original)
            } else if ((t.toggle.index) && (! t.original)) {
                teamDelta.push({"id":id, "state":1})
                tList.appendChild(buildNode("div", "item", t.team_name))
                tList.lastChild.setAttribute("data-id", id)
            }
        }
        var mid = document.getElementById("module-details").getAttribute("data-mid")
        var async = new asyncPost()
        async.post({'case':"ModuleTeams", 'module_id':mid, 'team_data':JSON.stringify(teamDelta)})
    } )
    d.insert(listBox)
    d.show()
}

function editSeeds() {
    var listBox = buildNode("div", "compact dialog-form")
    var teamIndex = getTeams()
    var teamList = []

    var moveTo = function(selected, t) {
        var shift = Math.round((t.index - selected.index) / Math.abs(t.index - selected.index))
        while (selected.index - shift != t.index) {
            teamList[selected.index] = teamList[selected.index + shift]
            teamList[selected.index].index = selected.index
            selected.index += shift
        }
        teamList[selected.index] = selected

        // redraw team list
        for (var i=0; i<teamList.length; i++) {
            listBox.removeChild(teamList[i].node)
            listBox.appendChild(teamList[i].node)
        }
    }

    var dragReset = function(selected) {
        // reset state of list
        for (var i=0; i<teamList.length; i++) {
            teamList[i].node.className = "viable item"
            teamList[i].node.onmouseover = function () {}
        }
        // prime list for dragging 'selected'
        if (selected) {
            selected.node.className = "viable item selected"
            for (var i=0; i<teamList.length; i++) {
                teamList[i].node.onmouseover = function(t) { return function () { moveTo(selected, t) } }(teamList[i])
            }
            selected.node.onmouseover = function () {}
        }
    }
    
    var midteams = document.getElementById("team-list").children
    for (var i=0; i<midteams.length; i++) {
        var t = teamIndex[parseInt(midteams[i].getAttribute("data-id"))]
        t.original = midteams[i]
        t.index = i
        t.node = buildNode("div", "viable item", t.team_name)
        t.node.onmousedown = function (team) { return function () { dragReset(team); return false } }(t)
        listBox.appendChild(t.node)
        teamList.push(t)
    }
    listBox.onmouseup = function () { dragReset() }

    var updateSeeds = function () {
        var teamSeeds = [] 
        var midBox = document.getElementById("team-list")
        for (var i=0; i<teamList.length; i++) {
            var t = teamList[i]
            midBox.removeChild(t.original)
            midBox.appendChild(t.original)
            teamSeeds.push({"id":t.team_id, "seed":t.index+1})
        }
        var async = new asyncPost()
        var mid = document.getElementById("module-details").getAttribute("data-mid")
        async.post( { "case":"ModuleSeeds", "module_id":mid, "team_data":JSON.stringify(teamSeeds) } )
    }

    var d = new Dialog("Seeding order", updateSeeds)
    d.insert(listBox)
    d.show()
}

function moduleOnLoad() {
    document.getElementById("edit-details").onclick = editDetails;
    document.getElementById("edit-teams").onclick = editTeams;
    document.getElementById("edit-seeds").onclick = editSeeds;
}
