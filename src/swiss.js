
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

