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

function Game(node, scores, data) {
    var statusClass = ["game-scheduled", "game-finished", "game-inprogress"]
    var self = this

    this.setStatus = function (s) { 
        self.status = s
        node.className = "game "+self.getStatusClass(s)
        node.innerHTML = self.getGameText()
        //node.replaceChild(document.createTextNode(self.getGameText()), node.firstChild)
    }
    this.getGameText = function () {
        if (scores.length == 1)
            return scores[0].team_name+" has a BYE"

        var a=scores[0].team_name
        var b=scores[1].team_name
        if (self.status == 1) {
            if (scores[0].score > scores[1].score)
                a = "<span class='victor'>"+a+"</span>"
            if (scores[0].score < scores[1].score)
                b = "<span class='victor'>"+b+"</span>"
        }
        if (self.status > 0) 
            return a+" vs "+b+", "+scores[0].score + "-" + scores[1].score
        else 
            return a+" vs "+b
    }
    this.getStatusClass = function(s) {
        var statusClass = ["game-scheduled", "game-finished", "game-inprogress"]
        return statusClass[s];
    }
    this.getStatusText = function(s) {
        var statusText = ["Scheduled", "Finished", "Now Playing"]
        return statusText[s];
    }
    this.getFormData = function () {
        var fd = new FormData()
        fd.append("game_data", JSON.stringify( { "status" : self.status, "game_id": data.game_id } ))
        fd.append("score_data", JSON.stringify(scores))
        return fd
    }

    this.node = node
    this.scores = scores
    this.setStatus(data.status);

    var dialog = new GameDialog(this)
    node.onclick = dialog.show
}

function gamesList() {
    var self = this;
    this.games = []
    this.module = JSON.parse(document.getElementById("games").getAttribute("module-data"));

    var queryGames = document.querySelectorAll(".game");
    for(var i=0; i<queryGames.length; i++) {
        var node = queryGames[i]
        var scores = JSON.parse(node.getAttribute("data-score"))
        var data = JSON.parse(node.getAttribute("data-game"))
        this.games.push(new Game(node, scores, data))
    }

    this.insertGames = function (round, games) {
        var newRound = buildNode("div", "round");
        newRound.appendChild(buildNode("div", "header", "Round "+round.round_number));
        newRound.appendChild(buildNode("div", "games"));
        for(var i=0; i<games.length; i++) {
            var node = buildNode("div", "game game-inprogress")
            var game = new Game(node, games[i].score_data, games[i].game_data)
            self.games.push(game);
            newRound.lastChild.appendChild(node)
        }
        var node = document.getElementById("games")
        node.insertBefore(newRound, node.firstChild);
    }

    this.nextReady = function () {
        for(var i=0; i<self.games.length; i++)
            if (self.games[i].status != 1)
                return false
        return true
    }

    this.updateNextBtn = function () {
        var nextBtn = document.getElementById("next")
        if (self.games.length == 0)
            nextBtn.innerHTML = "Start"
        else
            nextBtn.innerHTML = "Next Button"

        if (self.nextReady()) {
            nextBtn.className = "button"
            nextBtn.onclick = function () { addRound(self.module.module_id) }
        } else {
            nextBtn.className = "button disabled"
            nextBtn.onclick = ""
        }
    }
}

function runModuleOnLoad() {
    //document.getElementById("fill-round").onclick = fillRound;
    //document.getElementById("empty-round").onclick = fillRound;
    //document.getElementById("delete-round").onclick = fillRound;

    list = new gamesList(); // global gamesList list
    list.updateNextBtn();
}

function addRound(module_id) {
    var node = this;
    this.onclick = ""
    this.className = "button disabled"

    var async = new asyncPost()
    async.onSuccess = function (response) { 
        list.insertGames(response.round, response.games)
        list.updateNextBtn()
    }
    var fd = new FormData()
    fd.append("case", "AddRound")
    fd.append("module_id", module_id);
    async.post(fd)
}

function GameDialog(game) {
    var self=this

    this.update = function (s) {
        tmpStatus = s
        if (! self.statusNode.hasChildNodes())
            self.statusNode.appendChild( document.createTextNode( game.getStatusText(s) ))
        else
            self.statusNode.replaceChild( document.createTextNode( game.getStatusText(s) ), self.statusNode.firstChild )
        self.statusNode.className = "dialog-status "+game.getStatusClass(s)
    }
    this.toggleStatus = function () { self.update((tmpStatus + 2) % 3) }

    this.saveScore = function() {
        for(var i=0; i<game.scores.length; i++) {
            var score = parseInt(inputs[i].value)
            if (! isNaN(score))
                game.scores[i]['score'] = score
            else
                game.scores[i]['score'] = 0
        }
        game.node.onclick = ""
        game.setStatus(tmpStatus)
        var async = new asyncPost()
        async.onSuccess = function () { game.node.onclick = self.show; list.updateNextBtn() }
        var fd = game.getFormData()
        fd.append("case", "GameUpdate")
        async.post(fd)
    }

    this.show = function () { 
        for(var i=0; i<game.scores.length; i++)
            inputs[i].value = game.scores[i]['score']
        self.update(game.status)
        self.dialog.show() 
    }

    var tmpStatus
    var inputs = []

    // Build the node structure
    this.dialog = new Dialog("", self.saveScore)
    this.dialog.insert(buildNode("div", "header", game.scores[0].team_name+" vs "+game.scores[1].team_name))
    var line = buildNode("div", "line")
    var box = buildNode("div", "centerCtr");
    line.appendChild(box);
    this.dialog.insert(line);
    for(var i=0; i<game.scores.length; i++) {
        var team = buildNode("div", "team")
        team.appendChild(buildNode("span", ""))
        inputs[i] = buildNode("input", "dialog-score")
        inputs[i].value = game.scores[i]['score']
        team.firstChild.appendChild(inputs[i])
        team.appendChild(buildNode("span", "dialog-team", game.scores[i]['team_name']))
        box.appendChild(team)
    }
    this.statusNode = buildNode("div", "dialog-status")
    this.statusNode.onclick = this.toggleStatus
    this.dialog.insert(this.statusNode)
}
