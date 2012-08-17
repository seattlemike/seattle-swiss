
/*  
    Copyright 2011, 2012 Mike Bell and Paul Danos

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
    var form = document.getElementById("game_"+gid);
    if (form.elements.toggle.value) {
        form.elements.tog_btn.value = "On Court";
        form.className = "";
        form.elements.toggle.value = "";
    }
    else {
        form.elements.tog_btn.value = "Off Court";
        form.className = "playing";
        form.elements.toggle.value = "1";
    }
    return true;
}
