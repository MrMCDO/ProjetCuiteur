<?php
/* ------------------------------------------------------------------------------
    Architecture de la page
    - étape 1 : vérifications diverses et traitement des soumissions
    - étape 2 : génération du code HTML de la page
------------------------------------------------------------------------------*/

ob_start(); //démarre la bufferisation
session_start();

require_once 'bibli_generale.php';
require_once 'bibli_cuiteur.php';

/*------------------------- Etape 1 --------------------------------------------
- vérifications diverses et traitement des soumissions
------------------------------------------------------------------------------*/

// si utilisateur déjà authentifié, on le redirige vers la page cuiteur_1.php
if (gh_est_authentifie()){
    header('Location: ../index.php');
    exit();
}

// traitement si soumission du formulaire d'inscription
$er = isset($_POST['btnSInscrire']) ? eml_traitement_inscription() : array(); 

/*------------------------- Etape 2 --------------------------------------------
- génération du code HTML de la page
------------------------------------------------------------------------------*/

gh_aff_debut('Cuiteur | Inscription', '../styles/cuiteur.css');

gh_aff_entete('Inscription', false);
gh_aff_infos(false);

eml_aff_formulaire($er);

gh_aff_pied();
gh_aff_fin();

// facultatif car fait automatiquement par PHP
ob_end_flush();

// ----------  Fonctions locales du script ----------- //

/**
 * Affichage du contenu de la page (formulaire d'inscription)
 *
 * @param   array   $err    tableau d'erreurs à afficher
 * @global  array   $_POST
 */
function eml_aff_formulaire(array $err): void {

    // réaffichage des données soumises en cas d'erreur, sauf les mots de passe 
    if (isset($_POST['btnSInscrire'])){
        $values = gh_html_proteger_sortie($_POST);
    }
    else{
        $values['pseudo'] = $values['nomprenom'] = $values['email'] = $values['naissance'] = '';
    }
        
    if (count($err) > 0) {
        echo '<p class="error">Les erreurs suivantes ont été détectées :';
        foreach ($err as $v) {
            echo '<br> - ', $v;
        }
        echo '</p>';    
    }


    echo    
            '<p>Pour vous inscrire, merci de fournir les informations suivantes. </p>',
            '<form method="post" action="inscription.php">',
                '<table>';

    gh_aff_ligne_input( 'Votre pseudo :', array('type' => 'text', 'name' => 'pseudo', 'value' => $values['pseudo'], 
                        'placeholder' => 'Minimum 4 caractères alphanumériques', 'required' => null));
    gh_aff_ligne_input('Votre mot de passe :', array('type' => 'password', 'name' => 'passe1', 'value' => '', 'required' => null));
    gh_aff_ligne_input('Répétez le mot de passe :', array('type' => 'password', 'name' => 'passe2', 'value' => '', 'required' => null));
    gh_aff_ligne_input('Nom et prénom :', array('type' => 'text', 'name' => 'nomprenom', 'value' => $values['nomprenom'], 'required' => null));
    gh_aff_ligne_input('Votre adresse email :', array('type' => 'email', 'name' => 'email', 'value' => $values['email'], 'required' => null));
    gh_aff_ligne_input('Votre date de naissance :', array('type' => 'date', 'name' => 'naissance', 'value' => $values['naissance'], 'required' => null));

    echo 
                    '<tr>',
                        '<td colspan="2">',
                            '<input type="submit" name="btnSInscrire" value="S\'inscrire">',
                            '<input type="reset" value="Réinitialiser">', 
                        '</td>',
                    '</tr>',
                '</table>',
            '</form>';
}

/**
 *  Traitement de l'inscription 
 *
 *      Etape 1. vérification de la validité des données
 *                  -> return des erreurs si on en trouve
 *      Etape 2. enregistrement du nouvel inscrit dans la base
 *      Etape 3. ouverture de la session et redirection vers la page protegee.php 
 *
 * Toutes les erreurs détectées qui nécessitent une modification du code HTML sont considérées comme des tentatives de piratage 
 * et donc entraînent l'appel de la fonction gh_session_exit() sauf :
 * - les éventuelles suppressions des attributs required car l'attribut required est une nouveauté apparue dans la version HTML5 et 
 *   nous souhaitons que l'application fonctionne également correctement sur les vieux navigateurs qui ne supportent pas encore HTML5
 * - une éventuelle modification de l'input de type date en input de type text car c'est ce que font les navigateurs qui ne supportent 
 *   pas les input de type date
 *
 * @global array    $_POST
 *
 * @return array    tableau assosiatif contenant les erreurs
 */
