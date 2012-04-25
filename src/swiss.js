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
