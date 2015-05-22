<?php
require_once('db.php');

function myexit($str){
    db::disconnect();
    exit($str);
}

function getnick($id){
    $v = db::quick_fetch("select nick from users where id='$id'");
    if(empty($v)) return NULL;
    return $v['nick'];
}

function getid($nick){
    $v = db::quick_fetch("select id from users where nick='$nick'");
    if(empty($v)) return NULL;
    return $v['id'];
}

function cleanold(){
    db::send('delete from users where lastaction < ('. time() .'-180)');
    db::send('delete from messages where userid not in (select id from users)');
}

if(isset($_GET['gstatus'])) gstatus($_GET['gstatus']);
if(isset($_GET['glist'])) glist($_GET['glist']);
if(isset($_GET['aduel'])) aduel($_GET['aduel'], $_GET['me'], $_GET['op']);
if(isset($_GET['sname'])) sname($_GET['sname']);
if(isset($_GET['sduel'])) sduel($_GET['sduel'], $_GET['op']);
if(isset($_GET['dname'])) dname($_GET['dname']);
if(isset($_GET['dduel'])) dduel($_GET['dduel']);

function gstatus($id){
    $nick = getnick($id);
    if(empty($nick)){myexit('DISC');}
    db::send('update users set lastaction='.time()." where id='$id'");
    $str = '';
    
    // Check if Duel
    $res = db::send("select nick from users where duel='$nick'");
    while($v = db::fetch($res)){
        $str .= '#'.$v['nick'];
    }
    if($str !== ''){myexit('Duel'.$str);}
    
    // Check if messages
    $res = db::send("select * from messages where userid='$id' order by id");
    while($v = db::fetch($res)){
        db::send('delete from messages where id='. $v['id']);
        myexit($v['message']);
    }
            
    myexit('OK');
}

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

function aduel($answer, $id, $op){
    if($answer==='NO'){
        db::send("update users set duel='-' where nick='$op'");
        db::send("insert into messages (userid, message) select id, 'DuelNOK' from users where nick='$op'");
        myexit('OK');
    } elseif($answer==='YES') {
        $v = db::quick_fetch("select duel from users where nick='$op'");
        if(empty($v)) myexit('NOK');
        $nick = getnick($id);
        db::send("insert into messages (userid, message) select id, 'Start#$nick' from users where nick='$op'");
        db::send("insert into messages (userid, message) VALUES('$id', 'Start#$op')");
		db::send("update users set duel='-' where nick='$op'");
        myexit('OK');
    }
    myexit('NOK');
}

function sduel($id, $op){
    cleanold();
    $v = db::quick_fetch("select count(*) as pcs from users where nick='$op'");
    if($v['pcs']==='0'){
        myexit('DISC');
    }
    $v = db::quick_fetch("select duel from users where nick='$op'");
    if($v['duel']!=='-'){
        myexit('DUEL');
    }
    db::send("update users set duel='$op' where id='$id'");
    myexit('OK');
}

function sname($name){
    cleanold();
    $v = db::quick_fetch("select count(*) as pcs from users where nick='$name'");
    if ($v['pcs'] !== '0') {
        myexit('NOK');
    }
    $str = substr("abcdefghijklmnopqrstuvwxyz",mt_rand(0,25),1).substr(md5(time()),1);
    db::send("insert into users (id, nick, lastaction) VALUES ('$str', '$name', ".time().")");
    myexit($str);
}

function dname($id){
    cleanold();
    db::send("delete from users where id='$id'");
    db:send("delete from messages where userid='$id'");
    $nick = getnick($id);
    db::send("update users set duel='-' where duel='$nick'");
    myexit('OK');
}

function dduel($id){
    db::send("update users set duel='-' where id='$id'");
    myexit('OK');
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
                        aduel=<span class='respOK'>YES</span>&me=<i>myid</i>&op=<i>username</i>
                        <br>
                        aduel=<span class='respOK'>NO</span>&me=<i>myid</i>&op=<i>username</i>
                    </td>
                    <td>
                        Accept/Refuse the duel<br><br>
                        <span class='respOK'>OK</span> - if everything OK<br>
                        <span class='respNOK'>NOK</span> - if challenger cancelled / disconnected
                    </td>
                </tr>
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
                    <td>gstatus=<i>myid</i></td>
                    <td>See table below...</td>
                </tr>
                <tr>
                    <td>sname=<i>username</i></td>
                    <td>
                        Log in <i>username</i> (English, lowercase)<br><br>
                        Returns <span class='respNOK'>NOK</span> if reserved.<br>
                        Returns a string (length:32), with <span class='respOK'>unique id</span> if OK
                    </td>
                </tr>
                <tr>
                    <td>sduel=<i>myid</i>&op=<i>username</i></td>
                    <td>
                        Ask for opponent, if he wants to duel<br><br>
                        <span class='respOK'>OK</span> - Invite sent<br>
                        <span class='respNOK'>DISC</span> - Opponent logged out<br>
                        <span class='respNOK'>PLAY</span> - Already playing<br>
                        <span class='respNOK'>DUEL</span> - Already asked for duel with somebody
                    </td>
                </tr>
                <tr>
                    <td>dname=<i>myid</i></td>
                    <td>Logout<br><br>Returns <span class='respOK'>OK</span></td>
                </tr>
                <tr>
                    <td>dduel=<i>myid</i></td>
                    <td>Not waiting for the opponent any more<br><br>Returns <span class='respOK'>OK</span></td>
                </tr>
            </table><br>
            <h4>Possible responses of the <i>gstatus</i> command:</h4>
            <table>
                <tr>
                    <td>When there is no new info to the player</td>
                    <td><span class='respOK'>OK</span></td>
                </tr>
                <tr>
                    <td>When id not found; relogin is needed</td>
                    <td><span class='respNOK'>DISC</span></td>
                </tr>
                <tr>
                    <td>When waiting for duel response</td>
                    <td><span class='respOK'>DuelOK</span><br><span class='respNOK'>DuelNOK</span></td>
                </tr>
                <tr>
                    <td>When somebody(ies) asked you for duel</td>
                    <td><span class='respOK'>Duel#user1#user2#user3</span></td>
                </tr>
                <tr>
                    <td>When the game starts</td>
                    <td><span class='respOK'>Start#user1</span></td>
                </tr>
            </table><br>
            PHP time: <?=time() ?>
        </center>
</body>
</html>