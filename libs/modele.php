<?php

/*
Dans ce fichier, on définit diverses fonctions permettant de récupérer des données utiles pour notre site. 
*/

include_once "maLibSQL.pdo.php";

///// ----- INSERTION BDD ----- /////

//Ajoute une recette aux favoris
function addFav($recette, $user) {
	$sql = "INSERT INTO panier(iduser, idreciepe) VALUES ('".$user."','".$recette."')";
	SQLInsert($sql);
}

//Ajoute un ingrédient
function addIngredient($nom, $path) {
	$sql = "INSERT INTO ingredients(nom, path) VALUES ('$nom','$path')";
	SQLInsert($sql);
}

//Ajoute une recette dans la BDD
function addReciepe($recette) {
	$sql = "INSERT INTO recettes(titre, ingredients, preparation, listIngredient) VALUES ('".addslashes($recette['titre'])."','".addslashes($recette['ingredients'])."', '".addslashes($recette['preparation'])."', '".addslashes(implode(";", $recette["index"]))."')";
	SQLInsert($sql);
}

//Ajoute un utilisateur à la base de données
function addUser($nom,$prenom,$email,$nom_user,$password,$tel,$adress,$zipcode,$city,$sexe,$birthdate){
	$birthdate = ($birthdate == '') ? 'NULL' : "'".$birthdate."'" ;
	$SQL="INSERT INTO utilisateurs(nom,prenom,pseudo,mail,password,tel,adress,zipcode,city,sexe,birthdate) VALUES('$nom','$prenom','$nom_user','$email','$password','$tel','$adress','$zipcode','$city','$sexe',$birthdate)";
	SQLInsert($SQL);
}

//Ajoute au panier, les recettes temporairement aimées
function mergeFavorites($tempFav, $id) {
	$currentFav = getAllFav($id);	//On récup les recettes deja aimées
	
	$tempFavID = array();
	$currentID = array();
	
	foreach($tempFav as $index => $recette) $tempFavID[] = $recette['idreciepe'];
	foreach($currentFav as $index => $recette) $currentID[] = $recette['idreciepe'];
	
	$union_array = array_merge($tempFavID, $currentID);
	
	$temp = array_diff($union_array, $currentID);
	foreach($temp as $index => $idr) addFav($idr, $id);
}

///// -----/INSERTION BDD ----- /////



///// ----- UPDATE BDD ----- /////

// Permet de remplacer le mot de passe de l'utilisateur désigné par $idUser par $pass
function changerPasse($idUser,$pass) {
	$SQL = "UPDATE utilisateurs SET password='$pass' WHERE iduser='$idUser'";
	SQLUpdate($SQL);
}

//Mets a jour les coordonnées utilisateur
function updateUser($nom,$prenom,$nom_user,$tel,$adress,$zipcode,$city,$sexe,$birthdate) {
	$subSql = ($nom == '') ? '': "nom='$nom', ";
	$subSql .= ($prenom == '') ? '': "prenom='$prenom', ";
	$subSql .= ($tel == '') ? '': "tel='$tel', ";
	$subSql .= ($adress == '') ? '': "adress='$adress', ";
	$subSql .= ($zipcode == '') ? '': "zipcode='$zipcode', ";
	$subSql .= ($city == '') ? '': "city='$city', ";
	$subSql .= ($sexe == '') ? '': "sexe='$sexe', ";
	$subSql .= ($birthdate == '') ? "birthdate=NULL ": "birthdate=$birthdate ";
	$SQL = "UPDATE utilisateurs SET ".$subSql." WHERE pseudo='$nom_user'";
	SQLUpdate($SQL);
}

///// -----/UPDATE BDD ----- /////


///// ----- DELETE BDD ----- /////

//Retire une recette des favoris
function removeFav($recette, $user) {
	$sql = "DELETE FROM panier WHERE iduser = ".$user." AND idreciepe = ".$recette."";
	SQLDelete($sql);
}

