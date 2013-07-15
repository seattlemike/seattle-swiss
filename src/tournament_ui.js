/*  
    Copyright 2011, 2012, 2013 Mike Bell and Paul Danos

    This file is part of 20Swiss.
    
    20Swiss is free software: you can redistribute it and/or modify it under the
    terms of the GNU Affero General Public License as published by the Free
    Software Foundation, either version 3 of the License, or (at your option)
    any later version.

    20Swiss is distributed in the hope that it will be useful, but WITHOUT ANY
    WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
    FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License for
    more details.

    You should have received a copy of the GNU Affero General Public License
    along with 20Swiss.  If not, see <http://www.gnu.org/licenses/>. 
*/

// Torunamnent UI

function clearOnce() {
    this.value = "";
    this.onclick = "";
}

function buildTeamNode(node, team) {
    removeChildren(node)
    node.setAttribute("data-name", team.name)
    node.setAttribute("data-text", team.details)
    node.setAttribute("data-uid", team.uid)
    if (team.teamid)
        node.setAttribute("data-teamid", team.teamid)
    node.appendChild(buildNode("div", "team-name info", team.name))
    node.appendChild(buildNode("div", "team-text", team.details))
    return node
}

function processBulk(text) {
    alert("this is not yet working");
}

function bulkAddTeams() {
    var teamInput = buildNode("textarea","medium","team name / details / uid, one team per line");
    teamInput.onclick = clearOnce
    //TODO
    var d = new Dialog("Add many teams [NOT YET WORKING]",
                    function () { processBulk(teamInput.value) })
    d.insert(teamInput)
    d.show()
}

function addTeam(tid, name, details, uid) {
    // build team node
    var t = buildNode("div", "team", "")
    t.appendChild(buildNode("div", "team-name info", name))
    t.appendChild(buildNode("div", "team-text", details))
    t.setAttribute("data-name", name)
    t.setAttribute("data-text", details)
    t.setAttribute("data-uid", uid)
    var list = document.getElementById("teams-list")
    list.appendChild(t)

    // add team to database
    var async = new asyncPost()
    var fd = new FormData()
    fd.append("case", "NewTeam")
    fd.append("tournament_id", tid)
    fd.append("team_name", name)
    fd.append("team_details", details)
    fd.append("team_uid", uid)
    async.onSuccess = function (r) { 
        t.onclick = function () { editTeam(t) }
        t.className='team viable'
        t.setAttribute("data-teamid", r.teamId)
    }
    async.post(fd)
}

function tryDeleteTeam(node) {
    var tourney = document.getElementById("details")
    var async = new asyncPost()
    var fd = new FormData()
    fd.append("case", "DeleteTeam")
    fd.append("tournament_id", tourney.getAttribute("data-tid") )
    fd.append("team_id", node.getAttribute("data-teamid") )
    async.onSuccess = function (r) {
        if (r.deleted == true) {
            node.parentNode.removeChild(node) 
        } else {
            alert("Failed to delete team with TOURNAMENT GAMES pending/played")
            node.onclick = function () { editTeam(node) }
            node.className="team viable"
        }
    }
    node.onclick = ""
    node.className="team"
    async.post(fd)
}

function tryUpdateTeam(node, team) {
    tourney = document.getElementById("details")
    var async = new asyncPost()
    var fd = new FormData()
    fd.append("case", "UpdateTeam")
    fd.append("tournament_id", tourney.getAttribute("data-tid") )
    fd.append("team_id", team.teamid)
    fd.append("team_name", team.name)
    fd.append("team_details", team.details)
    fd.append("team_uid", team.uid)
    async.onSuccess = function () { 
        node.onclick = function () { editTeam(node) }
        node.className = "team viable"
    }

    buildTeamNode(node, team)
    async.post(fd);
}

