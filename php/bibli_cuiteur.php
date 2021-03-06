<?php

/*********************************************************
 *        Bibliothèque de fonctions spécifiques          *
 *               à l'application Cuiteur                 *
 *********************************************************/

 // Force l'affichage des erreurs
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting( E_ALL );

// Définit le fuseau horaire par défaut à utiliser. Disponible depuis PHP 5.1
date_default_timezone_set('Europe/Paris');

//définition de l'encodage des caractères pour les expressions rationnelles multi-octets
mb_regex_encoding ('UTF-8');

define('IS_DEV', true);//true en phase de développement, false en phase de production

 // Paramètres pour accéder à la base de données
define('BD_SERVER', 'localhost');
define('BD_NAME', 'cuiteur_bd');
define('BD_USER', 'cuiteur_userl');
define('BD_PASS', 'cuiteur_passl');
/*define('BD_NAME', 'merlet_cuiteur');
define('BD_USER', 'merlet_u');
define('BD_PASS', 'merlet_p');*/


// paramètres de l'application
define('LMIN_PSEUDO', 4);
define('LMAX_PSEUDO', 30); //longueur du champ dans la base de données
define('LMAX_EMAIL', 80); //longueur du champ dans la base de données
define('LMAX_NOMPRENOM', 60); //longueur du champ dans la base de données


define('LMIN_PASSWORD', 4);
define('LMAX_PASSWORD', 20);

define('MAX_CHAR_MESSAGE', 255);

define('AGE_MIN', 18);
define('AGE_MAX', 120);

define("NUMBER_CUIT_DISPLAY",4);
define('MAX_PHOTO_PROFILE_WEIGHT_KB', 20); // in kB

define("NB_SUGGESTIONS",5);
define("NB_MOST_POPULAR_USERS",10);

define("NB_TENDS_TO_DISPLAY",10);

// param for aside
define("NB_TENDS_ASIDE",5);
define("NB_SUGGESTIONS_ASIDE",2);

//_______________________________________________________________
/**
 * Génération et affichage de l'entete des pages
 *
 * @param ?string    $titre          Titre de l'entete (si null, affichage de l'entete de cuiteur.php avec le formulaire)
 * @param bool       $with_buttons   If true, show buttons (deconnection, profile modification, ...) => default true
 * @param ?string    $message        Message to show in the message field
 */
function gh_aff_entete(?string $titre = null, bool $connected=true, string $message = ''):void{
    echo '<div id="bcContenu">';
    if($connected === true){
        echo    '<header id="header_connected">';
    }else{
        echo '<header id="header_disconnected">';
    }
    if($connected === true){
        echo '<a href="./deconnexion.php" title="Se déconnecter de cuiteur"></a>',
            '<a href="../index.php" title="Ma page d\'accueil"></a>',
            '<a href="./recherche.php" title="Rechercher des personnes à suivre"></a>',
            '<a href="./compte.php" title="Modifier mes informations personnelles"></a>';
    }
    if ($titre === null){
        echo    '<form action="../index.php" method="POST">',
                    '<textarea name="txtMessage">';
        if($message !== ''){
            echo gh_html_proteger_sortie($message);
        }
        echo    '</textarea>',
                '<input type="submit" name="btnPublish" value="" title="Publier mon message">',
            '</form>';   
    }else{
        if($titre == ''){
            echo    '<h1 id="no_title">', $titre, '</h1>';

        }else{
            echo    '<h1>', $titre, '</h1>';
        }
        
    }
    echo    '</header>';    
}

//_______________________________________________________________
/**
 * Génération et affichage du bloc d'informations utilisateur
 *
 * @param bool    $connecte  true si l'utilisateur courant s'est authentifié, false sinon
 * @param mysqli  $dn        objet mysqli de la connexion à la base de données
 */
