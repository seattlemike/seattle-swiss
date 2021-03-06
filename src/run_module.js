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

// Classes

function Module() {
    // should replace gameslist

    // format
    // rounds > games
    // teams
    // tid
    // ?
    // status?
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
    this.postUpdate = function () {
        var gameData = { "status":self.status, "game_id":data.game_id }
        var async = new asyncPost()
        async.onSuccess = function () { node.onclick = self.dialog.show; list.updateNextBtn() }
        async.post( { 'case':'UpdateGame', 'score_data':JSON.stringify(scores), 'game_data':JSON.stringify(gameData) } )
    }
    this.deleteGame = function () {
        var async = new asyncPost()
        async.onSuccess = function () { 
            var listNode = node.parentNode
            listNode.removeChild(node)
            if (listNode.children.length == 0)
                removeNode(listNode.parentNode)
            list.updateNextBtn() 
        }
        async.post({'case':'DeleteGame', 'game_id':data.game_id})
        node.onclick = ""
    }

    this.node = node
    this.scores = scores
    this.setStatus(data.status)
    this.round_id = data.round_id

    if (scores.length == 2) {
        this.dialog = new GameDialog(this)
        node.onclick = this.dialog.show
    }
}

function GamesList() {
    var self = this;
    this.module = JSON.parse(document.getElementById("module").getAttribute("data-module"));
    this.games = []

    this.parseRoundNode = function(round) {
        var games = round.lastChild.children
        for(var j=0; j<games.length; j++) {
            var scores = JSON.parse(games[j].getAttribute("data-score"))
            var data = JSON.parse(games[j].getAttribute("data-game"))
            this.games.push(new Game(games[j], scores, data))
        }
    }

    // build a new game from async return data
    this.newGame = function (data, roundNode) {
        var gameNode = buildNode("div", "game game-inprogress")
        var rid = data.game_data.round_id
        gameNode.setAttribute("data-score", JSON.stringify(data.score_data))
        gameNode.setAttribute("data-game", JSON.stringify(data.game_data))
        var game = new Game(gameNode, data.score_data, data.game_data)
        self.games.push(game);
        roundNode.lastChild.appendChild(gameNode)
    }

    this.insertGame = function (data) {
        if (data.game_data.round_id != self.latest.getAttribute("data-rid"))
            throw new Error("Problem adding game from round "+rid+" to latest round.")
        self.newGame(data, self.latest)
        self.updateNextBtn()
    }

    // build a new round from async return data
    this.buildRound = function (round) {
        // round: title, rid, games
        var moduleNode = document.getElementById("module")
        var node = buildNode("div", "round")
        node.appendChild(buildNode("div", "header", round.title))
        node.appendChild(buildNode("div", "games-list"))
        node.setAttribute("data-rid", round.rid)
        moduleNode.insertBefore(node, moduleNode.firstChild)
        for (var j=0; j<round.games.length; j++) {
            self.newGame(round.games[j], node)
        }
        return node
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
            nextBtn.innerHTML = "Next Round"

        if (self.nextReady()) {
            nextBtn.className = "button"
            nextBtn.onclick = function () { addRound(self.module.module_id) }
        } else {
            nextBtn.className = "button disabled"
            nextBtn.onclick = ""
        }
    }

    // delete game corresponding to game-node
    this.deleteGame = function (node) {
        for (var i=self.games.length-1; i>=0; i--) {
            if (self.games[i].node == node) {
                self.games[i].deleteGame() // delete game from our list (and from the games-list div)
                self.games.splice(i,1)
            }
        }
    }
}

// UI

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
        game.postUpdate()
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
    var box = buildNode("div", "list-box");
    line.appendChild(box);
    this.dialog.insert(line);
    for(var i=0; i<game.scores.length; i++) {
        var team = buildNode("div", "rank-team")
        team.appendChild(buildNode("div", "rank"))
        inputs[i] = buildNode("input", "dialog-score")
        inputs[i].value = game.scores[i]['score']
        inputs[i].onclick = function () { 
            this.select()
            if (tmpStatus == 0) { self.toggleStatus() }
        }
        team.firstChild.appendChild(inputs[i])
        team.appendChild(buildNode("div", "dialog-team", game.scores[i]['team_name']))
        box.appendChild(team)
    }
    this.statusNode = buildNode("div", "dialog-status")
    this.statusNode.onclick = this.toggleStatus
    this.dialog.insert(this.statusNode)
}

