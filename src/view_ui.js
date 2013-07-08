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

// View UI

function teamDialog() {
    var team = JSON.parse(this.getAttribute("data-team"))

    var d = new Dialog(team.name, function () { })
    d.insert(buildNode("div", "dark-head", team.text))

    var gameDetails = buildNode("div","num-details");
    gameDetails.appendChild(buildNode("div", "item"))
    gameDetails.lastChild.appendChild(buildNode("div","label", "Rank"))
    gameDetails.lastChild.appendChild(buildNode("div","", this.firstChild.innerHTML))
    gameDetails.appendChild(buildNode("div", "item"))
    gameDetails.lastChild.appendChild(buildNode("div","label", "Strength"))
    gameDetails.lastChild.appendChild(buildNode("div","", team.maxprob.toFixed(3)))
    gameDetails.appendChild(buildNode("div", "item"))
    gameDetails.lastChild.appendChild(buildNode("div","label", "Record"))
    gameDetails.lastChild.appendChild(buildNode("div","", this.lastChild.innerHTML))
    d.insert(gameDetails)

    var gameCtr = buildNode("div","list-box")
    for(var i=0; i<team.results.length; i++) {
        var res = team.results[i]
        var game = buildNode("div", "result", res.score[0]+"-"+res.score[1]+" vs "+team.results[i].opp_name)
        gameCtr.appendChild(game);
    }

    d.insert(gameCtr)
    d.insert(buildNode("div","clear"))
    //d.dialog.style.position = "absolute"
    d.show()
}

function viewOnLoad() {
    var teams = document.querySelectorAll(".rank-team")
    for (var i=0; i<teams.length; i++)
        teams[i].onclick = teamDialog
}