function editTeam(node) {
    var team = {}
    team.name = node.getAttribute("data-name")
    team.details = node.getAttribute("data-text")
    team.uid = node.getAttribute("data-uid")
    var teamForm = buildNode("div", "dialog-form", "")
    var name = buildNode("label", "", "Name")
    name.appendChild( buildInput("team-name", team.name) )
    var details = buildNode("label", "", "Details")
    details.appendChild( buildInput("team-details", team.details) )
    var uid = buildNode("label", "", "UID")
    uid.appendChild( buildInput("team-uid", team.uid) )
    uid.lastChild.className = "numeric"
    var delBtn = buildNode("a", "button", "Delete");
    delBtn.onclick = function () {
        var del = new Dialog("Are you sure you want to delete "+team.name+"?", function () { tryDeleteTeam(node); d.hide() } )
         del.show()
    }
    teamForm.appendChild(name)
    teamForm.appendChild(details)
    teamForm.appendChild(uid)
    var input = buildNode("label","","Delete Team?")
    teamForm.appendChild(input)
    input.appendChild(buildNode("div", "lline"))
    input.lastChild.appendChild(delBtn)


    var d = new Dialog(team.name, 
                       function () { 
                            team.teamid = node.getAttribute("data-teamid")
                            team.name = name.lastChild.value
                            team.details = details.lastChild.value
                            team.uid = uid.lastChild.value
                            node.className = "team"
                            node.onclick = ""
                            tryUpdateTeam(node, team)
                        } )
    d.insert(teamForm)
    d.show()
}

function addModule(tid) {
    // build module node
    var rssBtn = buildNode("img")
    rssBtn.width=14
    rssBtn.height=14
    rssBtn.src="/img/feed-icon-28x28.png"
    rssBtn.href="/"
    var m = buildNode("div", "item")
    m.appendChild(document.createElement('a'))
    m.firstChild.title = "Follow via RSS"
    m.firstChild.appendChild(rssBtn)
    m.appendChild(buildNode("a", "", "New Module"))
    var mList = document.getElementById("modules-list")
    if (mList.firstChild.nodeName == "P")
        mList.removeChild(mList.firstChild)
    mList.appendChild(m)

    // add module to database
    var async = new asyncPost()
    var fd = new FormData()
    fd.append("case", "NewModule")
    fd.append("tournament_id", tid)
    async.onSuccess = function (r) { 
        m.firstChild.href="/rss/"+r.moduleId+"/";
        m.lastChild.href="/private/module/"+r.moduleId+"/";
    }
    async.post(fd)
}

function addAdmin(tid) {
    // build module node
    var form = buildNode("form", "lline", "")
    form.appendChild(buildNode("label", "", "Admin Email"))
    var email = buildInput("admin-email", "")
    form.lastChild.appendChild(email)
    var node = buildNode("div", "item")

    var d = new Dialog(details.name, function () { 
        // check for admin-email valid and add to tournament 
        var async = new asyncPost()
        var fd = new FormData()
        fd.append("case", "AddAdmin")
        fd.append("tournament_id", tid)
        fd.append("admin_email", email.value)
        async.onSuccess = function (r) { 
            if (r.adminEmail) {
                node.replaceChild(document.createTextNode(r.adminName+" ("+r.adminEmail+")"), node.firstChild)
            } else {
                node.appendChild(document.createTextNode(" FAILED TO ADD ADMIN"))
            }
        }
        async.post(fd)
        node.appendChild(document.createTextNode(email.value))
        document.getElementById("admins-list").appendChild(node)
    } )
    d.insert(form);
    d.show()
}

function tryUpdateDetails(details) {
    var async = new asyncPost()
    var fd = new FormData()
    fd.append("case", "UpdateTournament")
    fd.append("tournament_id", details.tid)
    fd.append("tournament_name", details.Name.input.value)
    fd.append("tournament_text", details.Text.input.value)
    fd.append("tournament_date", details["Date"].input.value)
    fd.append("tournament_slug", details.Slug.input.value)
    fd.append("tournament_privacy", details.Privacy.index)
    async.onSuccess = function(r) { 
        document.getElementById("title").innerHTML=details.Name.input.value
        details.Name.original.innerHTML = details.Name.input.value
        details.Text.original.innerHTML = details.Text.input.value
        details["Date"].original.innerHTML = details["Date"].input.value
        details.Privacy.original.innerHTML = details.Privacy.state
        details.Privacy.original.parentNode.setAttribute("data-priv", details.Privacy.index)
        if (r.unique)
            details.Slug.original.innerHTML = details.Slug.input.value
        else
            alert("Slug in use by another tournament")
        var btn = document.getElementById("edit-details")
        btn.className = "button"
        btn.onclick = editDetails
    }
    async.post(fd)
}

