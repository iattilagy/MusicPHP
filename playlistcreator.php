<html>
<head>
<link rel="stylesheet" type="text/css" href="styles.css" media="screen" /> 
</head>
<body>
<h3><a href=playlists.php>Back to playlists</a>
<form action="" method="post" style="display:inline; float:right;">
</form>
</h3>
<tracks>
<?php   echo "<form action=\"\" method=post>";
    echo "Playlist title: <input type=\"text\" name=\"title\">";
    echo "<input type=\"submit\" name=\"createplaylist\" value=\"Create playlist\"><br>";
    $con = new mysqli("localhost", "music", "muse","music");
    if ($con->connect_error) {
        die("Connection failed: " . $con->connect_error);
    } 
    mysqli_query($con,"use music;");
    //Get track names and artist
    $tracks=mysqli_fetch_all(mysqli_query($con,"select tracks.id,artist.name,tracks.title from tracks join artist on tracks.artist_id=artist.id;"));   
    
    //Create playlist if submit is clicked
    if($_POST['createplaylist']!=NULL){
        $stmt = $con->prepare("insert into playlists (name) values (?);");
        $stmt->bind_param('s', $_POST['title']);
        $stmt->execute();
        $plid=mysqli_fetch_assoc(mysqli_query($con,"select id from playlists where name='".$_POST['title']."';"))['id'];
        $i=0;
        foreach($_POST['track'] as $item){
            $i++;
            mysqli_query($con,"insert into playlistlinker (playlist_id,track_id,number) values ('".$plid."','".$item."','".$i."');");
        }
    }
    
    //Create checkbox layout
    for($i=0;$i<count($tracks);$i++){ 
        echo "<trackradio><input onChange='this.form.submit();'";
        echo "type=\"checkbox\"";
        foreach($_POST['track'] as $item){
            if($item==$tracks[$i][0]){
                echo " checked ";
            }
        }
        echo "name=\"track[]\" value=\"".$tracks[$i][0]."\">".$tracks[$i][1].": <b>".$tracks[$i][2]."</b></trackradio>";
    }
    mysqli_close($con);
    echo "</form>";
?>
</tracks>
<body>
