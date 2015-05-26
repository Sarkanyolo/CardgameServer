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
require('manual.html');