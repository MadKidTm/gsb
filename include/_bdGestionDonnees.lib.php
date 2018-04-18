<?php
/**
 * Regroupe les fonctions d'acc�s aux donn�es.
 * @package default
 * @author Arthur Martin
 * @todo Fonctions retournant plusieurs lignes sont � r��crire.
 */

/**
 * Se connecte au serveur de donn�es MySql.
 * Se connecte au serveur de donn�es MySql � partir de valeurs
 * pr�d�finies de connexion (h�te, compte utilisateur et mot de passe).
 * Retourne l'identifiant de connexion si succ�s obtenu, le bool�en false
 * si probl�me de connexion.
 * @return resource identifiant de connexion
 */
function connecterServeurBD() {
    $hostName = "localhost";
    $username = "root";
    $password = "";
    $dbName = "gsb_valide";
    $con = mysqli_connect($hostName, $username, $password, $dbName);

    if (mysqli_connect_errno()){

        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }else{
      return $con;
    }
}

/**
 * S�lectionne (rend active) la base de donn�es.
 * S�lectionne (rend active) la BD pr�d�finie gsb_frais sur la connexion
 * identifi�e par $con. Retourne true si succ�s, false sinon.
 * @param resource $con identifiant de connexion
 * @return boolean succ�s ou �chec de s�lection BD
 */
function activerBD($con) {
    $bd = "gsb_valide";
    $query = "SET CHARACTER SET utf8";
    // Modification du jeu de caract�res de la connexion
    $res = mysqli_query($con, $query);
    $ok = mysqli_select_db($con, $bd);
    return $ok;
}

/**
 * Ferme la connexion au serveur de donn�es.
 * Ferme la connexion au serveur de donn�es identifi�e par l'identifiant de
 * connexion $con.
 * @param resource $con identifiant de connexion
 * @return void
 */
function deconnecterServeurBD($con) {
    mysqli_close($con);
}

/**
 * Echappe les caract�res sp�ciaux d'une cha�ne.
 * Envoie la cha�ne $str �chapp�e, c�d avec les caract�res consid�r�s sp�ciaux
 * par MySql (tq la quote simple) pr�c�d�s d'un \, ce qui annule leur effet sp�cial
 * @param string $str cha�ne � �chapper
 * @return string cha�ne �chapp�e
 */
function filtrerChainePourBD($con, $str) {
    if ( ! get_magic_quotes_gpc() ) {
        // si la directive de configuration magic_quotes_gpc est activ�e dans php.ini,
        // toute cha�ne re�ue par get, post ou cookie est d�j� �chapp�e
        // par cons�quent, il ne faut pas �chapper la cha�ne une seconde fois
        $str = mysqli_real_escape_string($con, $str);
    }
    return $str;
}

/**
 * Fournit les informations sur un visiteur demand�.
 * Retourne les informations du visiteur d'id $unId sous la forme d'un tableau
 * associatif dont les cl�s sont les noms des colonnes(id, nom, prenom).
 * @param resource $con identifiant de connexion
 * @param string $unId id de l'utilisateur
 * @return array  tableau associatif du visiteur
 */
function obtenirDetailVisiteur($con, $unId) {
    $id = filtrerChainePourBD($con, $unId);
    $requete = "select id, nom, prenom from utilisateurs where id='" . $unId . "'";
    $idJeuRes = mysqli_query($con, $requete);
    $ligne = false;
    if ( $idJeuRes ) {
        $ligne = mysqli_fetch_assoc($idJeuRes);
        mysqli_free_result($idJeuRes);
    }
    return $ligne ;
}

/**
 * Fournit les informations d'une fiche de frais.
 * Retourne les informations de la fiche de frais du mois de $unMois (MMAAAA)
 * sous la forme d'un tableau associatif dont les cl�s sont les noms des colonnes
 * (nbJustitificatifs, idEtat, libelleEtat, dateModif, montantValide).
 * @param resource $con identifiant de connexion
 * @param string $unMois mois demand� (MMAAAA)
 * @param string $unIdVisiteur id visiteur
 * @return array tableau associatif de la fiche de frais
 */
