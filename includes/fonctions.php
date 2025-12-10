<?php

function getJoueursActifs($pdo) {
    $req = $pdo->query("SELECT * FROM joueurs WHERE actif = 1");
    return $req->fetchAll(PDO::FETCH_ASSOC);
}

function getMatchs($pdo) {
    return $pdo->query("SELECT * FROM matchs")->fetchAll(PDO::FETCH_ASSOC);
}