///// -----/DELETE BDD ----- /////



///// ----- GETTER ----- /////

//Algorithme de recherche avancée cad recherche a ingrédients multiple
//$is = array des noms d'ingrédients contenus
//$isnt = array des noms d'ingrédients non contenus
function advancedSearch($is, $isnt) {	
	$recetteIn = array();
	$recetteIsnt = array();
	$tempRecette = array();	
	if(count($is) == 0) {
		$SQL="SELECT * FROM recettes";
		$recetteIn = parcoursRs(SQLSelect($SQL));
	}
	else {
		foreach($is as $nomIngredient) {
			foreach(getReciepeByIngredient($nomIngredient) as $index => $recette) $recetteIn[] = $recette;
			foreach(getAllSons($nomIngredient) as $index => $ing) {
				foreach(getReciepeByIngredient($ing["nom"]) as $index => $recette) $recetteIn[] = $recette;
			}
			$tempRecette[] = $recetteIn;
			$recetteIn = array();
		}
	}
	
	//intersec 
	$jeej = array();
	foreach($tempRecette as $arrays) {
		if (count($recetteIn) == 0) $recetteIn = array_merge($recetteIn, $arrays);
		else {
			foreach($recetteIn as $index => $recette) {
				foreach($arrays as $i => $r) {
					if ($r["idreciepe"] == $recette["idreciepe"]) {
						$jeej[] = $r;
						break;
					}
				}
			}
			$recetteIn = $jeej;
			$jeej = array();
		}
	}
	
	foreach($isnt as $nomIngredient) {
		foreach(getReciepeByIngredient($nomIngredient) as $index => $recette) $recetteIsnt[] = $recette;
		foreach(getAllSons($nomIngredient) as $index => $ing) foreach(getReciepeByIngredient($ing["nom"]) as $index => $recette) $recetteIsnt[] = $recette;
	}
	
	//Removes dupes
	$recetteIn = array_unique($recetteIn, SORT_REGULAR);
	$recetteIsnt = array_unique($recetteIsnt, SORT_REGULAR);
	
	//diff
	foreach($recetteIsnt as $index => $recette) {
		foreach($recetteIn as $i => $r) {
			if ($r["idreciepe"] == $recette["idreciepe"]) {
				unset($recetteIn[$i]);
				break;
			}
		}
	}
	
	//scoring
	$scoredResult = array();
	foreach($recetteIn as $key => $recette) $scoredResult[$recette["idreciepe"]] = affectScore($is, $recette);
	
	$result = "";
	foreach($scoredResult as $idr => $score) $result = $result.$idr.'|'.$score.';';
	return rtrim($result, ";");
}

//Recupère toutes les recette favorite de l'utilisateur connecté
//$id = iduser
function getAllFav($id){
	return parcoursRs(SQLSelect("SELECT * FROM panier P, recettes R WHERE iduser = ".$id." AND P.idreciepe = R.idreciepe"));
}

//Récupère tout les parents (jusau'a la racine) d'un ingrédient.
//$path = le chemin absolu de l'ingrédient
function getAllParents($path) {
    if (!strstr($path, "."))
        return array();

    $tab = explode(".",$path);
    $i=count($tab);
    $parent = array();
    do{
        unset($tab[$i-1]);
        $path = implode(".",$tab);
        $sql = "SELECT * FROM ingredients WHERE path = '$path'";
        $parent[] = parcoursRs(SQLSelect($sql))[0];
        $i--;
    }
    while($i!=1);
    return array_reverse($parent);
}

//Récupère TOUT les fils d'un aliment (fils des fils des fils...)
//$ingredientName = nom de l'ingrédient
function getAllSons($ingredientName) {
    $tab = getSons($ingredientName);
    if (count($tab)!=0){
        foreach ($tab as $index => $value){
                $tab = array_merge($tab, getAllSons($value["nom"]));
        }
        return $tab;
    }
    else return array();
}

