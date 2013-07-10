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
    var teamList = buildNode("form", "lline", "")
    var name = buildNode("label", "", "Name")
    name.appendChild( buildInput("team-name", team.name) )
    var details = buildNode("label", "", "Details")
    details.appendChild( buildInput("team-details", team.details) )
    var uid = buildNode("label", "", "UID")
    uid.appendChild( buildInput("team-uid", team.uid) )
    uid.lastChild.className = "numeric"
    //TODO: make this look better
    var delBtn = buildNode("a", "button", "Delete");
    delBtn.onclick = function () {
        var del = new Dialog("Are you sure you want to delete "+team.name+"?", function () { tryDeleteTeam(node); d.hide() } )
         del.show()
    }
    teamList.appendChild(name)
    teamList.appendChild(details)
    teamList.appendChild(uid)
    teamList.appendChild(delBtn)

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
    d.insert(teamList)
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
    document.getElementById("modules-list").appendChild(m)

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
                node.removeChild(node.firstChild)
                node.appendChild(document.createTextNode(r.adminName+" ("+r.adminEmail+")"))
                node.setAttribute("admin-id", r.adminId)
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
    fd.append("case", "UpdateDetails")
    fd.append("tournament_id", details.tid)
    fd.append("tournament_name", details.name)
    fd.append("tournament_text", details.text)
    fd.append("tournament_date", details.date)
    fd.append("tournament_display", details.display)
    async.onSuccess = function(r) {
        // TODO: replace all instances?
    }
    async.post(fd)
    // TODO: replace immediate instances
}

function editDetails() {
    var node = document.getElementById("details")
    var details = {}
    details.date = node.children[1].innerHTML
    details.name = node.children[2].innerHTML
    details.text = node.children[3].innerHTML
    details.display = node.children[4].lastChild.innerHTML
    details.tid = node.getAttribute("data-tid")

    var form = buildNode("form", "lline", "")
    var date = buildNode("label", "", "Date")
    date.appendChild( buildInput("tourney-date", details.date) )
    var name = buildNode("label", "", "Name")
    name.appendChild( buildInput("tourney-name", details.name) )
    var text = buildNode("label", "", "Description")
    text.appendChild( buildInput("tourney-text", details.text) )
    // TODO: make display a three-way toggle, and figure out how link-only is
    //       going to work
    var display = buildNode("label", "", "Display")
    display.appendChild( buildInput("team-display", details.display) )
    form.appendChild(date)
    form.appendChild(name)
    form.appendChild(text)
    form.appendChild(display)

    var d = new Dialog(details.name, 
                       function () { 
                            details.date = date.value
                            details.name = name.value
                            details.text = text.value
                            details.display = display.value
                            var btn = document.getElementById("edit-details")
                            btn.onclick = ""
                            btn.className = "button disabled"
                            tryUpdateDetails(details)
                        } )
    d.insert(form)
    d.show()

}

function removeAdminDialog() {
    //TODO

}


function tournamentOnLoad() {
    var tourney = document.getElementById("details")
    var tid = tourney.getAttribute("data-tid")
    if (tourney.getAttribute("data-owner")) {
        document.getElementById("delete-tournament").onclick = function() { 
            // recursive delete with delform?
            var fd = { "case" : "DelTournament", "tournament_id" : tid }
            var d = new Dialog("Delete this tournament?", function () { buildSyncForm(fd).submit() })
            d.show()
        }
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

