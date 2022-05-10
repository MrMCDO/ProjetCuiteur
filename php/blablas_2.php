<?php

ob_start(); //démarre la bufferisation

require_once 'bibli_generale.php';
require_once 'bibli_cuiteur.php';

$bd = gh_bd_connect();

$sql = 'SELECT usID, usPseudo, usNom, usAvecPhoto, blTexte, blDate, blHeure
        FROM users
        INNER JOIN blablas ON blIDAuteur = usID
        WHERE usID = 2
        ORDER BY blID DESC';

$res = gh_bd_send_request($bd, $sql);

gh_aff_debut('Cuiteur | Blablas', '../styles/cuiteur.css');

// Récupération des données et encapsulation dans du code HTML envoyé au navigateur
$i = 0;
while ($t = mysqli_fetch_assoc($res)) {
    if ($i == 0){
        gh_aff_entete(gh_html_proteger_sortie("Les blablas de {$t['usPseudo']}"));
        gh_aff_infos();
        echo '<ul>';
    }
    echo    '<li>', 
                '<img src="../', ($t['usAvecPhoto'] == 1 ? "upload/{$t['usID']}.jpg" : 'images/anonyme.jpg'), 
                '" class="imgAuteur" alt="photo de l\'auteur">',
                gh_html_proteger_sortie($t['usPseudo']), ' ', gh_html_proteger_sortie($t['usNom']), '<br>',
                gh_html_proteger_sortie($t['blTexte']),
                '<p class="finMessage">',
                gh_amj_clair($t['blDate']), ' à ', gh_heure_clair($t['blHeure']),
                '<a href="../index.php">Répondre</a> <a href="../index.php">Recuiter</a></p>',
            '</li>';
    ++$i;
}

echo '</ul>';

// libération des ressources
mysqli_free_result($res);
mysqli_close($bd);

gh_aff_pied();
gh_aff_fin();

// facultatif car fait automatiquement par PHP
ob_end_flush();



?>