//Recupère la liste des recettes favories d'un utilisateur
//$id = iduser
function getFav($id){
	return parcoursRs(SQLSelect("SELECT idreciepe FROM panier WHERE iduser = ".$id.""));
}

//Récupère un ingredient selon son chemin (unique)
//$path = le chemin de absolu de l'ingredient
function getIngredient($path) {
	$sql = "SELECT * FROM ingredients WHERE path = '$path'";
	return parcoursRs(SQLSelect($sql));
}

//Recupere la liste de fils d'un ingredient. renvoie null si c'est une feuille.
//$hierarchie = array php contenant une arboressence
//$nom = nom de l'ingredient auquel on veut trouver ses fils
function getIngredientFils($hierarchie, $nom) {
	foreach($hierarchie as $key => $value) {	
		if ($key == $nom) {
			foreach ($value as $categorie => $ingredients) {				//...on parcours ses relations pere-fils
				if ($categorie == 'sous-categorie') return $ingredients;	//...si l'ingredient n'est pas une feuille, on retourne ses fils
			}
		}
	}
	return null;															//L'ingrédient est une feuille
}

//Renvoie l'adresse mail d'un utilisateur
//$id = idUtilisateur
function getMail($id) {
	if (is_string($id)) $id = intval($id);
	$SQL="SELECT mail FROM utilisateurs WHERE iduser='$id'";
	return SQLGetChamp($SQL);
}

//Récupère le parent direct selon un chemin
//$path = le chemin
function getParent($path) {
    $tab = explode(".",$path);
    unset($tab[count($tab)-1]);
    $path = implode(".",$tab);
    return getIngredient($path)[0];
}

//Récupère un array contenant les path d'un ingrédient
//$nom = le nom de l'ingrédient
function getPath($nom) {
	$nom = addslashes($nom);
	$sql = "SELECT path FROM ingredients WHERE nom = '$nom'";
	return parcoursRs(SQLSelect($sql));
}

//Recupere le nom de la racine
//$hierarchie = array php contenant une arboressence
function getRacineName($hierarchie) {	
	foreach($hierarchie as $key => $value) {										//Parcours des ingredients
		foreach ($value as $categorie => $innervalue) {								//Parcours des relations pere-fils
			if (count($value) == 1 && $categorie == 'sous-categorie') return $key;	//On trouve la racine et on renvoie la data
		}
	}
	return null;
}

//Recupère une recette par son id
//$id = idreciepe
function getReciepe($id){
	return parcoursRs(SQLSelect("SELECT * FROM recettes WHERE idreciepe = ".$id.""))[0];
}

//Récupère toutes les recettes contenant un ingrédient donné
//$ingredientName = nom de l'ingrédient
function getReciepeByIngredient($ingredientName) {
	$sql = "SELECT * FROM recettes";
	$recettes = parcoursRs(SQLSelect($sql));
	$return = array();
	foreach ($recettes as $index => $recette) {
		$ingredients = $recette['listIngredient'];
		foreach(explode(";", $ingredients) as $ingredient) {
			if ($ingredientName == $ingredient) {
				$return[] = $recette;
				break;
			}
		}
	}
	return $return;
}

//Récupère les fils directs d'un aliment
//$ingredientName = nom de l'ingrédient
function getSons($ingredientName) {
	$ingredientName = addslashes($ingredientName);
	$sql = "SELECT path FROM ingredients WHERE nom = '$ingredientName'";
	$path = SQLGetChamp($sql);
	$path = str_replace(".", "\\.", $path);
	$sql = "SELECT * FROM ingredients WHERE path REGEXP '^".$path."\.[0123456789]+$'";
	return parcoursRs(SQLSelect($sql));
}

//Renvoie le numéro de téléphone d'un utilisateur
//$id = idUtilisateur
function getTel($id) {
	if (is_string($id)) $id = intval($id);
	$SQL="SELECT tel FROM utilisateurs WHERE iduser='$id'";
	return SQLGetChamp($SQL);
}

