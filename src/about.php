<?php
  $title_text="Seattle Swiss";
  include("header.php");
  ?>

<div class='con'>
    <div class='centerBox textbox'>
        <div class='mainBox'> 
            <div class="header">Well, About That</div>
            <p> You're catching us at a pretty early stage, so fair warning:
            things may not work, things may break, things may change, and
            things may just plain not do what you want.
            But you have recourse!  If you want to see something improved /
            fixed / changed / given more options, please let us know.  Email mike or paul [see below].
            </p>
        </div>
        <div class='mainBox'> 
            <div class="header">About Tournaments</div>
            <p>Seattle Swiss is Tournament Software, meant as a flexible, robust system to 
            coordinate rounds in which teams are paired up for matches.  It's simple and it's free,
            but it's still solidly in that in-development phase.  We do <a
            href="http://en.wikipedia.org/wiki/Swiss-system_tournament">Swiss-style</a> pairing
            right now, and are in the process of implementing a system for <a
            href="http://en.wikipedia.org/wiki/Single_elimination">single</a> and <a
            href="http://en.wikipedia.org/wiki/Double_elimination">double elimination</a> rounds.
            </p>
            <p>We've been working with the following goals in mind:
            <ol><li>A simple, free interface for tournament administration</li>
                <li>An easy way for the public to see results online</li>
                <li>Good tie-breaking <a href="http://en.wikipedia.org/wiki/Tie-breaking_in_Swiss_system_tournaments">mechanisms</a></li>
            </ol>
            </p>
        </div>
        <div class='mainBox'> 
            <div class="header">About Tie-breaking</div>
            <p> Here's a quick summary of the tie breaking as it's currently set up: </p>
            <ol class="spaced"> 
                <li><i>Score</i> (<i>i.e. Wins and Losses</i>)<br>This isn't really a
                tie-break; it's the way a team's rank is computed.  But let's imagine that everyone
                is tied to start out with, and this is the first 'tie-break' that we use to sort it
                all out.  One point for a win, a half point for a draw, nothing for a loss.</li>
                <li><i>Buchholz</i><br>The primary tie-break, a team's Buchholz Score is calculated
                by adding the Win/Loss Score (see above) of each opponent that the team faced.</li>

                <li><i>Berger</i><br>Our secondary tie-break, this one is similar to the Buchholz,
                but adds only the scores of those opponents whom a team defeated (and half the
                scores of those with whom the team tied). Instead of measuring the difficulty of all
                teams faced, it measures the difficulty of the teams defeated.</li>

                <li><i>Cumulative</i><br>Our tertiary tie-break, the Cumulative Score, is designed
                to favor teams who win games earlier rather than later.  This addresses the problem 
                that teams who lose early and win late have had (generally) easier games than teams
                who won early and lost late.  The tie-break is computed by, after each round, adding
                the current Win/Loss Score to the tie-break value.  So, for example, a team whose
                record looks like Win, Tie, Loss, Win has a Cumulative Score of 1, 2.5 (1+1.5), 4
                (1+1.5+1.5), and 6.5 (1+1.5+1.5+2.5) after each of the first four rounds.</li>
            </ol>
            <p>The intention is to add several more tie-breaking schemes, and some flexibility to
            select which is the primary mechanism, secondary, etc.  Just not quite yet.</p>
        </div>
        <div class='mainBox'> 
            <div class="header">About Us</div>
            Several Seattle bike polo folks have been working on this project.  Send praise to mike, criticisms to paul:<br>
            mike/paul&nbsp;at&nbsp;seattleswiss&nbsp;dot&nbsp;com.
        </div>
    </div>
</div>

<?php include("footer.php"); ?>