function gh_aff_infos(bool $connecte = true, mysqli $db = null):void{
    echo '<aside>';
    if ($connecte){
        $userData = gh_sql_get_user_stats($db, $_SESSION['usID']);     

        echo
            '<h3>Utilisateur</h3>',
            '<ul>',
                '<li>',
                    '<img class="photoProfil" src="../', ($userData['usAvecPhoto'] == 1 ? "upload/$userData[usID].jpg" : 'images/anonyme.jpg'), 
                        '" alt="photo de l\'utilisateur">',
                    gh_html_a('./utilisateur.php', $userData['usPseudo'], 'id', $userData['usID'], 'Voir mes infos'), ' ', $userData['usNom'],   
                '</li>',
                '<li>', gh_html_a('./blablas.php', $userData['nbBlablas']. ' ' . ($userData['nbBlablas'] > '1' ? 'blablas' : 'blabla'), 'id', $userData['usID'], 'Voir la liste de mes messages'), '</li>',
                '<li>', gh_html_a('./abonnements.php', $userData['nbAbonnements']. ' ' . ($userData['nbAbonnements'] > '1' ? 'abonnements' : 'abonnement'), 'id', $userData['usID'], 'Voir les personnes que je suis'), '</li>',
                '<li>', gh_html_a('./abonnes.php', $userData['nbAbonnes']. ' ' . ($userData['nbAbonnes'] > '1' ? 'abonnés' : 'abonné'), 'id', $userData['usID'], 'Voir les personnes qui me suivent'), '</li>',                
            '</ul>',

            '<h3>Tendances</h3>';
            $sqlTends = 'SELECT taID
                         FROM tags
                        GROUP BY taID
                        ORDER BY COUNT(*) DESC, taID ASC
                        LIMIT '. NB_TENDS_ASIDE .';';
            $resTends = gh_bd_send_request($db, $sqlTends);
            gh_aff_liste_tendances($resTends, true);
            mysqli_free_result($resTends);

        echo'<h3>Suggestions</h3>';
        $res = gh_sql_get_current_user_suggestions($db, NB_SUGGESTIONS_ASIDE);
        echo '<ul>';
        while($row = mysqli_fetch_assoc($res)){
            echo '<li>',
                    '<img class="photoProfil" src="../', ($row['usAvecPhoto'] == 1 ? "upload/$row[usID].jpg" : 'images/anonyme.jpg'), 
                        '" alt="photo de l\'utilisateur">',
                    gh_html_a('./utilisateur.php', $row['usPseudo'], 'id', $row['usID'], 'Voir mes infos'), ' ', $row['usNom'],
                '</li>';      
        }
        echo    '<li>', gh_html_a('./suggestions.php', 'Plus de suggestions', '', '', 'Voir plus de suggestions'), '</li>',
            '</ul>';

        mysqli_free_result($res);
    }
    echo '</aside>',
         '<main>';  
}

//_______________________________________________________________
/**
 * Génération et affichage du pied de page
 *
 */
function gh_aff_pied(): void{
    echo    '</main>',
            '<footer>',
                '<a href="../index.php">A propos</a>',
                '<a href="../index.php">Publicité</a>',
                '<a href="../index.php">Patati</a>',
                '<a href="../index.php">Aide</a>',
                '<a href="../">Patata</a>',
                '<a href="../index.php">Stages</a>',
                '<a href="../index.php">Emplois</a>',
                '<a href="../index.php">Confidentialité</a>',
            '</footer>',
    '</div>';
}