function obtenirDetailFicheFrais($con, $unMois, $unIdVisiteur) {
    $unMois = filtrerChainePourBD($con, $unMois);
    $ligne = false;
    $requete="select IFNULL(nbJustificatifs,0) as nbJustificatifs, Etat.id as idEtat, libelle as libelleEtat, dateModif, montantValide
    from FicheFrais inner join Etat on idEtat = Etat.id
    where idVisiteur='" . $unIdVisiteur . "' and mois='" . $unMois . "'";
    $idJeuRes = mysqli_query($con, $requete);
    if ( $idJeuRes ) {
        $ligne = mysqli_fetch_assoc($idJeuRes);
    }
    mysqli_free_result($idJeuRes);

    return $ligne ;
}

/**
 * V�rifie si une fiche de frais existe ou non.
 * Retourne true si la fiche de frais du mois de $unMois (MMAAAA) du visiteur
 * $idVisiteur existe, false sinon.
 * @param resource $con identifiant de connexion
 * @param string $unMois mois demand� (MMAAAA)
 * @param string $unIdVisiteur id visiteur
 * @return bool�en existence ou non de la fiche de frais
 */
function existeFicheFrais($con, $unMois, $unIdVisiteur) {
    $unMois = filtrerChainePourBD($con, $unMois);
    $requete = "select idVisiteur from FicheFrais where idVisiteur='" . $unIdVisiteur .
              "' and mois='" . $unMois . "'";
    $idJeuRes = mysqli_query($con, $requete);
    $ligne = false ;
    if ( $idJeuRes ) {
        $ligne = mysqli_fetch_assoc($idJeuRes);
        mysqli_free_result($idJeuRes);
    }

    // si $ligne est un tableau, la fiche de frais existe, sinon elle n'exsite pas
    return is_array($ligne) ;
}

/**
 * Fournit le mois de la derni�re fiche de frais d'un visiteur.
 * Retourne le mois de la derni�re fiche de frais du visiteur d'id $unIdVisiteur.
 * @param resource $con identifiant de connexion
 * @param string $unIdVisiteur id visiteur
 * @return string dernier mois sous la forme AAAAMM
 */
function obtenirDernierMoisSaisi($con, $unIdVisiteur) {
	$requete = "select max(mois) as dernierMois from FicheFrais where idVisiteur='" .
            $unIdVisiteur . "'";
	$idJeuRes = mysqli_query($con, $requete);
    $dernierMois = false ;
    if ( $idJeuRes ) {
        $ligne = mysqli_fetch_assoc($idJeuRes);
        $dernierMois = $ligne["dernierMois"];
        mysqli_free_result($idJeuRes);
    }
	return $dernierMois;
}

/**
 * Ajoute une nouvelle fiche de frais et les �l�ments forfaitis�s associ�s,
 * Ajoute la fiche de frais du mois de $unMois (MMAAAA) du visiteur
 * $idVisiteur, avec les �l�ments forfaitis�s associ�s dont la quantit� initiale
 * est affect�e � 0. Cl�t �ventuellement la fiche de frais pr�c�dente du visiteur.
 * @param resource $con identifiant de connexion
 * @param string $unMois mois demand� (MMAAAA)
 * @param string $unIdVisiteur id visiteur
 * @return void
 */