//Renvoie le pseudo d'un utilisateur
//$id = idUtilisateur
function getUser($id) {
	if (is_string($id)) $id = intval($id);
	$SQL="SELECT pseudo FROM utilisateurs WHERE iduser='$id'";
	return SQLGetChamp($SQL);
}

//Récupère la photo d'un cocktail si elle existe, une image par defaut sinon.
//$reciepeName = le nom de la recette
function retrievePhoto($reciepeName) {
	$src = "img/Photos/";
	$ext = ".jpg";
	$default = "missing-image";
	
	$unwanted_array = array(    'Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
								'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U',
								'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c',
								'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o',
								'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y' );
	$reciepeName = strtr( $reciepeName, $unwanted_array );
	$reciepeName = str_replace(" ","_", $reciepeName);

	return (file_exists($src.ucfirst($reciepeName).$ext)) ? $src.ucfirst($reciepeName).$ext : $src.$default.$ext;
}

//Permet de retourner un tableau contenant les champs concernant un utilisateur désigné par son id $id_user
//$id_user = id utilisateur
function utilisateurParId($id_user){
	$sql = "SELECT * FROM utilisateurs WHERE iduser = '$id_user'";
	return parcoursRs(SQLSelect($sql));
}

//Permet de retourner un tableau contenant les champs concernant un utilisateur désigné par son pseudo $login
//$id_user = login utilisateur
function utilisateurParLogin($login){
	$sql = "SELECT * FROM utilisateurs WHERE pseudo = '$login'";
	return parcoursRs(SQLSelect($sql));
}

///// -----/GETTER ----- /////



///// ----- CHECK ----- /////

// Retourne true si le pseudo $login existe déjà dans la base de données, false sinon
function checkIfExists($login) {
	$SQL="SELECT iduser FROM utilisateurs WHERE pseudo='$login'";
	$Array=SQLSelect($SQL);
	return ($Array==false) ? false : true;
}

//Verifie si une recette est dans les favoris
function isFavorite($idUser, $idRecette){
	$sql = "SELECT idreciepe FROM panier WHERE iduser = ".$idUser." AND idreciepe = ".$idRecette."";
	$fav = parcoursRs(SQLSelect($sql));
	return count($fav);
}

// Vérifie si l'utilisateur est sur la page d'inscription/connexion
function isLoginPage(){
	return (isset($_GET["view"]) && ($_GET["view"] == "inscription" || $_GET["view"] == "connexion"));
}

// Vérifie si l'utilisateur est sur la page d'accueil
function isMainPage(){
	return (!isset($_GET["view"]) || $_GET["view"] == "accueil" );
}

//Vérifie si une chaine commence par une sous chaine
function startsWith($haystack, $needle){
    $length = strlen($needle);
    return (substr($haystack, 0, $length) === $needle);
}

//Vérifie les entrées de formulaire
function verifForm($data) {
	$email = filter_var($data['mail'], FILTER_VALIDATE_EMAIL);
	$pseudo = ctype_alnum($data['pseudo']);
	
	$email = ($email === false) ? false: true;
	$pseudo = ($pseudo === false) ? false: true;
	
	$result = $email && $pseudo;
	if (isset($data['tel']) && strlen($data['tel']) > 0 ) {
		$tel = preg_match("/^[0-9]{10}$/", $data['tel']);
		$tel = ($tel == 0) ? false: true;
		$result = $result && $tel;
	}
	if (isset($data['zipcode']) && strlen($data['zipcode']) > 0 ) {
		$zip = preg_match("/^[0-9]{5}$/", $data['zipcode']);
		$zip = ($zip == 0) ? false: true;
		$result = $result && $zip;
	}
	return $result;
}

