<?php
require_once('db.php');

if(isset($_GET['glist'])) glist();
if(isset($_GET['sname'])) sname($_GET['sname']);
if(isset($_GET['sduel'])) sduel($_GET['sduel'], $_GET['op']);
if(isset($_GET['dname'])) dname($_GET['dname']);
if(isset($_GET['dduel'])) ddual($_GET['dduel']);

function glist(){
    $s = '';
    $res = db::send('select nick from users order by nick');
    while ($v = db::fetch($res)) {
        $s .= '#' . $v['nick'];
    }
    myexit(substr($s,1));
}

function sduel($me, $op){
    $v = db::quick_fetch("select count(*) as pcs from users where nick='$op'");
    if($v['pcs']==='0'){
        myexit('DISC');
    }
    $v = db::quick_fetch("select duel from users where nick='$op'");
    if($v['duel']!=='-'){
        myexit('DUEL');
    }
    db::send("update users set duel='$op' where nick='$me'");
    myexit('OK');
}

function sname($name){
    $v = db::quick_fetch("select count(*) as pcs from users where nick='$name'");
    if ($v['pcs'] !== '0') {
        myexit('NOK');
    }
    db::send("insert into users (nick) VALUES ('$name')");
    myexit('OK');
}

function dname($name){
    db::send("delete from users where nick='$name'");
    myexit('OK');
}

function dduel($name){
    db::send("update users set duel='-' where nick='$name'");
    myexit('OK');
}

function myexit($str){
    db::disconnect();
    exit($str);
}

db::disconnect();
?><!DOCTYPE html>
<html lang="hu">
    <head>
        <meta charset="UTF-8">
        <title>CardGame Server</title>
        <style>
            body{margin:0;margin-top:5px;background-color:#fafafa}
            table{border-collapse:collapse}
            td{border:1px solid #aaa;padding:6px;text-align:center}
            tr{background-color:#fff}
            table tr:hover{background-color:#ddf;cursor:pointer}
        </style>
    </head>
    <body>
        <center>
            <h3>Available commands:</h3>
            <table>
                <tr><td><a href='s.php?glist=all'>glist=all</a></td><td>user1#user2#user3#user4#</td></tr>
                <tr><td>gstatus=<i>myname</i></td><td>See table below...</td></tr>
                <tr><td>sname=<i>username</i></td><td>Sets the <i>username</i>; Returns NOK if reserved.</td></tr>
                <tr><td>sduel=<i>myname</i>&op=<i>username</i></td><td>Ask for opponent, if he wants to duel<br><br>Returns:<br>OK, if invite sent<br>DISC, if opponent logged out<br>PLAY if already playing<br>DUEL if already asked for somebody</td></tr>
                <tr><td>dname=<i>username</i></td><td>Deletes the <i>username</i>; Returns OK</td></tr>
                <tr><td>dduel=<i>myname</i></td><td>Not waiting for the opponent any more</td></tr>
            </table><br>
            <h4>Possible responses of the gstatus command:</h4>
            <table>
                <tr><td>When there is no new info to the player</td><td>OK</td></tr>
                <tr><td>When waiting for duel response</td><td>DuelOK, DuelNOK</td></tr>
            </table>
        </center>
</body>
</html>