function ajouterFicheFrais($con, $unMois, $unIdVisiteur) {
    $unMois = filtrerChainePourBD($con, $unMois);
    // modification de la derni�re fiche de frais du visiteur
    $dernierMois = obtenirDernierMoisSaisi($con, $unIdVisiteur);
	$laDerniereFiche = obtenirDetailFicheFrais($con, $dernierMois, $unIdVisiteur);
	if ( is_array($laDerniereFiche) && $laDerniereFiche['idEtat']=='CR'){
		modifierEtatFicheFrais($con, $dernierMois, $unIdVisiteur, 'CL');
	}

    // ajout de la fiche de frais � l'�tat Cr��
    $requete = "insert into FicheFrais (idVisiteur, mois, nbJustificatifs, montantValide, idEtat, dateModif) values ('"
              . $unIdVisiteur
              . "','" . $unMois . "',0,NULL, 'CR', '" . date("Y-m-d") . "')";
    mysqli_query($con, $requete);

    // ajout des �l�ments forfaitis�s
    $requete = "select id from FraisForfait";
    $idJeuRes = mysqli_query($con, $requete);
    if ( $idJeuRes ) {
        $ligne = mysqli_fetch_assoc($idJeuRes);
        while ( is_array($ligne) ) {
            $idFraisForfait = $ligne["id"];
            // insertion d'une ligne frais forfait dans la base
            $requete = "insert into LigneFraisForfait (idVisiteur, mois, idFraisForfait, quantite)
                        values ('" . $unIdVisiteur . "','" . $unMois . "','" . $idFraisForfait . "',0)";
            mysqli_query($con, $requete);
            // passage au frais forfait suivant
            $ligne = mysqli_fetch_assoc ($idJeuRes);
        }
        mysqli_free_result($idJeuRes);
    }
}

/**
 * Retourne le texte de la requ�te select concernant les mois pour lesquels un
 * visiteur a une fiche de frais.
 *
 * La requ�te de s�lection fournie permettra d'obtenir les mois (AAAAMM) pour
 * lesquels le visiteur $unIdVisiteur a une fiche de frais.
 * @param string $unIdVisiteur id visiteur
 * @return string texte de la requ�te select
 */
function obtenirReqMoisFicheFrais($unIdVisiteur) {
    $req = "select FicheFrais.mois as mois from  FicheFrais where FicheFrais.idvisiteur ='"
            . $unIdVisiteur . "' order by FicheFrais.mois desc ";
    return $req ;
}

function onbtenirReqMoisSuivi(){
    $req = "SELECT FicheFrais.mois as mois 
            FROM FicheFrais
            WHERE idEtat = 'VA' OR idEtat = 'MP'
            
            GROUP BY mois
            ORDER BY FicheFrais.mois desc ";
    return $req ;
}

/**
 * Retourne le texte de la requ�te select concernant les �l�ments forfaitis�s
 * d'un visiteur pour un mois donn�s.
 *
 * La requ�te de s�lection fournie permettra d'obtenir l'id, le libell� et la
 * quantit� des �l�ments forfaitis�s de la fiche de frais du visiteur
 * d'id $idVisiteur pour le mois $mois
 * @param string $unMois mois demand� (MMAAAA)
 * @param string $unIdVisiteur id visiteur
 * @return string texte de la requ�te select
 */
function obtenirReqEltsForfaitFicheFrais($con, $unMois, $unIdVisiteur) {

    $unMois = filtrerChainePourBD($con, $unMois);
    $requete = "SELECT idFraisForfait, libelle, quantite
                FROM LigneFraisForfait
                INNER JOIN FraisForfait on FraisForfait.id = LigneFraisForfait.idFraisForfait
                WHERE idVisiteur='" . $unIdVisiteur . "' and mois='" . $unMois . "'";

    return $requete;
}

/**
 * Retourne le texte de la requ�te select concernant les �l�ments hors forfait
 * d'un visiteur pour un mois donn�s.
 *
 * La requ�te de s�lection fournie permettra d'obtenir l'id, la date, le libell�
 * et le montant des �l�ments hors forfait de la fiche de frais du visiteur
 * d'id $idVisiteur pour le mois $mois
 * @param string $unMois mois demand� (MMAAAA)
 * @param string $unIdVisiteur id visiteur
 * @return string texte de la requ�te select
 */
function obtenirReqEltsHorsForfaitFicheFrais($con, $unMois, $unIdVisiteur) {
    $unMois = filtrerChainePourBD($con, $unMois);
    $requete = "select id, date, libelle, montant from LigneFraisHorsForfait
              where idVisiteur='" . $unIdVisiteur
              . "' and mois='" . $unMois . "'";
    return $requete;
}

