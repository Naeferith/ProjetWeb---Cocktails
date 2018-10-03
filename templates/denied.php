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
			<h2 class="section-heading text-uppercase">Errerur 403 - Acces Interdit</h2>
		  </div>
		</div>
		<div class="form-group row">
			<div class="col-lg-12 text-center">
			  <a href="?view=accueil" class="btn btn-primary">Retour</a>
			</div>
		</div>
	</section>
</div>