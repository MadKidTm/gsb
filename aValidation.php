<?php

$repInclude = './include/';
require($repInclude . "_init.inc.php");

// page inaccessible si visiteur non connecté
if ( ! estVisiteurConnecte() ) {
    header("Location: cSeConnecter.php");
}
require($repInclude . "_entete.inc.html");
require($repInclude . "_sommaire.inc.php");

$idVisiteur = $_GET['id'];

validerFicheFrais($idConnexion, $idVisiteur);

header('Location: cValidationFicheFrais.php');