/**
 * Supprime une ligne hors forfait.
 * Supprime dans la BD la ligne hors forfait d'id $unIdLigneHF
 * @param resource $con identifiant de connexion
 * @param string $idLigneHF id de la ligne hors forfait
 * @return void
 */
function supprimerLigneHF($con, $unIdLigneHF) {
    $requete = "delete from LigneFraisHorsForfait where id = " . $unIdLigneHF;
    mysql_query($requete, $con);
}

//Fonction permettant de refuser une ligne de frais hors forfait
function refuserLigneHF($con, $unIdLigneHF, $unLibelleLigneHF){
    $requete = "UPDATE LigneFraisHorsForfait
                SET libelle = 'REFUSE-$unLibelleLigneHF'
                WHERE id = '$unIdLigneHF'";
                
    mysqli_query($con, $requete);
}

//Fonction permetant de valider une Ligne de frais Hors Forfait
function validerLigneHF($con, $unIdLigneHF, $unLibelleLigneHF){
    
    //On calcule la longeur de la chaine sans 'REFUSE-'
    $longeur = strlen($unLibelleLigneHF) - 7;
    //On recupère la chaine apres "REFUSE-" jusqu'a la fin
    $unLibelleLigneHF = substr($unLibelleLigneHF, 7, $longeur);
    
    $requete = "UPDATE LigneFraisHorsForfait
                SET libelle = '$unLibelleLigneHF'
                WHERE id = '$unIdLigneHF'";
    
    mysqli_query($con, $requete);
                
    
}

/**
 * Ajoute une nouvelle ligne hors forfait.
 * Ins�re dans la BD la ligne hors forfait de libell� $unLibelleHF du montant
 * $unMontantHF ayant eu lieu � la date $uneDateHF pour la fiche de frais du mois
 * $unMois du visiteur d'id $unIdVisiteur
 * @param resource $con identifiant de connexion
 * @param string $unMois mois demand� (AAMMMM)
 * @param string $unIdVisiteur id du visiteur
 * @param string $uneDateHF date du frais hors forfait
 * @param string $unLibelleHF libell� du frais hors forfait
 * @param double $unMontantHF montant du frais hors forfait
 * @return void
 */
function ajouterLigneHF($con, $unMois, $unIdVisiteur, $uneDateHF, $unLibelleHF, $unMontantHF) {
    $unLibelleHF = filtrerChainePourBD($con, $unLibelleHF);
    $uneDateHF = filtrerChainePourBD($con, convertirDateFrancaisVersAnglais($uneDateHF));
    $unMois = filtrerChainePourBD($con, $unMois);
    $requete = "insert into LigneFraisHorsForfait(idVisiteur, mois, date, libelle, montant)
                values ('" . $unIdVisiteur . "','" . $unMois . "','" . $uneDateHF . "','" . $unLibelleHF . "'," . $unMontantHF .")";
                
    mysqli_query($con, $requete);
}

/**
 * Modifie les quantit�s des �l�ments forfaitis�s d'une fiche de frais.
 * Met � jour les �l�ments forfaitis�s contenus
 * dans $desEltsForfaits pour le visiteur $unIdVisiteur et
 * le mois $unMois dans la table LigneFraisForfait, apr�s avoir filtr�
 * (annul� l'effet de certains caract�res consid�r�s comme sp�ciaux par
 *  MySql) chaque donn�e
 * @param resource $con identifiant de connexion
 * @param string $unMois mois demand� (MMAAAA)
 * @param string $unIdVisiteur  id visiteur
 * @param array $desEltsForfait tableau des quantit�s des �l�ments hors forfait
 * avec pour cl�s les identifiants des frais forfaitis�s
 * @return void
 */