function editDetails() {
    var Detail = function(node, title) {
        this.original = node
        this.value = node.innerHTML
        this.node = buildNode("label","",title)
        this.input = document.createElement("input")
        this.input.value = this.value
        this.node.appendChild(this.input)
    }

    var node = document.getElementById("details")
    var form = buildNode("div", "dialog-form", "")
    var details = {}
    details["Date"] = new Detail(node.children[1], "Date")
    details.Name = new Detail(node.children[2], "Name")
    details.Text = new Detail(node.children[3], "Text")
    details.Slug = new Detail(node.children[4].lastChild, "Slug")
    for (i in details)
        form.appendChild(details[i].node)

    details.tid = node.getAttribute("data-tid")
    togBtn = buildNode("a","button","")
    details.Privacy = new ToggleText(togBtn, ["Private", "Anyone-with-link", "Public"])
    details.Privacy.original = node.children[5].lastChild
    details.Privacy.setIndex(parseInt(node.children[5].getAttribute("data-priv")))
    form.appendChild(buildNode("label","","Privacy"))
    form.lastChild.appendChild(buildNode("div", "lline"))
    form.lastChild.lastChild.appendChild(togBtn)

    if (node.getAttribute("data-owner")) {
        var delBtn = buildNode("a","button","Delete")
        form.appendChild(buildNode("label","","Delete Tournament?"))
        form.lastChild.appendChild(buildNode("div", "lline"))
        form.lastChild.lastChild.appendChild(delBtn)
        delBtn.onclick = function() { 
            var fd = { "case" : "DelTournament", "tournament_id" : node.getAttribute("data-tid")}
            var delDialog = new Dialog("Delete this tournament?", function () { buildSyncForm(fd).submit() })
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

function removeAdminDialog() {
    var dialogToggle = function(oldNode) {
        var self = this
        var adminText = oldNode.innerHTML
        var re = /\((.*?)\)$/
        var matches = adminText.match(re)
        if (!matches) return false;
        this.original = oldNode
        this.node = buildNode("div", "game dark-box viable", adminText)
        this.email = matches[1]
        this.selected = 0
        this.toggle = function() {
            if (self.selected) {
                self.selected = 0;
                self.node.className = "game dark-box viable";
            } else {
                self.selected = 1;
                self.node.className = "game selected viable";
            }
        }
        this.node.onclick = this.toggle
    }
    var adminList = []
    var admins = document.getElementById("admins-list").children
    var listBox = buildNode("div", "compact games-list")
    for (var i=1; i<admins.length; i++) { // is excluding children[0] good enough? is that always tournament owner?
        var a = new dialogToggle(admins[i])
        if (a.email) {
            adminList.push(a)
            listBox.appendChild(a.node)
        }
    }
    var d = new Dialog("Remove Admins", function () {
        var newTeams = []
        for (var i=0; i<adminList.length; i++)
            if (adminList[i].selected) {
                var async = new asyncPost()
                var fd = new FormData()
                fd.append("case", "RemoveAdmin")
                fd.append("tournament_id", document.getElementById("details").getAttribute("data-tid"))
                fd.append("admin_email", adminList[i].email)
                async.post(fd)
                adminList[i].original.parentNode.removeChild(adminList[i].original)
            }
    })
    d.insert(listBox)
    d.show()
}

function tournamentOnLoad() {
    var tourney = document.getElementById("details")
    var tid = tourney.getAttribute("data-tid")
    if (tourney.getAttribute("data-owner")) {
        document.getElementById("add-admin").onclick = function() { addAdmin(tid) }
        document.getElementById("remove-admin").onclick = removeAdminDialog
    } else {
        document.getElementById("add-admin").className = "button disabled"
        document.getElementById("delete-tournament").className = "button disabled"
        document.getElementById("remove-admin").onclick = function () {
        var fd = { "case" : "DelSelf", "tournament_id" : tid }
        var d = new Dialog("Remove yourself from the Admin list?", function () { buildSyncForm(fd).submit() })
        d.show()
        }
    }

    document.getElementById("add-team").onclick = function () { addTeam(tid, "Team Name", "Details", 0) }
    document.getElementById("add-teams").onclick = function () { bulkAddTeams(tid) }
    document.getElementById("edit-details").onclick = function () { editDetails(tid) }
    document.getElementById("add-module").onclick = function () { addModule(tid) }
    
    teams = document.getElementById("teams-list").children
    for(var i=0; i<teams.length; i++) {
        teams[i].className = "team viable"
        teams[i].onclick = function () { editTeam(this) }
    }
}
