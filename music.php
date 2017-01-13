<html>
<head>
    <meta http-equiv="refresh" content="2" >
    <?php
        //Directory of album covers
        define("PICSDIR","albumCovers/");
        //Directory where music files are stored (FLAC only)
        define("MUSICDIR","/home/attila/Music/");
    ?>
    <link rel="stylesheet" type="text/css" href="styles.css" media="screen" /> 
</head>
<body>
    <main>
        <?php 
        
        //Open connection
        $con = new mysqli("localhost", "music", "muse","music");
        if ($con->connect_error) {
            die("Connection failed: " . $con->connect_error);
        } 
        mysqli_query($con,"use music;");
        
        //Get album and playlist id from settings
        $albumid=mysqli_fetch_assoc(mysqli_query($con,"select value from settings where name='album'"))['value'];
        $playlistid=mysqli_fetch_assoc(mysqli_query($con,"select value from settings where name='playlist'"))['value'];
        
        //If album id is 0 and playlist id not we are playing a playlist
        if($albumid=='0' && $playlistid!='0'){
            $pl=mysqli_fetch_all(mysqli_query($con,"select tracks.id from tracks join playlistlinker on playlistlinker.track_id=tracks.id join playlists on playlistlinker.playlist_id=playlists.id where playlists.id='".$playlistid."' order by number;"));
        } 
        //If album id is not 0 we are playing an album
        else if ($album!='0'){
            $pl=mysqli_fetch_all(mysqli_query($con,"select tracks.id from tracks join albums on tracks.album_id=albums.id where albums.id='".$albumid."' order by tracknumber;"));
        } 
        //If both are 0 party mode was triggered so generating new partymode table
        if($albumid=='0' && $playlistid=='0') {
            $pl=mysqli_fetch_all(mysqli_query($con,"select id from tracks;"));
            shuffle($pl);
            mysqli_query($con,"delete from partymode");
            for($i=0;$i<count($pl);$i++)
                mysqli_query($con,"insert into partymode (id) values('".$pl[$i][0]."')");
            $albumid=-1;
            $playlistid=-1;
            mysqli_query($con,"UPDATE settings SET value='".$albumid."' WHERE name='album';");  
            mysqli_query($con,"UPDATE settings SET value='".$playlistid."' WHERE name='playlist';");  
        }
        //Party mode is enabled and table is done so getting music id array from partymode
        if($albumid=='-1' && $playlistid=='-1') {
            $pl=mysqli_fetch_all(mysqli_query($con,"select id from partymode;"));
        }
        
        //Remove unneeded second dimension
        for($i=0;$i<count($pl);$i++)
            $playlist[$i]=$pl[$i][0];
        
        //Get settings values
        $playi=mysqli_fetch_assoc(mysqli_query($con,"select value from settings where name='playi'"))['value'];        
        $playing=mysqli_fetch_assoc(mysqli_query($con,"select value from settings where name='playing'"))['value'];
        
        //If playing is unset it should be true
        if($playing != "true" && $playing != "false"){
            $playing=true;
        }
        
        //Going to previous track
        if($_POST['prev']=="prev"){
            exec("killall play");
            $playi--;
            usleep(500); //Wait until play is terminated
            $jumpingback=true;
        }
        
        //Going to next track
        if($_POST['next']=="next"){
            exec("killall play"); //Auto jump occurs if play is terminated
        }
        
        //Disable playing
        if($_POST['stop']=="stop"){
                $playing="false";
                exec("killall play");
        }
        
        //reenable playing
        if($_POST['stop']=="play"){
                $playing="true";
        }
         
        //restart playlist
        if($playi===NULL || $playi>=count($playlist)){
            $playi=-1;
        }
        
        //Start play if needed
        if($playing=="true"){
            if($jumpingback || strpos(exec("ps -e | grep play"),"pts")===FALSE){
                if(!$jumpingback && $_POST['stop']!="play") $playi++;
                $filename = mysqli_fetch_assoc(mysqli_query($con,"Select filename from tracks where id='".$playlist[$playi]."';"))['filename'];
                system("rm screenlog.0");
                $play="screen -dmSL music play '".MUSICDIR.$filename."'";
                system($play);
            }
        }
        
        //Get track info
        $artist=mysqli_fetch_assoc(mysqli_query($con,"Select name from artist join tracks on artist.id=tracks.artist_id where tracks.id='".$playlist[$playi]."';"))['name'];
        echo "<h4>".$artist.": ";
        $albumarray=mysqli_fetch_assoc(mysqli_query($con,"Select album_title,year,genre from albums join tracks on albums.id=tracks.album_id where tracks.id='".$playlist[$playi]."';"));
        echo $albumarray['album_title']."</h4>"; 
        echo "<h6>".$albumarray['year']." ";
        echo $albumarray['genre']."</h6>";
        $title=mysqli_fetch_assoc(mysqli_query($con,"Select title from tracks where id='".$playlist[$playi]."';"))['title'];
        echo "<h1>".$title."</h1>";
        echo "<img id=\"albumimg\" "; 
        $album=str_replace(' ',  '', $albumarray['album_title']);  
        //Use album cover from PICSDIR if available, if not try falling back on online sources
        if(file_exists(PICSDIR.$album.".jpg")){
            echo "src=".PICSDIR.$album.".jpg >";
        } else {         
            $file="http://api.chartlyrics.com/apiv1.asmx/SearchLyricDirect?artist=".$artist."&song=".$title;
            $file=str_replace(' ', '+', $file);
            $xmldata=file_get_contents($file);
            $xml=simplexml_load_string($xmldata);
            echo "src=".$xml->LyricCovertArtUrl.">";
        }
        echo "<br><small>";
        $sampleandbit=mysqli_fetch_assoc(mysqli_query($con,"Select sampling,bitrate from tracks where id='".$playlist[$playi]."';"));
        echo $sampleandbit['sampling']." ".$sampleandbit['bitrate']; 
        echo "<br></small>"; 
        $file = "/srv/www/htdocs/screenlog.0";
        $data =  file_get_contents($file);
        $end=strrpos($data,"]");
        $start=strrpos($data,"%")-4;
        echo "<progress max=\"100\" value=\"".substr($data,$start,2).
        "\"></progress>";
        
        //Create buttons layout
        echo "<buttons>
        <form class=\"buttons\" method=\"post\">
        <input class=\"submit\" type=\"submit\" name=\"prev\" value=\"prev\" />
        <input class=\"submit\" type=\"submit\" name=\"stop\" value=\"";
        if($playing=="true")
            echo "stop";
        else
            echo "play";
        
        echo "\" /><input class=\"submit\" type=\"submit\" name=\"next\" value=\"next\" />
        </form>
        </buttons>";
        
        //Get lyrics from chartlyrics
        echo "</main> <lyrics>";
        $file="http://api.chartlyrics.com/apiv1.asmx/SearchLyricDirect?artist=".$artist."&song=".$title;
        $file=str_replace(' ', '+', $file);
        $xmldata=file_get_contents($file);
        $xml=simplexml_load_string($xmldata);
        echo nl2br($xml->Lyric);    
        
        //Write back settings values
        mysqli_query($con,"UPDATE settings SET value='".$playi."' WHERE name='playi';");  
        mysqli_query($con,"UPDATE settings SET value='".$playing."' WHERE name='playing';");
        
        echo "</lyrics> <playlist>";
        
        //echo partymode if it is enabled
        if($albumid=='-1' && $playlistid=='-1') {
            echo "<h1>Party mode</h1>";
        }
        
        //Create links and playlist
        echo "<h3><a href=\"albums.php\">Select album </a>
                  <a href=\"playlists.php\" style=\"float:right\">Select playlist</a> <br><br><br>
                  <a href=\"partymode.php\" style=\"float:center\">Enable partymode</a>
                  </h3>";
        
        echo "<ul>";
        for($i=0;$i<count($playlist);$i++){
            echo "<li ";
            if($i==$playi) echo "style=\"background:#1C213C\"";
            echo " >".mysqli_fetch_assoc(mysqli_query($con,"Select name from artist join tracks on artist.id=tracks.artist_id where tracks.id='".$playlist[$i]."';"))['name'].": ".mysqli_fetch_assoc(mysqli_query($con,"Select title from tracks where id='".$playlist[$i]."';"))['title']."</li>";
        }
        echo "</ul>";
        
        mysqli_close($con);   
        
    ?>
    </playlist>
</head>
<body>