function modifierEltsForfait($con, $unMois, $unIdVisiteur, $desEltsForfait) {
    $unMois=filtrerChainePourBD($con, $unMois);
    $unIdVisiteur=filtrerChainePourBD($con, $unIdVisiteur);
    foreach ($desEltsForfait as $idFraisForfait => $quantite) {
        $requete = "UPDATE LigneFraisForfait SET quantite = " . $quantite
                    . " where idVisiteur = '" . $unIdVisiteur . "' and mois = '"
                    . $unMois . "' and idFraisForfait='" . $idFraisForfait . "'";
                    
      mysqli_query($con, $requete);
    }
}

/**
 * Contr�le les informations de connexionn d'un utilisateur.
 * V�rifie si les informations de connexion $unLogin, $unMdp sont ou non valides.
 * Retourne les informations de l'utilisateur sous forme de tableau associatif
 * dont les cl�s sont les noms des colonnes (id, nom, prenom, login, mdp)
 * si login et mot de passe existent, le bool�en false sinon.
 * @param resource $con identifiant de connexion
 * @param string $unLogin login
 * @param string $unMdp mot de passe
 * @return array tableau associatif ou bool�en false
 */
function verifierInfosConnexion($con, $unLogin, $unMdp) {
    $unLogin = filtrerChainePourBD($con, $unLogin);
    $unMdp = filtrerChainePourBD($con, $unMdp);
    // le mot de passe est crypt� dans la base avec la fonction de hachage md5
    $req = "select id, nom, prenom, login, mdp, type from utilisateurs where login='".$unLogin."' and mdp='" . $unMdp . "'";
    $idJeuRes = mysqli_query($con, $req);
    $ligne = false;
    if ( $idJeuRes ) {
        $ligne = mysqli_fetch_assoc($idJeuRes);
        mysqli_free_result($idJeuRes);
    }
    return $ligne;
}

/**
 * Modifie l'�tat et la date de modification d'une fiche de frais

 * Met � jour l'�tat de la fiche de frais du visiteur $unIdVisiteur pour
 * le mois $unMois � la nouvelle valeur $unEtat et passe la date de modif �
 * la date d'aujourd'hui
 * @param resource $con identifiant de connexion
 * @param string $unIdVisiteur
 * @param string $unMois mois sous la forme aaaamm
 * @return void
 */
function modifierEtatFicheFrais($con, $unMois, $unIdVisiteur, $unEtat) {
    $requete = "UPDATE FicheFrais SET idEtat = '" . $unEtat .
               "', dateModif = now() WHERE idVisiteur ='" .
               $unIdVisiteur . "' and mois = '". $unMois . "'";
    mysqli_query($requete, $con);
}

function obtenirVisiteurQuiOnDesFiches($con, $mois){
    $requete = "SELECT id, nom, prenom, idEtat 
                FROM utilisateurs
                INNER JOIN FicheFrais ON utilisateurs.id = FicheFrais.idVisiteur
                WHERE mois LIKE '$mois'
                AND idEtat = 'VA' 
                OR mois LIKE '$mois' AND idEtat = 'CL'
                
                
                GROUP BY nom";

    $visiteurs = mysqli_query($con, $requete);

    while ( $visiteur = mysqli_fetch_assoc($visiteurs)){
      $tabVisiteurs[] = array(
          'id'     => $visiteur['id'],
          'nom'    => $visiteur['nom'],
          'prenom' => $visiteur['prenom'],
          'etat'   => $visiteur['idEtat']
      );
    }
    return $tabVisiteurs;

}



