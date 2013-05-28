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

    var statusClass = ["game-scheduled", "game-finished", "game-inprogress"]
    var games = document.querySelectorAll(".game")

    for (var i=0; i<games.length; i++) {
        var scores = JSON.parse(games[i].getAttribute("data-score"))
        var game = JSON.parse(games[i].getAttribute("data-game"))
        game.node = games[i]
        var dialog = new GameDialog(game, scores)
        games[i].onclick = dialog.show
        games[i].className= "game "+statusClass[game.status]
    }
}

function GameDialog(game, scores) {
    var self=this

    var statusText = ["Scheduled", "Finished", "Now Playing"]
    var statusClass = ["game-scheduled", "game-finished", "game-inprogress"]
    var titleNode = buildNode("div", "header")
    var tmpStatus

    this.update = function (s) {
        tmpStatus = s
        self.statusNode.innerHTML = statusText[s]
        self.statusNode.className = "dialog-status "+statusClass[s]
        titleNode.innerHTML = scores[0].team_name+" vs "+scores[1].team_name
    }
    this.toggleStatus = function () { self.update((tmpStatus + 2) % 3) }

    this.saveScore = function() {
        game.status = tmpStatus
        for(var i=0; i<scores.length; i++) {
            var score = parseInt(scores[i].input.value)
            if (! isNaN(score))
                scores[i]['score'] = score;
            else
                scores[i]['score'] = 0;
        }
        
        game.node.className = "game "+statusClass[game.status]
        game.node.innerHTML = scores[0].team_name + " vs " + scores[1].team_name
        if (game.status > 0) { game.node.innerHTML += ", " + scores[0].score + "-" + scores[1].score }

        game.onclick = ""
        var async = new asyncPost()
        async.onSuccess = function () { game.node.onclick = self.show; game.node.className = "game "+statusClass[game.status] }
        var fd = new FormData()
        fd.append("case", "GameUpdate")
        fd.append("game_data", JSON.stringify( { "status" : game.status, "game_id": game.game_id } ))
        fd.append("score_data", JSON.stringify(scores))
        async.post(fd)

    }

    this.show = function () { 
        for(var i=0; i<scores.length; i++)
            scores[i].input.value = scores[i]['score']
        self.update(game.status);
        self.dialog.show() 
    }

    // Build the node structure
    this.dialog = new Dialog("", self.saveScore)
    this.dialog.insert(titleNode)
    this.box = buildNode("div", "")
    for(var i=0; i<scores.length; i++) {
        var line = buildNode("div", "dialog-line")
        line.appendChild(buildNode("span", "dialog-team", scores[i]['team_name']))
        scores[i].input = buildNode("input", "dialog-score")
        scores[i].input.value = scores[i]['score']
        line.appendChild(scores[i].input)
        this.box.appendChild(line);
    }
    this.statusNode = buildNode("div", "dialog-status")
    this.statusNode.onclick = this.toggleStatus
    this.dialog.insert(this.box)
    this.dialog.insert(this.statusNode)
}
