<?php
//Enables partymode and redirects to music.php
$con = new mysqli("localhost", "music", "muse","music");
if ($con->connect_error) {
   die("Connection failed: " . $con->connect_error);
} 
mysqli_query($con,"use music;");
exec("killall play");
mysqli_query($con,"UPDATE settings SET value='-1' WHERE name='playi';");
mysqli_query($con,"UPDATE settings SET value='0' WHERE name='album';");  
mysqli_query($con,"UPDATE settings SET value='0' WHERE name='playlist';");  
mysqli_close($con);   
header("Location: music.php");
die();
?>