function verifUserBdd($login,$passe){
// Vérifie l'identité d'un utilisateur 
// dont les identifiants sont passes en paramètre
// renvoie faux si user inconnu
// renvoie l'id de l'utilisateur si succès
	
	$SQL_pass="SELECT password FROM utilisateurs WHERE pseudo='$login'";
	$pass = SQLGetChamp($SQL_pass);

	if($pass == false) return false;
	
	if(password_verify($passe,$pass) == true){

		$SQL="SELECT iduser FROM utilisateurs WHERE pseudo='$login'";
		return SQLGetChamp($SQL);
	}
	else return false;
// si on avait besoin de plus d'un champ
// on aurait du utiliser SQLSelect
}

///// -----/CHECK ----- /////



//// ----- DIVERS ----- /////

//Retourne un score est compris entre 0 et 1 pour une recette en fonction des ingrédients selectionnés
function affectScore($baseIngredients, $recette) {
	//ratio pour fils recursif
	$ratio = 2;
	
	//indicateur de détection d'un ingrédient
	$flag = false;
	
	//On récupère la liste de la recette
	$ingredientsRecette = explode(";", $recette["listIngredient"]);
	
	$temp = array_diff($ingredientsRecette, $baseIngredients);
	
	//Si equivalence stricte
	if (count($temp) == 0) return 1;
	
	$temp = array();
	//On récup les paths
	foreach ($ingredientsRecette as $ingredientReel) $temp[$ingredientReel] = getPath($ingredientReel);
	$ingredientsRecette = $temp;
	$temp = array();
	
	foreach ($baseIngredients as $ingredientSelect) $temp[$ingredientSelect] = getPath($ingredientSelect);
	$baseIngredients = $temp;
	
	$score = 0;
	foreach ($ingredientsRecette as $nameIngReel => $pathsIngReel) {		//On parcours les ingrédients de la recette
		$flag = false;
		foreach ($baseIngredients as $nameIngSelect => $pathsIngSelect) {
			foreach($pathsIngReel as $index => $colPath) {
				foreach ($pathsIngSelect as $i => $cP) {
					if (startsWith($colPath['path'], $cP['path'])) {		//Si le path de l'ing select commence par celui de l' ing reel
						$filsEcart = count(explode('.', $colPath['path'])) - count(explode('.', $cP['path']));
						$score += ($filsEcart == 0) ? 1 : 1/($filsEcart * $ratio);
						$flag = true;
						break;
					}
				}
				if ($flag) break;
			}
			if ($flag) break;
		}
	}
	return round($score / count($ingredientsRecette), 2);
}

//Parcours donnees.ico.php pour inserer les ingredients dans la bdd avec leur arboressence.
function parseData($dataTable, $nom, $path) {
	//$dataTable est le tableau de données
	//$hierarchie est un tableau des fils d'un ingredient
	//Si $hierarchie est une feuille, on remonte
	addIngredient(addslashes($nom), $path);				//On ajoute l'ingredient
	$hierarchie = getIngredientFils($dataTable, $nom);
	if (is_null($hierarchie)) return;
	
	foreach ($hierarchie as $index => $ingredient) {	//Pour chaque ingredient fils
		$localpath = $path.'.'.$index;					//On lui affecte le bon chemin
		parseData($dataTable, $ingredient, $localpath);	//On parcours ses fils
	}
}

function recommendation($baseIngredients){
    $chemin=$nom="";
    $max=0;
    foreach ($baseIngredients as $ingredientSelect) $temp[$ingredientSelect] = getPath($ingredientSelect);

    foreach ($temp as $nameIngSelect => $pathsIngSelect){
        foreach ($pathsIngSelect as $index => $path) {
            if (count(explode(".",$path['path'])) > $max) {
                $max = count(explode(".",$path['path']));
                $nom = $nameIngSelect;
                $chemin = $path['path'];
            }
        }
    }
    $baseIngredients[array_search($nom, $baseIngredients)] = getParent($chemin)['nom'];
    return $baseIngredients;
}
//// -----/DIVERS ----- /////
?>
