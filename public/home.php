<?php
$body_class="home"; //aggiunto al body come classe 
$title_page="Home"; //title della pagina
$arr_roles=[]; //pagina accessibile a tutti
?>
<?php ob_start(); ?><style></style><?php $my_style = ob_get_clean(); ?>
<?php include '../src/header.php'; ?>

<h2>Welcome, <?php echo $_SESSION['email']; ?></h2>

<?php print_flash("msg"); ?>
<?php //print_errors($error); ?>

<p>Choose what you want to do.</p>
<ul>
    <li><a href="user_list.php">Users</a></li>
    <li><a href="logout.php">Logout</a></li>
</ul>


<?php ob_start(); ?>
<script>
    document.addEventListener("DOMContentLoaded", function(){
	
    });
</script>
<?php 
$my_script = ob_get_clean();
include '../src/footer.php';
