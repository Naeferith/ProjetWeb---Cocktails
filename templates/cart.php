<?php

// Si la page est appelée directement par son adresse, on redirige en passant pas la page index
if (basename($_SERVER["PHP_SELF"]) != "index.php")
{
	header("Location:../index.php");
	die("");
}

// Pose qq soucis avec certains serveurs...
echo "<?xml version=\"1.0\" encoding=\"utf-8\" ?>";
?>

<div class="container">
	<section>		
		<div class="row">
		  <div class="col-lg-12 text-center">
			<h2 class="section-heading text-uppercase">Vos recettes favorites</h2>
			<?php
				$fav = (isset($_SESSION["tempFav"])) ? $_SESSION["tempFav"] : getAllFav($_SESSION['idUser']);
				$nbRecettes = count($fav);
				$indexR = 0;
				$parLigne = 5;
				for($i = 0; $i <= floor($nbRecettes / $parLigne); $i++) {
					echo '<div class="row">';
					for($j = 0; $j < $parLigne; $j++) {
						$indexR = $i*$parLigne + $j;
						if ($indexR >= $nbRecettes) break;
						echo '<div class="card reciepe-card" style="width: 12rem;">
								  <img class="card-img-top" src="'.retrievePhoto($fav[$indexR]["titre"]).'" alt="Card image cap">
								  <div class="card-body">
									<h5 class="card-title">'.$fav[$indexR]["titre"].'</h5>
									<a href="?view=recette&id='.$fav[$indexR]["idreciepe"].'" class="btn btn-primary">Voir la recette</a>
								  </div>
								</div>';
					}
					echo '</div>';
				}
			?>
		  </div>
		</div>
	</section>
</div>