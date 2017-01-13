<html>
<head>
<link rel="stylesheet" type="text/css" href="styles.css" media="screen" /> 
</head>
<body>
<albums>
<?php
    define("PICSDIR","albumCovers/");
    echo "<h3><a href=\"music.php\">Back to player</a></h3>";
    echo "<form action=\"\" method=post>";
    
    //Open connection
    $con = new mysqli("localhost", "music", "muse","music");
    if ($con->connect_error) {
        die("Connection failed: " . $con->connect_error);
    } 
    mysqli_query($con,"use music;");
    
    //Get album names and current album id from settings
    $albums=mysqli_fetch_all(mysqli_query($con,"select * from albums;"));   
    $currentid=mysqli_fetch_assoc(mysqli_query($con,"select value from settings where name='album'"))['value'];
        
    //Apply changes if radio button is clicked
    if(!empty($_POST) && $currentid!=$_POST['album']){
        $currentid=$_POST['album'];
        exec("killall play");
        mysqli_query($con,"UPDATE settings SET value='-1' WHERE name='playi';");  
    }
    
    //Create radio button divs
    for($i=0;$i<count($albums);$i++){
        echo "<albumradio><input onChange='this.form.submit();'";
        if($currentid==$albums[$i][0]) echo " checked ";
        if(file_exists(PICSDIR.str_replace(' ','',$albums[$i][1]).".jpg"))
            echo "type=\"radio\" name=\"album\" value=\"".$albums[$i][0]."\">".$albums[$i][1]."<img align=middle id=albumsmall src=".
            PICSDIR.str_replace(' ','',$albums[$i][1]).".jpg ></albumradio>";
        else
            echo "type=\"radio\" name=\"album\" value=\"".$albums[$i][0]."\">".$albums[$i][1]."</albumradio>";
    }
    
    //Write back changes and close
    mysqli_query($con,"UPDATE settings SET value='".$currentid."' WHERE name='album';");  
    mysqli_close($con);
    echo "</form>";
?>
</albums>
</head>
<body>
