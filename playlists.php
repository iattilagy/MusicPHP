<html>
<head>
<link rel="stylesheet" type="text/css" href="styles.css" media="screen" /> 
</head>
<body>
<albums>
<?php
    define("PICSDIR","albumCovers/");
    echo "<h3><a href=\"music.php\">Back to player</a>";
    echo "<a href=\"playlistcreator.php\" style=\"float:right\">New playlist</a></h3>";
    echo "<form action=\"\" method=post>";
    $con = new mysqli("localhost", "music", "muse","music");
    if ($con->connect_error) {
        die("Connection failed: " . $con->connect_error);
    } 
    mysqli_query($con,"use music;");
    //Get playlists and current playlistid
    $albums=mysqli_fetch_all(mysqli_query($con,"select * from playlists;"));   
    $currentid=mysqli_fetch_assoc(mysqli_query($con,"select value from settings where name='playlist'"))['value'];
        
    //Apply changes if radio button is clicked
    if(!empty($_POST) && $currentid!=$_POST['playlist']){
        $currentid=$_POST['playlist'];
        exec("killall play");
        mysqli_query($con,"UPDATE settings SET value='-1' WHERE name='playi';");  
        mysqli_query($con,"UPDATE settings SET value='0' WHERE name='album';");  
    }
    
    //Create radio button layout
    for($i=0;$i<count($albums);$i++){
        echo "<playlistradio><input onChange='this.form.submit();'";
        if($currentid==$albums[$i][0]) echo " checked ";
        echo "type=\"radio\" name=\"playlist\" value=\"".$albums[$i][0]."\">".$albums[$i][1]."</playlistradio>";
    }
    
    //Write back settings
    mysqli_query($con,"UPDATE settings SET value='".$currentid."' WHERE name='playlist';");  
    mysqli_close($con);
    echo "</form>";
?>
</albums>
</head>
<body>