//_______________________________________________________________
/**
* Generate the html code to display blablas 
*
* @param mysqli_result  $r           Result of the SELECT query
* @param int            $nbToDisplay Number of results to display (0 = all)
* @param mysqli         $db          Database connection
*/
function gh_aff_blablas(mysqli $db, mysqli_result $r, int $nbToDisplay = 0): void {
    mysqli_data_seek($r , 0);

    $t = mysqli_fetch_assoc($r);
    for ($i = 0; $t != NULL && ($nbToDisplay === 0 || $i < $nbToDisplay); $i++) {
        if ($t['oriID'] === null){
            $id_orig = $t['autID'];
            $pseudo_orig = $t['autPseudo'];
            $photo = $t['autPhoto'];
            $nom_orig = $t['autNom'];
        }
        else{
            $id_orig = $t['oriID'];
            $pseudo_orig = $t['oriPseudo'];
            $photo = $t['oriPhoto'];
            $nom_orig = $t['oriNom'];
        }
        echo    '<li>', 
                    '<img src="../', ($photo == 1 ? "upload/$id_orig.jpg" : 'images/anonyme.jpg'), 
                    '" class="imgAuteur" alt="photo de l\'auteur">',
                    gh_html_a('utilisateur.php', '<strong>'.gh_html_proteger_sortie($pseudo_orig).'</strong>','id', $id_orig, 'Voir mes infos'), 
                    ' ', gh_html_proteger_sortie($nom_orig),
                    ($t['oriID'] !== null ? ', recuité par '
                                            .gh_html_a( 'utilisateur.php','<strong>'.gh_html_proteger_sortie($t['autPseudo']).'</strong>',
                                                        'id', $t['autID'], 'Voir mes infos') : ''),
                    '<br>';
                    // display the blabla, and convert the mentions and tags into links
                    $blabla = gh_html_proteger_sortie($t['blTexte']);

                    $mentions = array();
                    $tags = array();

                    // extract mentions and tags
                    preg_match_all('/@([a-zA-Z0-9_àèìòùÀÈÌÒÙáéíóúýÁÉÍÓÚÝâêîôûÂÊÎÔÛãñõÃÑÕäëïöüÿÄËÏÖÜŸçÇßØøÅå]+)/', $blabla, $mentions);
                    preg_match_all('/(?!\s)#[A-Za-z][A-Za-z0-9_àèìòùÀÈÌÒÙáéíóúýÁÉÍÓÚÝâêîôûÂÊÎÔÛãñõÃÑÕäëïöüÿÄËÏÖÜŸçÇßØøÅå]*\b/', $blabla, $tags);

                    $mentions[1] = array_unique($mentions[1]);
                    $tags[0] = array_unique($tags[0]);

                    // replace mentions and tags by links
                    foreach ($mentions[1] as $m) {
                        $sqlGetUserId = "SELECT usID
                                         FROM   users
                                         WHERE  usPseudo = '$m'";
                        $result = gh_bd_send_request($db, $sqlGetUserId);
                        $row = mysqli_fetch_assoc($result);
                        $blabla = str_replace('@'.$m, gh_html_a('utilisateur.php', '@'.$m, 'id', $row['usID'], 'Voir les infos de '.$m), $blabla);
                    }

                    foreach ($tags[0] as $tag) {
                        $tag = str_replace('#', '', $tag);
                        $blabla = str_replace('#'.$tag, gh_html_a('tendances.php', '#'.$tag, 'hashtag', $tag, 'Voir les blablas contenant le tag '.$tag), $blabla);
                    }

                    echo '<p>'.$blabla.'</p>',
                    '<p class="finMessage">',
                    gh_amj_clair($t['blDate']), ' à ', gh_heure_clair($t['blHeure']);
                    if ($t['autID'] == $_SESSION['usID']){
                        $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
                        echo '<a href="./supprimer_blabla.php?idBlabla='.urlencode($t['blID']).'&from='.urlencode($actual_link).'">Supprimer</a></p>';
                    }
                    else {
                        echo '<a href="../index.php?repondre='.urlencode($pseudo_orig).'">Répondre</a> <a href="./recuit.php?idBlabla='.urlencode($t['blID']).'">Recuiter</a></p>';
                    }
            echo '</li>';
        $t = mysqli_fetch_assoc($r);
    }
}
//_______________________________________________________________
/**
* Show user's stats
*
*
* @param array  $data       Array containing user's stats
*/
function gh_aff_user_stats(array $data): void {
    //$data = gh_html_proteger_sortie($data);

    $photoProfilPath = $data['usAvecPhoto'] == '1' ? '../upload/'. $data['usID'] .'.jpg' : '../images/anonyme.jpg';
        echo '<p class="userStats">',
                '<img src="', $photoProfilPath, '" alt="Photo de profil" class="photoProfil">',
                gh_html_a('./utilisateur.php?id='. $data['usID'], $data['usPseudo']), ' ', $data['usNom'], '<br>',
                gh_html_a('./blablas.php?id='. $data['usID'], $data['nbBlablas'] .' blabla'. ($data['nbBlablas'] > 1 ? 's' : '')), ' - ',
                gh_html_a('./mentions.php?id='. $data['usID'], $data['nbMentions'] .' mention'. ($data['nbMentions'] > 1 ? 's' : '')), ' - ',
                gh_html_a('./abonnes.php?id='. $data['usID'], $data['nbAbonnes'] .' abonné'. ($data['nbAbonnes'] > 1 ? 's' : '')), ' - ',
                gh_html_a('./abonnements.php?id='. $data['usID'], $data['nbAbonnements'] .' abonnement'. ($data['nbAbonnements'] > 1 ? 's' : '')),
             '</p>';
}
//_______________________________________________________________
/**
* Show user stats in the elements list
*
*
* @param mysqli_result  $r           Result of the SELECT query
* @param mysqli         $db          Database connection
* @param array          $data        Array containing user's stats
*/
function gh_aff_user_stats_list(mysqli_result $r, mysqli $db, array $data = array()): void {
    echo '<form action="./sabonner.php" method="post">';
    echo '<ul class="cardsList">';
    if(count($data) != 0){
        echo '<li class="noBackground">';
        gh_aff_user_stats($data);
        echo '</li>';
    }
    while ($t = mysqli_fetch_assoc($r)) {
        echo '<li>';
        gh_aff_user_stats(gh_sql_get_user_stats($db, $t['usID']));
        if ($t['usID'] != $_SESSION['usID']){
            echo '<div class="bouton_sabonner">';
            if (gh_sql_check_subscription($db, $_SESSION['usID'], $t['usID'])){
                echo '<input type="checkbox" name="desabonnement_'. $t['usID'] .'" id="desabonnement_'. $t['usID'] .'" value="'. $t['usID'] .'">',
                     '<label for="abonnement_'. $t['usID'] .'">Se désabonner</label>';
            }else {
                echo '<input type="checkbox" name="abonnement_'. $t['usID'] .'" id="abonnement_'. $t['usID'] .'" value="'. $t['usID'] .'">',
                     '<label for="abonnement_'. $t['usID'] .'">S\'abonner</label>';
            }
                echo '</div>';
        }
        echo '</li>';
    }
    echo '</ul><input id="validerAbonnement" type=submit name="btnValiderAbonnement" value="Valider"></form>';
}
//_______________________________________________________________
/**
     * Display a list of tends
     * 
     * @param mysqli_result $result
     * @param bool          $showHashTag Wether to show the hashtag or not. Shpw an ordoned list of tends if not.
     */
    function gh_aff_liste_tendances(mysqli_result $result, bool $showHashTag = false) {
        if($showHashTag == false){
            echo '<ol>';
        }else{
            echo '<ul>';
        }
        while($res = mysqli_fetch_assoc($result)){
            if($showHashTag == false){
                echo '<li class="li_tendances">';
            }
            else {
                echo '<li>';
            }
            if($showHashTag) {
                echo '#', gh_html_a('./tendances.php?hashtag='.$res['taID'], $res['taID']);
            }
            else {
                echo gh_html_a('./tendances.php?hashtag='.$res['taID'], $res['taID'].' ('.$res['nbTags'].')');
            }
            echo '</li>';
        }
        if($showHashTag == false){
            echo '</ol>';
        }else{
            echo    '<li>', gh_html_a('./tendances.php', 'Toutes les tendances'), '</li>',
                 '</ul>';
        }
    }
