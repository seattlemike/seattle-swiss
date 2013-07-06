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

    node.appendChild(buildNode("div", "info", team.name))
    node.appendChild(buildNode("div", "dark info", team.details))
    return node
}

function processBulk(text) {
    alert("this is not yet working");
}

function bulkAddTeams() {
    var teamInput = buildNode("textarea","medium","team name / details / uid, one team per line");
    teamInput.onclick = clearOnce
    var d = new Dialog("Add many teams",
                    function () { processBulk(teamInput.value) })
    d.insert(teamInput)
    d.show()
}

function addTeam(tid, name, details, uid) {
    // build team node
    var t = buildNode("div", "team", "")
    var list = document.getElementById("teams-list")
    t.appendChild(buildNode("div", "info", name))
    t.appendChild(buildNode("div", "dark info", details))
    t.setAttribute("data-name", name)
    t.setAttribute("data-text", details)
    t.setAttribute("data-uid", uid)
    list.appendChild(t)

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

function addRound() {
    
}

function addAdmin() {

}

function editDetails() {

}

function delTournament() {
    var d = new Dialog("Delete this tournament?",
                    function () { var form = document.getElementById("delForm"); if (form) form.submit() })
    d.show()
}

function tournamentOnLoad() {
    var tourney = document.getElementById("details")
    var tid = tourney.getAttribute("data-tid")
    var adminBtn = document.getElementById("add-admin")
    if (adminBtn)
        adminBtn.onclick = addAdmin

    document.getElementById("add-team").onclick = function () { addTeam(tid, "Team Name", "Details", 0) }
    document.getElementById("add-teams").onclick = function () { bulkAddTeams(tid) }
    document.getElementById("edit-details").onclick = function () { editDetails(tid) }
    document.getElementById("add-round").onclick = function () { addRound(tid) }
    
    teams = document.getElementById("teams-list").children
    for(var i=0; i<teams.length; i++) {
        teams[i].className = "team viable"
        teams[i].onclick = function () { editTeam(this) }
    }
}