function eml_traitement_inscription(): array {
    
    if( !gh_parametres_controle('post', array('pseudo', 'email', 'nomprenom', 'naissance', 
                                              'passe1', 'passe2', 'btnSInscrire'))) {
        gh_session_exit();   
    }
    
    foreach($_POST as &$val){
        $val = trim($val);
    }
    
    $erreurs = array();
    
    // vérification du pseudo
    $l = mb_strlen($_POST['pseudo'], 'UTF-8');
    if ($l == 0){
        $erreurs[] = 'Le pseudo doit être renseigné.';
    }
    else if ($l < LMIN_PSEUDO || $l > LMAX_PSEUDO){
        $erreurs[] = 'Le pseudo doit être constitué de '. LMIN_PSEUDO . ' à ' . LMAX_PSEUDO . ' caractères.';
    }
    else if( !mb_ereg_match('^[[:alnum:]]{'.LMIN_PSEUDO.','.LMAX_PSEUDO.'}$', $_POST['pseudo'])){
        $erreurs[] = 'Le pseudo ne doit contenir que des caractères alphanumériques.' ;
    }
    
    // vérification des mots de passe
    gh_verif_passe($_POST['passe1'], $_POST['passe2'], $erreurs);
    
    // vérification des noms et prenoms
    gh_verif_nom($_POST['nomprenom'], $erreurs);
    
    // vérification du format de l'adresse email
    gh_verif_email($_POST['email'], $erreurs);

    // vérification de la date de naissance
    gh_verif_date_naissance($_POST['naissance'], $erreurs);
    
   
    if (count($erreurs) == 0) {
        // vérification de l'unicité du pseudo 
        // (uniquement si pas d'autres erreurs, parce que la connection à la base de données est consommatrice de ressources)
        $bd = gh_bd_connect();

        // pas utile, car le pseudo a déjà été vérifié, mais tellement plus sécurisant...
        $pseudo = gh_bd_proteger_entree($bd, $_POST['pseudo']);
        $sql = "SELECT usID FROM users WHERE usPseudo = '$pseudo'"; 
    
        $res = gh_bd_send_request($bd, $sql);
        
        if (mysqli_num_rows($res) != 0) {
            $erreurs[] = 'Le pseudo spécifié est déjà utilisé.';
            // libération des ressources 
            mysqli_free_result($res);
            mysqli_close($bd);
        }
        else{
            // libération des ressources 
            mysqli_free_result($res);
        }
        
    }
    
    // s'il y a des erreurs ==> on retourne le tableau d'erreurs    
    if (count($erreurs) > 0) {  
        return $erreurs;    
    }
    
    // pas d'erreurs ==> enregistrement de l'utilisateur
    $nomprenom = gh_bd_proteger_entree($bd, $_POST['nomprenom']);
    $email = gh_bd_proteger_entree($bd, $_POST['email']);
    
    $passe1 = password_hash($_POST['passe1'], PASSWORD_DEFAULT);
    $passe1 = gh_bd_proteger_entree($bd, $passe1);
    
    // convert date of birth to sql format
    $yyyymmdd = gh_convert_date_to_sql_format($_POST['naissance']);
    
    $date_inscription = date('Ymd');
    
    $sql = "INSERT INTO users(usNom, usVille, usWeb, usMail, usPseudo, usPasse, usBio, usDateNaissance, usDateInscription) 
            VALUES ('$nomprenom', '', '', '$email', '$pseudo', '$passe1',  '', $yyyymmdd, $date_inscription)";
            
    gh_bd_send_request($bd, $sql);
    
    // mémorisation de l'ID dans une variable de session 
    // cette variable de session permet de savoir si le client est authentifié
    $_SESSION['usID'] = mysqli_insert_id($bd);
    
    // libération des ressources
    mysqli_close($bd);
    
    // redirection vers la page protegee.php
    header('Location: compte.php');
    exit();
}


?>