function deleteGamesDialog() {
    // the toggle-able game entry in the dialog
    var dialogGame = function(node, rid) {
        var self=this
        this.round = rid
        this.original = node
        var scores = JSON.parse(node.getAttribute("data-score"))
        this.node = buildNode("div", node.className)
        if (scores.length == 1)
            this.node.appendChild(document.createTextNode(scores[0].team_name+" [bye]"));
        else if (scores.length == 2)
            this.node = buildNode("div", node.className, scores[0].team_name+" vs "+scores[1].team_name);
        //this.node = node.cloneNode() // <-- problem with <span class='victor'>
        this.delTag = 0

        this.toggle = function() {
            if (self.delTag) {
                self.delTag=0
                self.node.className = self.node.className.replace(" greyed", "")
            } else {
                self.delTag=1;
                self.node.className += " greyed"
            }
        }
        this.node.onclick = this.toggle
    }
    var games = []

    // the dialog ui
    var gameList = buildNode("div", "compact")
    var rounds = document.querySelectorAll(".round")
    for(var i=0; i<rounds.length; i++) {
        var round = buildNode("div", "round", "")
        //round.appendChild(rounds[i].firstChild.cloneNode())
        round.appendChild(buildNode("div", "games-list"))
        var gameNodes = rounds[i].lastChild.children;
        for(var j=0; j<gameNodes.length; j++) {
            var game = new dialogGame(gameNodes[j], rounds[i].getAttribute("data-rid"))
            round.lastChild.appendChild(game.node)
            games.push(game)
        }
        gameList.appendChild(round)
    }
    var d = new Dialog("Delete Games", function () {
        for (var i=0; i<games.length; i++)
            if (games[i].delTag)
                list.deleteGame(games[i].original)
    })
    d.insert(gameList)
    d.dialog.style.position = "absolute"
    d.show()
}

function addGameDialog() {
    var dialogTeam = function(team) {
        var self = this
        this.node = buildNode("div", "game dark-box", team.team_name)
        this.id = team.team_id
        this.selected = 0
        this.toggle = function() {
            if (self.selected) {
                self.selected = 0;
                self.node.className = "game dark-box";
            } else {
                self.selected = 1;
                self.node.className = "game selected";
            }
        }
        this.node.onclick = this.toggle
    }
    var teamList = []
    var listBox = buildNode("div", "compact games-list")
    var teamData = JSON.parse(document.getElementById("module").getAttribute("data-teams"))
    var moduleData = JSON.parse(document.getElementById("module").getAttribute("data-module"))
    for (var i=0; i<teamData.length; i++) {
        var team = new dialogTeam(teamData[i])
        listBox.appendChild(team.node)
        teamList.push(team)
    }
    var d = new Dialog("Add Game", function () {
        var newTeams = []
        for (var i=0; i<teamList.length; i++)
            if (teamList[i].selected)
                newTeams.push({'id':teamList[i].id})
        if ((newTeams.length == 1) || (newTeams.length == 2)) {
            var async = new asyncPost()
            async.onSuccess = function (response) { list.insertGame(response) }
            async.post( {'case':'AddGame', 'module_id':moduleData.module_id, 'team_data':JSON.stringify(newTeams)} )
        }
    })
    d.insert(listBox)
    d.show()
}

// Actions

function addRound(module_id) {
    var node = this;
    this.onclick = ""
    this.className = "button disabled"

    var async = new asyncPost()
    async.onSuccess = function (response) { 
        for (var i=0; i<response.rounds.length; i++) {
            //console.log("Build Round "+response.rounds[i].title)
            //console.log("  Games: "+response.rounds[i].games)
            list.buildRound(response.rounds[i])
        }
        list.updateNextBtn()
    }
    async.post({'case':'AddRound', 'module_id':module_id})
}

function runModuleOnLoad() {
    list = new GamesList() // global games list (with game nodes)
    // build rounds-list and games-list from existing round/game nodes
    var rounds = document.querySelectorAll(".round")
    for(var i=0; i<rounds.length; i++)
        list.parseRoundNode(rounds[i])
    list.latest = rounds[0]

    list.updateNextBtn()
    document.getElementById("game-add").onclick = addGameDialog 
    document.getElementById("game-del").onclick = deleteGamesDialog

    // if (games.length)
    //document.getElementById("edit-toggle").onclick = function () { toggleHide('edit-controls') }
    //document.getElementById("edit-controls").style["display"] = "none"
}
