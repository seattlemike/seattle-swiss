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

function postToggles(form) {
    var toggles = [];
    var gamebox = document.getElementById("games");
    var games = gamebox.getElementsByTagName("form");
    for (var j = 0; j < games.length; j++) {
        if ((games[j] != form) && games[j].elements.hasOwnProperty("toggle") && games[j].elements.toggle.value) {
            toggles.push(games[j].elements.game_id.value);
        }
    }

    var toggleField = document.createElement("input");
    toggleField.setAttribute("type", "hidden");
    toggleField.setAttribute("name", "toggles");
    toggleField.setAttribute("value", toggles.join(" "));
    form.appendChild(toggleField);
}

function togOnCourt(gid) {
    var box = document.getElementById("box_"+gid);
    var form = document.getElementById("form_"+gid);
    if (form.elements.toggle.value) {
        form.elements.tog_btn.value = "On Court";
        form.elements.toggle.value = "";
        box.className = box.className.replace(/\bplaying\b/,"");
    }
    else {
        form.elements.tog_btn.value = "Off Court";
        form.elements.toggle.value = "1";
        if (! box.className.match(/\bplaying\b/) )
            box.className += " playing";
    }
    return true;
}

function toggleHide(id) {
    var el = document.getElementById(id)
    if (el.style.display == "none")
        el.style.display = ""
    else el.style.display = "none"
}

function runModuleOnLoad() {
    //document.getElementById("fill-round").onclick = fillRound;
    //document.getElementById("empty-round").onclick = fillRound;
    //document.getElementById("delete-round").onclick = fillRound;

    var games = document.querySelectorAll(".game")
    for (var i=0; i<games.length; i++) {
        var scores = JSON.parse(games[i].getAttribute("data-score"))
        var game = JSON.parse(games[i].getAttribute("data-game"))
        if (game.status == 1)
            games[i].className += " played"
        var dialog = new GameDialog(game, scores)
        games[i].onclick = dialog.show
    }
}

function GameDialog(game, scores) {
    var self=this
    this.game = game
    this.scores = scores

    this.toggleStatus = function () {
        self.game.status = (self.game.status + 1) % 3
        self.statusNode.lastChild.innerHTML = self.statusTexts[game.status]
    }
    this.dialog = new Dialog(scores[0]['team_name']+" vs "+scores[1]['team_name'], function () { })
    this.statusTexts = ["Scheduled", "Finished", "Now Playing"]
    this.statusNode = buildNode("div", "line")
    this.statusNode.appendChild(buildNode("div", "label", "Status"))
    this.statusNode.appendChild(buildNode("div", "status", this.statusTexts[game.status]))
    this.statusNode.onclick = this.toggleStatus
    this.dialog.insert(this.statusNode)

    this.show = function () { self.dialog.show() }
}
