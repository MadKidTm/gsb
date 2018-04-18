<?php
/**
 * Page d'accueil de l'application web AppliFrais
 * @package default
 * @todo  RAS
 */
  $repInclude = './include/';
  require($repInclude . "_init.inc.php");

  // page inaccessible si visiteur non connecté
  if ( ! estVisiteurConnecte() )
  {
        header("Location: cSeConnecter.php");
  }
  require($repInclude . "_entete.inc.html");
  require($repInclude . "_sommaire.inc.php");
?>
  <!-- Division principale -->
  <div id="contenu">
      <h2>Bienvenue sur l'intranet GSB</h2>
      <?php
        if (estVisiteurConnecte() ) {
      ?>
              <ul id="menuList">
                  <?php
                   if($_SESSION['typeUser'] == 'visiteur'){
                  ?>
                     <li class="smenu">
                        <a href="cSaisieFicheFrais.php" title="Saisie fiche de frais du mois courant">Saisie fiche de frais</a>
                     </li>
                     <li class="smenu">
                        <a href="cConsultFichesFrais.php" title="Consultation de mes fiches de frais">Mes fiches de frais</a>
                     </li>
                 <?php
                    }

                    if($_SESSION['typeUser'] == 'comptable'){
                      cloturerFicheFrais($idConnexion);
                      ?>
                        <li class="smenu">
                           <a href="cValidationFicheFrais.php" title="Validation des fiches frais du mois dernier">Validation fiche de frais</a>
                        </li>
                        <li class="smenu">
                           <a href="cSuiviPaiement.php" title="Suivi des mises en paiement des fiches validées">Suivre paiement des fiches frais</a>
                        </li>
                      <?php
                    }
                  ?>
               </ul>
              <?php
                // affichage des éventuelles erreurs déjà détectées
                if ( nbErreurs($tabErreurs) > 0 ) {
                    echo toStringErreurs($tabErreurs) ;
                }
        }
              ?>
  </div>
<?php
  require($repInclude . "_pied.inc.html");
  require($repInclude . "_fin.inc.php");
?>