//_______________________________________________________________
/**
* Détermine si l'utilisateur est authentifié
*
* @global array    $_SESSION 
* @return bool     true si l'utilisateur est authentifié, false sinon
*/
function gh_est_authentifie(): bool {
    return  isset($_SESSION['usID']);
}

//_______________________________________________________________
/**
 * Termine une session et effectue une redirection vers la page transmise en paramètre
 *
 * Elle utilise :
 *   -   la fonction session_destroy() qui détruit la session existante
 *   -   la fonction session_unset() qui efface toutes les variables de session
 * Elle supprime également le cookie de session
 *
 * Cette fonction est appelée quand l'utilisateur se déconnecte "normalement" et quand une 
 * tentative de piratage est détectée. On pourrait améliorer l'application en différenciant ces
 * 2 situations. Et en cas de tentative de piratage, on pourrait faire des traitements pour 
 * stocker par exemple l'adresse IP, etc.
 * 
 * @param string    URL de la page vers laquelle l'utilisateur est redirigé
 */
function gh_session_exit(string $page = '../index.php'):void {
    session_destroy();
    session_unset();
    $cookieParams = session_get_cookie_params();
    setcookie(session_name(), 
            '', 
            time() - 86400,
            $cookieParams['path'], 
            $cookieParams['domain'],
            $cookieParams['secure'],
            $cookieParams['httponly']
        );
    header("Location: $page");
    exit();
}
//____________________Form fields verification___________________
//_______________________________________________________________
/**
 * Verify last name and first name
 *
 * - Check if it's not empty 
 * - Check if it's not too long
 * - Check if it doesn't contain HTML tags
 * - Check if it doesn't contain forbidden characters
 * 
 * @param string    $name        Last name
 * @param array    $errors       Array of errors
 */
