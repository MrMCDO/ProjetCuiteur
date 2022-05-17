<?php

ob_start(); // start output buffering
session_start();

require_once 'bibli_generale.php';
require_once 'bibli_cuiteur.php';

// if user is not authenticated, redirect to index.php
if (! gh_est_authentifie()){
    header('Location: ../index.php');
    exit;
}

/*------------------------------------------------------------------------------
- Do search if form is submitted
------------------------------------------------------------------------------*/
$row;
if(isset($_POST['recherche'])){
    $db = gh_bd_connect();
    $recherche = gh_bd_proteger_entree($db, $_POST['recherche']);
    $request = "SELECT DISTINCT users.*, eaIDAbonne
                FROM users LEFT JOIN estabonne ON users.usID = estabonne.eaIDAbonne
                WHERE (usPseudo LIKE '%$recherche%' OR usNom LIKE '%$recherche%')";
                
    $result = gh_bd_send_request($db, $request);
}



/*------------------------------------------------------------------------------
- Generating the html code for the page
------------------------------------------------------------------------------*/

gh_aff_debut('Cuiteur | Recherche', '../styles/cuiteur.css');

gh_aff_entete('Rechercher des utilisateurs', true);
gh_aff_infos(true);

echo '<form  id="recherche" action="recherche.php" method="post">',
        '<table>',
            '<tr><td><input type=text name="recherche" size=30 maxlength=30></td>',
                '<td><input type=submit value="Rechercher"></td></tr>',
        '</table>',
    '</form>';
if(isset($_POST['recherche'])){
    gh_aff_user_stats_list($result, $db);
    // free resources
    mysqli_free_result($result);
    mysqli_close($db);
}
gh_aff_pied();
gh_aff_fin();

// facultatif car fait automatiquement par PHP
ob_end_flush();