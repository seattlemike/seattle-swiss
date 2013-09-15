<?php
/*  Copyright 2011, 2012 Mike Bell and Paul Danos

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

    $header_text="Two Oh Swiss";
    $title_text="About";
    include("header.php");
    disp_header("About");
    disp_topbar();
    disp_titlebar("About 20Swiss");
?>

<div class='con'>
    <div class='centerBox'>
        <div class='mainBox'> 
            <div class="header">Note (Sep 2013)</div>
            <p>This is the old 'about' page, and some of this content is now out-of-date.  Working on that.</p>
        </div>
        <div class='mainBox'> 
            <div class="header">About Tournaments</div>
            <p>20Swiss ("Two Oh Swiss") is Tournament Software.  It handles <a
            href="http://en.wikipedia.org/wiki/Swiss-system_tournament">Swiss-style</a> pairing, <a
            href="http://en.wikipedia.org/wiki/Single_elimination">single</a> and <a
            href="http://en.wikipedia.org/wiki/Double_elimination">double elimination</a> brackets,
            and provides a means of importing teams with their seeding from one tournament to the
            next.</p>
            <p>If you're interested in running a tournament, create a free account <a
            href="http://beta.seattleswiss.com/login/">here</a> and give it a try! (though see
            below for a thorough warning against expecting things to be perfect)</p>
        </div>
        <div class='mainBox'>
            <div class="header">About The Software [<a href="https://github.com/seattlemike/seattle-swiss">src</a>]</div>
            <p>20Swiss is intended to be a flexible, robust system to coordinate a tournament via
            the mechanism of rounds in which teams are paired up for matches.  It's simple and it's
            free, but it's still solidly in that in-development phase.</p>
            <p>We've been working with the following goals in mind:
            <ol><li>A simple, free interface for tournament administration</li>
                <li>An easy way for the public to see results online</li>
                <li>Good tie-breaking <a href="http://en.wikipedia.org/wiki/Tie-breaking_in_Swiss_system_tournaments">mechanisms</a> to calculate ranking in a Swiss tournament</li>
            </ol>
            </p>
            <p>If you'd like to help us with this project, or if you'd like to set up your own
            server to run tournaments on, or if you want to extend the source for your own nefarious
            purposes - whatever your reason, the source is available for download <a
            href="https://github.com/seattlemike/seattle-swiss">here</a>.</p>
        </div>
        <div class='mainBox'> 
            <div class="header">About Tie-breaking</div>
            <p>Figuring out who is the best from among a group of teams is a difficult thing to do,
            and getting decent seeding for the entire group is even worse.  A swiss tournament is
            not terrible at seeding a group when it's used along with a good set of tie-breaks to
            calculate the teams' rank at a given point.  This in turn makes for good matchups.</p>

            <p>Here's how the tie-breaks work now:
                <span style='margin-left: 1em'>
                <br /><br />1. <i>Score</i> (<i>i.e. Wins and Losses</i>)<br/>This isn't really a
                tie-break, but it's the basis for a team's rank.  Let's imagine that everyone
                is tied to start out with, and this is the first 'tie-break' that we use to sort
                everyone into groups.  One point for a win, a half point for a draw, nothing for a
                loss.  Then within each group, we head to the next tie-break computation.

                <br /><br />2. <i>Buchholz</i><br/>The primary tie-break, a team's Buchholz Score is calculated
                by adding up the Win/Loss Score (see above) of each opponent that the team
                faced.

                <br /><br />3. <i>Berger</i><br/>Our secondary tie-break, this one is similar to the Buchholz,
                but uses only the scores of those opponents whom a team defeated (and one half the
                scores of those with whom the team tied).  Instead of measuring the difficulty of all
                teams faced, it measures the difficulty of the teams defeated.

                <br /><br />4. <i>Cumulative</i><br/>Our tertiary tie-break, the Cumulative Score, is designed
                to favor teams who win games earlier rather than later.  This addresses the problem 
                that teams who lose early and win late have had (generally) easier games than teams
                who won early and lost late.  The tie-break is computed by, after each round, adding
                the current Win/Loss Score to the tie-break value.  So, for example, a team whose
                record looks like Win, Tie, Loss, Win has a Cumulative Score of 1, 2.5 (1+1.5), 4
                (1+1.5+1.5), and 6.5 (1+1.5+1.5+2.5) after each of the first four rounds.
            </p>
            <p>The intention is to add several more tie-breaking schemes, and some flexibility to
            select which is the primary mechanism, secondary, etc.  Just not quite yet.</p>
        </div>
        <div class='mainBox'> 
            <div class="header">Well, About That</div>
            <p> You're catching us at a pretty early stage, so fair warning: things may not work,
            things that once worked may break, things will surely change, and things may just plain
            not do what you want.  But you have recourse!  If you want to see something improved /
            fixed / changed / given more options, please let us know: <i>email mike or paul</i>.
            </p>
            <p>Additionally, if you'd like a copy of the source to check out what's going on under
            the hood, to contribute (please!), or to modify and redistribute for some
            tournament-type-thing of your own, head on over to our <a href="https://github.com/seattlemike/seattle-swiss">github repository</a>.
        </div>
        <div class='mainBox'> 
            <div class="header">About Us</div>
            <p>Several <a href="http://aarongrant00.wix.com/seattlebikepolo">Seattle Bike Polo</a> folks have been
            working on this project.  Send praise to mike, criticisms to paul:<br/>
            mike/paul&nbsp;at&nbsp;seattleswiss&nbsp;dot&nbsp;com.</p>
        </div>
    </div>
</div>

<?php include("footer.php"); ?>