function gh_verif_nom(string $name, array &$errors): void {
    if (empty($name)) {
        $errors[] = 'Le nom et le prénom doivent être renseignés.'; 
    }
    else {
        if (mb_strlen($name, 'UTF-8') > LMAX_NOMPRENOM){
            $errors[] = 'Le nom et le prénom ne peuvent pas dépasser ' . LMAX_NOMPRENOM . ' caractères.';
        }
        $noTags = strip_tags($name);
        if ($noTags != $name){
            $errors[] = 'Le nom et le prénom ne peuvent pas contenir de code HTML.';
        }
        else {
            if( !mb_ereg_match('^[[:alpha:]]([\' -]?[[:alpha:]]+)*$', $name)){
                $errors[] = 'Le nom et le prénom contiennent des caractères non autorisés.';
            }
        }
    }
}
//_______________________________________________________________
/**
 * Verify birth date
 *
 * - Check if it's not empty 
 * - Check if it's in the correct format (YYYY-MM-DD)
 * - Check if it's valid
 * - Check if user is older than AGE_MIN years and younger than AGE_MAX
 * 
 * @param string   $birthDate    Birth date
 * @param array    $errors       Array of errors
 */
function gh_verif_date_naissance(string $birthDate, array &$errors): void {
    if (empty($birthDate)){
        $errors[] = 'La date de naissance doit être renseignée.'; 
    }
    else{
        if( !mb_ereg_match('^\d{4}(-\d{2}){2}$', $birthDate)){ //vieux navigateur qui ne supporte pas le type date ?
            $errors[] = 'la date de naissance doit être au format "AAAA-MM-JJ".'; 
        }
        else{
            list($year, $month, $day) = explode('-', $birthDate);
            if (!checkdate($month, $day, $year)) {
                $errors[] = 'La date de naissance n\'est pas valide.'; 
            }
            else if (mktime(0,0,0,$month,$day,$year + AGE_MIN) > time()) {
                $errors[] = 'Vous devez avoir au moins '.AGE_MIN.' ans pour vous inscrire.'; 
            }
            else if (mktime(0,0,0,$month,$day,$year + AGE_MAX + 1) < time()) {
                $errors[] = 'Vous devez avoir au plus '.AGE_MAX.' ans pour vous inscrire.'; 
            }
        }
    }
}
//_______________________________________________________________
/**
 * Verify email
 *
 * - Check if it's not empty 
 * - Check if it's not too long
 * - Check if it's valid (syntax)
 * 
 * @param string   $email    Email address
 * @param array    $errors   Array of errors
 */
function gh_verif_email(string $email, array &$errors): void {
    if (empty($email)){
        $errors[] = 'L\'adresse mail ne doit pas être vide.'; 
    }
    else {
        if (mb_strlen($email, 'UTF-8') > LMAX_EMAIL){
            $errors[] = 'L\'adresse mail ne peut pas dépasser '.LMAX_EMAIL.' caractères.';
        }
        if(! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'L\'adresse mail n\'est pas valide.';
        }
    }
}
//_______________________________________________________________
/**
 * Verify passwords
 *
 * - Check if they're equal
 * - Check if they're not too short or too long
 * 
 * @param string   $passe1   Password
 * @param string   $passe2   Password confirmation
 * @param array    $errors   Array of errors
 */
function gh_verif_passe(string $passe1, string $passe2, array &$errors): void {
    if ($passe1 !== $passe2) {
        $errors[] = 'Les mots de passe doivent être identiques.';
    }
    $nb = mb_strlen($passe1, 'UTF-8');
    if ($nb < LMIN_PASSWORD || $nb > LMAX_PASSWORD){
        $errors[] = 'Le mot de passe doit être constitué de '. LMIN_PASSWORD . ' à ' . LMAX_PASSWORD . ' caractères.';
    }
}
//_______________________________________________________________
/**
 * Get all info about a user
 * 
 * @param mysqli   $mysqli    MySQLi object
 * @param int      $id        User's id
 * @return array              User's info
 */