function obtenirFicheFraisForfaits($con, $id,$mois){
    $requete = "SELECT idFraisForfait, libelle, quantite, montant, idEtat
                FROM LigneFraisForfait
                INNER JOIN FicheFrais ON LigneFraisForfait.idVisiteur = FicheFrais.idVisiteur
                INNER JOIN FraisForfait ON LigneFraisForfait.idFraisForfait = FraisForfait.id
                WHERE FicheFrais.idVisiteur LIKE '$id'
                    AND FicheFrais.mois LIKE '$mois'
                    AND LigneFraisForfait.mois LIKE '$mois'
                    AND LigneFraisForfait.idVisiteur LIKE '$id'
                    
                GROUP BY libelle" ;
                

    $infoFicheForfait = mysqli_query($con, $requete);
    

    while ( $fiche = mysqli_fetch_assoc($infoFicheForfait)){

      $tabFiche[] = array(
          'libelle'  => $fiche['libelle'],
          'quantite' => $fiche['quantite'],
          'etat'     => $fiche['idEtat'],
          'frais'    => $fiche['idFraisForfait'],
          'montant'  => $fiche['montant']
      );
    }
    //var_dump($tabFiche);
    return $tabFiche;

}

function obtenirFicheFraisHorsForfait($con, $id, $mois){
  $requete = "SELECT libelle, date, montant
              FROM LigneFraisHorsForfait
              WHERE idVisiteur LIKE '$id'
                  AND mois LIKE '$mois'";
                  
            

  $infoFicheHorsForfait = mysqli_query($con, $requete);

  while( $fiche = mysqli_fetch_assoc($infoFicheHorsForfait)){

    $tabFiche[] = array(
        'libelle' => $fiche['libelle'],
        'date'    => $fiche['date'],
        'montant' => $fiche['montant']
    );

  }
  mysqli_free_result($infoFicheHorsForfait);

  if(isset($tabFiche))
    return $tabFiche;
  else
    return false;


}

function obtenirFraisForfait($con, $idForfait){
  $requete = "SELECT montant
              FROM FraisForfait
              WHERE id LIKE '$idForfait'";

  $resultatReq = mysqli_query($con, $requete);

  $resultat = mysqli_fetch_assoc($resultatReq);
  mysqli_free_result($resultatReq);


  return floatval($resultat['montant']);

}

function obtenirLibelleEtat($con, $idEtat){
    $requete = "SELECT libelle
                FROM Etat
                WHERE id LIKE '$idEtat'" ;
    
    $libelle = mysqli_query($con, $requete);
    
    $libelle = mysqli_fetch_assoc($libelle);
    
    return $libelle['libelle'];
}

function cloturerFicheFrais($con){
    $date = mktime(0, 0, 0, date("m")-1, date("d"),   date("Y"));
    $mois =  date('Ym', $date);
    
    $req = "UPDATE FicheFrais
            SET idEtat = 'CL'
            WHERE mois = '$mois'
            AND idEtat = 'CR'";
            
    mysqli_query($con, $req);
}

function validerFicheFrais($con, $id){
    $date = mktime(0, 0, 0, date("m")-1, date("d"),   date("Y"));
    $mois =  date('Ym', $date);
    
    $requete = "UPDATE FicheFrais 
                SET idEtat = 'VA' 
                WHERE idVisiteur = '$id' AND mois = '$mois'";
                
    mysqli_query($con, $requete);
}

function mettreEnPaiementFicheFrais($con, $id, $date){
   
    $requete = "UPDATE FicheFrais 
                SET idEtat = 'MP' 
                WHERE idVisiteur = '$id' AND mois = '$date'";
    
                
    mysqli_query($con, $requete);
}

function obtenirFichesValide($con, $mois){
    $req = "SELECT id, nom, prenom, mois, idEtat
            FROM FicheFrais
            INNER JOIN utilisateurs ON FicheFrais.idVisiteur = utilisateurs.id
            WHERE idEtat = 'VA' AND mois='$mois'
                OR idEtat = 'MP' AND mois='$mois'
            
            ORDER BY nom";
    
    
    $fiches = mysqli_query($con, $req);
    
     while( $fiche = mysqli_fetch_assoc($fiches)){

    $tabFiche[] = array(
        'id'       => $fiche['id'],
        'nom'      => $fiche['nom'],
        'prenom'   => $fiche['prenom'],
        'mois'     => $fiche['mois'],
        'etat'     => $fiche['idEtat']
    );

  }
  mysqli_free_result($fiches);
    
  return $tabFiche;
}
?>
