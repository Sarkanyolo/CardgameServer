<?php
require_once('db.php');

if(isset($_GET['glist'])) glist($_GET['glist']);
if(isset($_GET['sname'])) sname($_GET['sname']);
if(isset($_GET['sduel'])) sduel($_GET['sduel'], $_GET['op']);
if(isset($_GET['dname'])) dname($_GET['dname']);
if(isset($_GET['dduel'])) dduel($_GET['dduel']);

function glist($param){
    $s = '';
    if($param === 'duel'){
        $res = db::send("select nick from users where duel='-' order by nick");
    } else {
        $res = db::send('select nick from users order by nick');
    }
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
            td{border:1px solid #aaa;padding:6px}
            tr{background-color:#fff}
            table tr:hover{background-color:#ddf;cursor:pointer}
            .respOK{color:green;font-style:italic}
            .respNOK{color:red;font-style:italic}
        </style>
    </head>
    <body>
        <center>
            <h3>Available commands:</h3>
            <table>
                <tr>
                    <td>
                        glist=<i>param</i></a>
                        <br><br>Possible params:<br>
                        <i>all</i> - List everyone<br>
                        <i>duel</i> - List players that asked for duel with somebody<br>
                        <i>play</i> - List of already playing ones
                    </td>
                    <td><span class='respOK'>user1#user2#user3#user4</span></td>
                </tr>
                <tr>
                    <td>gstatus=<i>myname</i></td>
                    <td>See table below...</td>
                </tr>
                <tr>
                    <td>sname=<i>username</i></td>
                    <td>
                        Sets the <i>username</i> (English, lowercase)<br><br>
                        Returns <span class='respNOK'>NOK</span> if reserved.
                    </td>
                </tr>
                <tr>
                    <td>sduel=<i>myname</i>&op=<i>username</i></td>
                    <td>
                        Ask for opponent, if he wants to duel<br><br>
                        <span class='respOK'>OK</span> - Invite sent<br>
                        <span class='respNOK'>DISC</span> - Opponent logged out<br>
                        <span class='respNOK'>PLAY</span> - Already playing<br>
                        <span class='respNOK'>DUEL</span> - Already asked for duel with somebody
                    </td>
                </tr>
                <tr>
                    <td>dname=<i>username</i></td>
                    <td>Deletes the <i>username</i><br>Returns <span class='respOK'>OK</span></td>
                </tr>
                <tr>
                    <td>dduel=<i>myname</i></td>
                    <td>Not waiting for the opponent any more<br>Returns <span class='respOK'>OK</span></td></tr>
            </table><br>
            <h4>Possible responses of the <i>gstatus</i> command:</h4>
            <table>
                <tr>
                    <td>When there is no new info to the player</td>
                    <td><span class='respOK'>OK</span></td>
                </tr>
                <tr>
                    <td>When waiting for duel response</td>
                    <td><span class='respOK'>DuelOK</span><br><span class='respNOK'>DuelNOK</span></td>
                </tr>
                <tr>
                    <td>When somebody(ies) asked you for duel</td>
                    <td><span class='respOK'>Duel#user1#user2#user3</span></td>
                </tr>
            </table>
        </center>
</body>
</html>