function gh_sql_get_user_info(mysqli $db, int $id): array {
    $sql = "SELECT *
            FROM users
            WHERE usID = $id";
    $results = gh_bd_send_request($db, $sql);
    $data = mysqli_fetch_assoc($results);
    // if no data, return empty array
    if ($data === null) {
        return [];
    }
    return gh_html_proteger_sortie($data);
    
}
//_______________________________________________________________
/**
 * Get stats about a user (usID, usPseudo, usNom, usAvecPhoto, nbBlablas, nbMentions, nbAbonnes, nbAbonnements)
 * 
 * @param mysqli   $mysqli    MySQLi object
 * @param int      $id        User's id
 * @return array              User's stats
 */
function gh_sql_get_user_stats(mysqli $db, int $id): array {
    $sql = "SELECT usID, usPseudo, usNom, usAvecPhoto
            FROM users
            WHERE usId = $id
            UNION ALL
            SELECT COUNT(*), NULL, NULL, NULL
            FROM blablas
            WHERE blIDAuteur = $id
            UNION ALL
            SELECT COUNT(*), NULL, NULL, NULL
            FROM mentions
            WHERE meIDUser = $id
            UNION ALL
            SELECT COUNT(*), NULL, NULL, NULL
            FROM estabonne
            WHERE eaIDAbonne = $id
            UNION ALL
            SELECT COUNT(*), NULL, NULL, NULL
            FROM estabonne
            WHERE eaIDUser = $id";

    $results = gh_bd_send_request($db, $sql);
    $row = mysqli_fetch_array($results);

    // if no data, return empty array
    if ($row[0] == "0") {
        return [];
    }

    $data = array(
        'usID' => $row[0],
        'usPseudo' => $row[1],
        'usNom' => $row[2],
        'usAvecPhoto' => $row[3]
    );
    $row = mysqli_fetch_array($results);
    $data['nbBlablas'] = $row[0];
    $row = mysqli_fetch_array($results);
    $data['nbMentions'] = $row[0];
    $row = mysqli_fetch_array($results);
    $data['nbAbonnes'] = $row[0];
    $row = mysqli_fetch_array($results);
    $data['nbAbonnements'] = $row[0];
    return gh_html_proteger_sortie($data);
}
//_______________________________________________________________
/**
 * Get suggestions for current user
 * 
 * @param mysqli   $mysqli              Database connection
 * @param int      $nbSuggestions       Number of suggestions to get
 * @return mysqli_result                Current User suggestions
 */
function gh_sql_get_current_user_suggestions(mysqli $db, int $nbSuggestions = NB_SUGGESTIONS): mysqli_result {
    $sql = "((SELECT DISTINCT usID, usPseudo, usNom, usAvecPhoto
              FROM users INNER JOIN estabonne ON usID=eaIDAbonne
              WHERE eaIDUser IN (SELECT eaIDAbonne FROM estabonne WHERE eaIDuser=$_SESSION[usID])
              AND usID!=$_SESSION[usID]
              AND usID NOT IN (SELECT eaIDAbonne
                               FROM estabonne
                               WHERE eaIDUSer=$_SESSION[usID]))
              UNION
              (SELECT usID, usPseudo, usNom, usAvecPhoto FROM (SELECT *
                                                               FROM users INNER JOIN estabonne ON usID=eaIDAbonne
                                                               WHERE eaIDAbonne != $_SESSION[usID]
                                                               AND eaIDAbonne NOT IN (SELECT eaIDAbonne FROM estabonne WHERE eaIDUser = $_SESSION[usID])
                                                               GROUP BY eaIDAbonne
                                                               ORDER BY COUNT(*) DESC
                                                               LIMIT " . NB_MOST_POPULAR_USERS . "
                                                              ) as sz
              ))
              ORDER BY RAND()
              LIMIT " . $nbSuggestions . ";";

    $res = gh_bd_send_request($db, $sql);
    return $res;
}
//_______________________________________________________________
/**
 * Check if a user subscribed to another user
 * @param mysqli $db Database connection
 * @param int    $eaIDUser User's id
 * @param int    $eaIDAbonne id of the user to check if eaIDUser is subscribed to
 * @return bool  true if $eaIDUser is subscribed to $eaIDAbonne, false otherwise
 */
function gh_sql_check_subscription(mysqli $db, int $eaIDUser, int $eaIDAbonne): bool {
    $request = "SELECT eaIDAbonne
                    FROM estabonne
                    WHERE eaIDUser = '". $eaIDUser ."'
                    AND eaIDAbonne = '". $eaIDAbonne ."'";
    $result = gh_bd_send_request($db, $request);

    return (mysqli_num_rows($result) > 0);
}
