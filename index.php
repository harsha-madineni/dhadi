<script type="text/javascript" src="jquery.min.js"></script>
<?php
require_once "pdo.php";
session_start();
$curr_time = time();
$table_name_stmt = $pdo->prepare( "SELECT gameid FROM gameids WHERE
                    p1_last_seen < ".($curr_time - 8));
$table_name_stmt->execute();
$table_names = $table_name_stmt->fetchAll();
//echo "<p>".$table_names[1][0]."</p>";
//print_r($table_names);
foreach ($table_names as $i) {
  try {
    $drop_game_table_sql = "DROP TABLE game".$i[0];
    $pdo->exec($drop_game_table_sql);
  }
  catch(Exception $e) {
  }
}
$pdo->exec("USE main;");
$clean_gameids_sql = "INSERT INTO dead_gameids (gameid, creator,
                      opponent, time_created) SELECT gameid,
                      creator, opponent, time FROM gameids WHERE
                      p1_last_seen < ".($curr_time - 300)."; DELETE FROM
                      gameids WHERE p1_last_seen < ".($curr_time - 300).";";
$pdo->exec($clean_gameids_sql);

if ( isset($_SESSION['error']) ) {
    echo '<p style="color:red">'.$_SESSION['error']."</p>\n";
}
if( isset($_POST['submit']) ) {

    if( strlen($_POST['name']) < 1 && strlen($_POST['oldgcode']) < 1
    && strlen($_POST['newgcode']) < 1 ) {
        $_SESSION['error'] = 'Required fields are not filled';
        header("Location: index.php");
        return;
    }

    elseif( strlen($_POST['name']) < 1 ) {
        $_SESSION['error'] = 'Player name not filled';
        header("Location: index.php");
        return;
    }

    elseif( strlen($_POST['oldgcode']) < 1 && strlen($_POST['newgcode']) < 1 ) {
        $_SESSION['error'] = 'Enter game code';
        header("Location: index.php");
        return;
    }

    elseif( strlen($_POST['oldgcode']) > 0 && strlen($_POST['newgcode']) > 0 ) {
        $_SESSION['error'] = 'Enter only one game code';
        header("Location: index.php");
        return;
    }

    else{
      $player_name = $_POST['name'];
      echo '<script> var player_name = "'.$player_name.'"; </script>';
      if( strlen($_POST['oldgcode']) > 0 ) {
          $gcode = $_POST['oldgcode'];
          $_SESSION['gcode'] = $gcode;
          $gtype = 'old';
          $player = 2;
          echo '<script> var player = 2; </script>';
          echo '<script> var gcode = "'.$gcode.'"; </script>';

          $stmt = $pdo->prepare("SELECT * FROM gameids WHERE gameid = :xyz");
          $stmt->execute(array(":xyz" => $gcode));
          $row = $stmt->fetch(PDO::FETCH_ASSOC);
          if ( $row === false ) {
              $_SESSION['error'] = "Game code doesn't exist";
              header( 'Location: index.php' ) ;
              return;
          }
          else {
            $add_name_sql = "UPDATE gameids SET opponent = :name
                             WHERE gameid = ".$gcode;
            $stmt = $pdo->prepare($add_name_sql);
            $stmt->execute(array(
              ':name' => $_POST['name']
            ));
          }
      }
      else{
          $gcode = $_POST['newgcode'];
          $_SESSION['gcode'] = $gcode;
          $gtype = 'new';
          $player = 1;
          echo '<script> var player = 1; </script>';
          echo '<script> var gcode = "'.$gcode.'"; </script>';

          $stmt = $pdo->prepare("SELECT * FROM gameids where gameid = :xyz");
          $stmt->execute(array(":xyz" => $gcode));
          $row = $stmt->fetch(PDO::FETCH_ASSOC);
          if ( $row !== false) {
              $_SESSION['error'] = "Game code already exists";
              header( 'Location: index.php' ) ;
              return;
          }

          $new_table_sql = "CREATE TABLE game".$gcode." (
                              ID INTEGER NOT NULL AUTO_INCREMENT KEY,
                              Player int,
                              TokenId int,
                              NewPosId int);";
          $pdo->exec($new_table_sql);
          $add_entry_sql = "INSERT INTO gameids (gameid, creator)
                              VALUES (:gameid, :creator)";
          $stmt = $pdo->prepare($add_entry_sql);
          $stmt->execute(array(
              ':gameid' => $gcode,
              ':creator' => $player_name));
      }
    }

}
if( isset($_SESSION['error']) || !isset($_POST['submit']) ) {
    echo '<form method="post">
    <label for="name">Enter player Name:</label>
    <input type="text" id="name" name="name" maxlength="20">
    <p><b>Enter game code to:</b></p>
    <label for="oldgcode">Join existing game:</label>
    <input type="number" min="1000" oninput="javascript: if (this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);" maxlength="4" id="oldgcode" name="oldgcode">
    <p><b>Or</b></p>
    <label for="newgcode">Create new game:</label>
    <input type="number" min="1000" oninput="javascript: if (this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);" maxlength="4" id="newgcode" name="newgcode"><br>
    <input type="submit" name="submit" value="Go">
    </form>';
    unset($_SESSION['error']);
}
?>
<style>
  :root {
    --lw1: 1vmin;
    --ll1: 45vmin;
    --x1: calc(50vw - var(--ll1));
    --y1: 0vmin;

    --lw2: var(--lw1);
    --ll2: calc(var(--ll1)*2/3);
    --x2: calc(var(--x1) + var(--ll1)/3);
    --y2: calc(var(--y1) + var(--ll1)/3);

    --lw3: var(--lw1);
    --ll3: calc(var(--ll1)/3);
    --x3: calc(var(--x1) + var(--ll1)*2/3);
    --y3: calc(var(--y1) + var(--ll1)*2/3);

    --cd: calc((50vw - var(--ll1))/7);
    --cxdif: calc(var(--cd) + var(--cd)/3);
    --p1color: rgb(51, 153, 255);
    --p2color: rgb(51, 255, 51);
    --ct1: calc(var(--cd)/2);
    --cl1: calc(var(--x1)/8 + var(--cd)/2);
    --cl2: calc(var(--cl1) + var(--x1) + 2*var(--ll1));
    --ct3: calc(var(--ct1) + var(--ll1));

    --pd: var(--cd);
    --pdisp: calc((var(--pd) - var(--lw1))/2);

  }

  @media(max-width: 160vmin){
    :root{
      --ll1: 45vmin;

      --cd: calc(var(--ll1)/6);
      --ct1: calc(var(--y1) + 2*var(--ll1) + var(--cd));
      --cl1: calc(var(--x1) + (var(--ll1) - var(--cd) - 3*var(--cxdif))/2);
      --cl2: calc(var(--x1) + var(--ll1) + (var(--ll1) - var(--cd) - 3*var(--cxdif))/2);
      --ct3: calc(var(--ct1) + var(--cd) + 3*var(--cxdif));
    }
  }

  :root{
    --dct1: calc(var(--ct3) + var(--cxdif));
    --dcl1: var(--cl1);
    --dct2: calc(var(--ct3) + var(--cxdif));
    --dcl2: calc(var(--cl1) + var(--cxdif));
    --dct3: calc(var(--ct3) + var(--cxdif));
    --dcl3: calc(var(--cl1) + 2*var(--cxdif));
    --dct4: calc(var(--ct3) + var(--cxdif));
    --dcl4: calc(var(--cl1) + 3*var(--cxdif));
    --dct5: calc(var(--ct3) + 2*var(--cxdif));
    --dcl5: calc(var(--cl1));
    --dct6: calc(var(--ct3) + 2*var(--cxdif));
    --dcl6: calc(var(--cl1) + var(--cxdif));
    --dct7: calc(var(--ct3) + 2*var(--cxdif));
    --dcl7: calc(var(--cl1) + 2*var(--cxdif));
    --dct8: calc(var(--ct3) + 2*var(--cxdif));
    --dcl8: calc(var(--cl1) + 3*var(--cxdif));
    --dct9: calc(var(--ct3) + 3*var(--cxdif));
    --dcl9: calc(var(--cl1));
    --dct10: calc(var(--ct3) + 3*var(--cxdif));
    --dcl10: calc(var(--cl1) + var(--cxdif));
    --dct11: calc(var(--ct3) + 3*var(--cxdif));
    --dcl11: calc(var(--cl1) + 2*var(--cxdif));
    --dct12: calc(var(--ct3) + var(--cxdif));
    --dcl12: var(--cl2);
    --dct13: calc(var(--ct3) + var(--cxdif));
    --dcl13: calc(var(--cl2) + var(--cxdif));
    --dct14: calc(var(--ct3) + var(--cxdif));
    --dcl14: calc(var(--cl2) + 2*var(--cxdif));
    --dct15: calc(var(--ct3) + var(--cxdif));
    --dcl15: calc(var(--cl2) + 3*var(--cxdif));
    --dct16: calc(var(--ct3) + 2*var(--cxdif));
    --dcl16: calc(var(--cl2));
    --dct17: calc(var(--ct3) + 2*var(--cxdif));
    --dcl17: calc(var(--cl2) + var(--cxdif));
    --dct18: calc(var(--ct3) + 2*var(--cxdif));
    --dcl18: calc(var(--cl2) + 2*var(--cxdif));
    --dct19: calc(var(--ct3) + 2*var(--cxdif));
    --dcl19: calc(var(--cl2) + 3*var(--cxdif));
    --dct20: calc(var(--ct3) + 3*var(--cxdif));
    --dcl20: calc(var(--cl2));
    --dct21: calc(var(--ct3) + 3*var(--cxdif));
    --dcl21: calc(var(--cl2) + var(--cxdif));
    --dct22: calc(var(--ct3) + 3*var(--cxdif));
    --dcl22: calc(var(--cl2) + 2*var(--cxdif));
  }

  /*positions*/
  :root{
    --pos1t: calc(var(--y1) + var(--lw1)/2);
    --pos1l: calc(var(--x1) + var(--lw1)/2);
    --pos2t: calc(var(--y1) + var(--lw1)/2);
    --pos2l: calc(var(--x1) + var(--ll1));
    --pos3t: calc(var(--y1) + var(--lw1)/2);
    --pos3l: calc(var(--x1) + 2*var(--ll1));
    --pos4t: calc(var(--y1) + var(--ll1));
    --pos4l: calc(var(--x1) + 2*var(--ll1));
    --pos5t: calc(var(--y1) + 2*var(--ll1) - var(--lw1)/2);
    --pos5l: calc(var(--x1) + 2*var(--ll1));
    --pos6t: calc(var(--y1) + 2*var(--ll1) - var(--lw1)/2);
    --pos6l: calc(var(--x1) + var(--ll1));
    --pos7t: calc(var(--y1) + 2*var(--ll1) - var(--lw1)/2);
    --pos7l: calc(var(--x1) + var(--lw1)/2);
    --pos8t: calc(var(--y1) + var(--ll1) - var(--lw1)/2);;
    --pos8l: calc(var(--x1) + var(--lw1)/2);
    --pos9t: calc(var(--y2) + var(--lw1)/2);
    --pos9l: calc(var(--x2) + var(--lw1)/2);
    --pos10t: calc(var(--y2) + var(--lw1)/2);
    --pos10l: calc(var(--x2) + var(--ll2));
    --pos11t: calc(var(--y2) + var(--lw1)/2);
    --pos11l: calc(var(--x2) + 2*var(--ll2) - var(--lw1)/2);
    --pos12t: calc(var(--y2) + var(--ll2));
    --pos12l: calc(var(--x2) + 2*var(--ll2) - var(--lw1)/2);
    --pos13t: calc(var(--y2) + 2*var(--ll2) + var(--lw1)/2);
    --pos13l: calc(var(--x2) + 2*var(--ll2) - var(--lw1)/2);
    --pos14t: calc(var(--y2) + 2*var(--ll2) + var(--lw1)/2);
    --pos14l: calc(var(--x2) + var(--ll2));
    --pos15t: calc(var(--y2) + 2*var(--ll2) + var(--lw1)/2);
    --pos15l: calc(var(--x2) + var(--lw1)/2);
    --pos16t: calc(var(--y2) + var(--ll2) - var(--lw1)/2);
    --pos16l: calc(var(--x2) + var(--lw1)/2);
    --pos17t: calc(var(--y3) + var(--lw1)/2);
    --pos17l: calc(var(--x3) + var(--lw1)/2);
    --pos18t: calc(var(--y3) + var(--lw1)/2);
    --pos18l: calc(var(--x3) + var(--ll3));
    --pos19t: calc(var(--y3) + var(--lw1)/2);
    --pos19l: calc(var(--x3) + 2*var(--ll3) - var(--lw1)/2);
    --pos20t: calc(var(--y3) + var(--ll3));
    --pos20l: calc(var(--x3) + 2*var(--ll3) - var(--lw1)/2);
    --pos21t: calc(var(--y3) + 2*var(--ll3) + var(--lw1)/2);
    --pos21l: calc(var(--x3) + 2*var(--ll3) - var(--lw1)/2);
    --pos22t: calc(var(--y3) + 2*var(--ll3) + var(--lw1)/2);
    --pos22l: calc(var(--x3) + var(--ll3));
    --pos23t: calc(var(--y3) + 2*var(--ll3) + var(--lw1)/2);
    --pos23l: calc(var(--x3) + var(--lw1)/2);
    --pos24t: calc(var(--y3) + var(--ll3) - var(--lw1)/2);
    --pos24l: calc(var(--x3) + var(--lw1)/2);
  }

  #game{
    position: relative;
  }

  #top{
    height: calc(50vmin - var(--ll1));
  }
</style>

<!--lines-->
<style>
  .line{
    position: absolute;
    background-color: black;
  }

  #l1{
    height: var(--lw1);
    width: var(--ll1);
    top: var(--y1);
    left: var(--x1);
  }

  #l2{
    height: var(--lw1);
    width: var(--ll1);
    top: var(--y1);
    left: calc(var(--x1) + var(--ll1));
  }

  #l3{
    height: var(--ll1);
    width: var(--lw1);
    top: calc(var(--y1));
    left: calc(var(--x1) + 2*var(--ll1) - var(--lw1)/2);
  }

  #l4{
    height: var(--ll1);
    width: var(--lw1);
    top: calc(var(--y1) + var(--ll1));
    left: calc(var(--x1) + 2*var(--ll1) - var(--lw1)/2);
  }

  #l5{
    height: var(--lw1);
    width: var(--ll1);
    top: calc(var(--y1) + 2*var(--ll1) - var(--lw1) + 0.1vmin);
    left: calc(var(--x1) + var(--ll1));
  }

  #l6{
    height: var(--lw1);
    width: var(--ll1);
    top: calc(var(--y1) + 2*var(--ll1) - var(--lw1) + 0.1vmin);
    left: var(--x1);
  }

  #l7{
    height: var(--ll1);
    width: var(--lw1);
    top: calc(var(--y1) + var(--ll1));
    left: var(--x1);
  }

  #l8{
    height: var(--ll1);
    width: var(--lw1);
    top: calc(var(--y1));
    left: var(--x1);
  }

  #l9{
    height: var(--lw2);
    width: var(--ll2);
    top: var(--y2);
    left: var(--x2);
  }

  #l10{
    height: var(--lw2);
    width: var(--ll2);
    top: var(--y2);
    left: calc(var(--x2) + var(--ll2));
  }

  #l11{
    height: var(--ll2);
    width: var(--lw2);
    top: calc(var(--y2));
    left: calc(var(--x2) + 2*var(--ll2) - var(--lw2));
  }

  #l12{
    height: var(--ll2);
    width: var(--lw2);
    top: calc(var(--y2) + var(--ll2));
    left: calc(var(--x2) + 2*var(--ll2) - var(--lw2));
  }

  #l13{
    height: var(--lw2);
    width: var(--ll2);
    top: calc(var(--y2) + 2*var(--ll2));
    left: calc(var(--x2) + var(--ll2));
  }

  #l14{
    height: var(--lw2);
    width: var(--ll2);
    top: calc(var(--y2) + 2*var(--ll2));
    left: var(--x2);
  }

  #l15{
    height: var(--ll2);
    width: var(--lw2);
    top: calc(var(--y2) + var(--ll2));
    left: var(--x2);
  }

  #l16{
    height: var(--ll2);
    width: var(--lw2);
    top: calc(var(--y2));
    left: var(--x2);
  }

  #l17{
    height: var(--lw3);
    width: var(--ll3);
    top: var(--y3);
    left: var(--x3);
  }

  #l18{
    height: var(--lw3);
    width: var(--ll3);
    top: var(--y3);
    left: calc(var(--x3) + var(--ll3));
  }

  #l19{
    height: var(--ll3);
    width: var(--lw3);
    top: calc(var(--y3));
    left: calc(var(--x3) + 2*var(--ll3) - var(--lw3) + 0.1vmin);
  }

  #l20{
    height: var(--ll3);
    width: var(--lw3);
    top: calc(var(--y3) + var(--ll3));
    left: calc(var(--x3) + 2*var(--ll3) - var(--lw3) + 0.1vmin);
  }

  #l21{
    height: var(--lw3);
    width: var(--ll3);
    top: calc(var(--y3) + 2*var(--ll3));
    left: calc(var(--x3) + var(--ll3));
  }

  #l22{
    height: var(--lw3);
    width: var(--ll3);
    top: calc(var(--y3) + 2*var(--ll3));
    left: var(--x3);
  }

  #l23{
    height: var(--ll3);
    width: var(--lw3);
    top: calc(var(--y3) + var(--ll3));
    left: var(--x3);
  }

  #l24{
    height: var(--ll3);
    width: var(--lw3);
    top: calc(var(--y3));
    left: var(--x3);
  }

  #l25{
    height: calc(var(--ll1)*2/3);
    width: var(--lw1);
    top: calc(var(--y1));
    left: calc(var(--x1) + var(--ll1) - var(--lw1)/2);
  }

  #l26{
    height: var(--lw1);
    width: calc(var(--ll1)*2/3);
    top: calc(var(--y3) + var(--ll3) - var(--lw1)/2);
    left: calc(var(--x3) + 2*var(--ll3) - var(--lw1)/2);
  }

  #l27{
    height: calc(var(--ll1)*2/3);
    width: var(--lw1);
    top: calc(var(--y3) + 2*var(--ll3));
    left: calc(var(--x3) + var(--ll3) - var(--lw1)/2);
  }

  #l28{
    height: var(--lw1);
    width: calc(var(--ll1)*2/3);
    top: calc(var(--y1) + var(--ll1) - var(--lw1)/2);
    left: var(--x1);
  }

</style>

<!--pointers-->
<style>
  .pointer{
    position: absolute;
    height: var(--pd);
    width: var(--pd);
    border-radius: 10%;
    opacity: 0.5;
  }

  #p1{
    background-color: orange;
    top: calc(var(--pos1t) - var(--pd)/2);
    left: calc(var(--pos1l) - var(--pd)/2);
    display: none;
  }

  #p2{
    background-color: orange;
    top: calc(var(--pos2t) - var(--pd)/2);
    left: calc(var(--pos2l) - var(--pd)/2);
    display: none;
  }

  #p3{
    background-color: orange;
    top: calc(var(--pos3t) - var(--pd)/2);
    left: calc(var(--pos3l) - var(--pd)/2);
    display: none;
  }

  #p4{
    background-color: orange;
    top: calc(var(--pos4t) - var(--pd)/2);
    left: calc(var(--pos4l) - var(--pd)/2);
    display: none;
  }

  #p5{
    background-color: orange;
    top: calc(var(--pos5t) - var(--pd)/2);
    left: calc(var(--pos5l) - var(--pd)/2);
    display: none;
  }

  #p6{
    background-color: orange;
    top: calc(var(--pos6t) - var(--pd)/2);
    left: calc(var(--pos6l) - var(--pd)/2);
    display: none;
  }

  #p7{
    background-color: orange;
    top: calc(var(--pos7t) - var(--pd)/2);
    left: calc(var(--pos7l) - var(--pd)/2);
    display: none;
  }

  #p8{
    background-color: orange;
    top: calc(var(--pos8t) - var(--pd)/2);
    left: calc(var(--pos8l) - var(--pd)/2);
    display: none;
  }

  #p9{
    background-color: orange;
    top: calc(var(--pos9t) - var(--pd)/2);
    left: calc(var(--pos9l) - var(--pd)/2);
    display: none;
  }

  #p10{
    background-color: orange;
    top: calc(var(--pos10t) - var(--pd)/2);
    left: calc(var(--pos10l) - var(--pd)/2);
    display: none;
  }

  #p11{
    background-color: orange;
    top: calc(var(--pos11t) - var(--pd)/2);
    left: calc(var(--pos11l) - var(--pd)/2);
    display: none;
  }

  #p12{
    background-color: orange;
    top: calc(var(--pos12t) - var(--pd)/2);
    left: calc(var(--pos12l) - var(--pd)/2);
    display: none;
  }

  #p13{
    background-color: orange;
    top: calc(var(--pos13t) - var(--pd)/2);
    left: calc(var(--pos13l) - var(--pd)/2);
    display: none;
  }

  #p14{
    background-color: orange;
    top: calc(var(--pos14t) - var(--pd)/2);
    left: calc(var(--pos14l) - var(--pd)/2);
    display: none;
  }

  #p15{
    background-color: orange;
    top: calc(var(--pos15t) - var(--pd)/2);
    left: calc(var(--pos15l) - var(--pd)/2);
    display: none;
  }

  #p16{
    background-color: orange;
    top: calc(var(--pos16t) - var(--pd)/2);
    left: calc(var(--pos16l) - var(--pd)/2);
    display: none;
  }

  #p17{
    background-color: orange;
    top: calc(var(--pos17t) - var(--pd)/2);
    left: calc(var(--pos17l) - var(--pd)/2);
    display: none;
  }

  #p18{
    background-color: orange;
    top: calc(var(--pos18t) - var(--pd)/2);
    left: calc(var(--pos18l) - var(--pd)/2);
    display: none;
  }

  #p19{
    background-color: orange;
    top: calc(var(--pos19t) - var(--pd)/2);
    left: calc(var(--pos19l) - var(--pd)/2);
    display: none;
  }

  #p20{
    background-color: orange;
    top: calc(var(--pos20t) - var(--pd)/2);
    left: calc(var(--pos20l) - var(--pd)/2);
    display: none;
  }

  #p21{
    background-color: orange;
    top: calc(var(--pos21t) - var(--pd)/2);
    left: calc(var(--pos21l) - var(--pd)/2);
    display: none;
  }

  #p22{
    background-color: orange;
    top: calc(var(--pos22t) - var(--pd)/2);
    left: calc(var(--pos22l) - var(--pd)/2);
    display: none;
  }

  #p23{
    background-color: orange;
    top: calc(var(--pos23t) - var(--pd)/2);
    left: calc(var(--pos23l) - var(--pd)/2);
    display: none;
  }

  #p24{
    background-color: orange;
    top: calc(var(--pos24t) - var(--pd)/2);
    left: calc(var(--pos24l) - var(--pd)/2);
    display: none;
  }
</style>

<!--circles-->
<style>

  .circle{
    position: absolute;
    height: var(--cd);
    width: var(--cd);
    border-radius: 50%;
  }

  #c1{
    background-color: var(--p1color);
    left: var(--cl1);
    top: var(--ct1);
  }

  #c2{
    background-color: var(--p1color);
    left: calc(var(--cl1) + var(--cxdif));
    top: var(--ct1);
  }

  #c3{
    background-color: var(--p1color);
    left: calc(var(--cl1) + 2*var(--cxdif));
    top: var(--ct1);
  }

  #c4{
    background-color: var(--p1color);
    left: calc(var(--cl1) + 3*var(--cxdif));
    top: var(--ct1);
  }

  #c5{
    background-color: var(--p1color);
    left: var(--cl1);
    top: calc(var(--ct1) + var(--cxdif));
  }

  #c6{
    background-color: var(--p1color);
    left: calc(var(--cl1) + var(--cxdif));
    top: calc(var(--ct1) + var(--cxdif));
  }

  #c7{
    background-color: var(--p1color);
    left: calc(var(--cl1) + 2*var(--cxdif));
    top: calc(var(--ct1) + var(--cxdif));
  }

  #c8{
    background-color: var(--p1color);
    left: calc(var(--cl1) + 3*var(--cxdif));
    top: calc(var(--ct1) + var(--cxdif));
  }

  #c9{
    background-color: var(--p1color);
    left: var(--cl1);
    top: calc(var(--ct1) + 2*var(--cxdif));
  }

  #c10{
    background-color: var(--p1color);
    left: calc(var(--cl1) + var(--cxdif));
    top: calc(var(--ct1) + 2*var(--cxdif));
  }

  #c11{
    background-color: var(--p1color);
    left: calc(var(--cl1) + 2*var(--cxdif));
    top: calc(var(--ct1) + 2*var(--cxdif));
  }

  #c12{
    background-color: var(--p2color);
    left: var(--cl2);
    top: var(--ct1);
  }

  #c13{
    background-color: var(--p2color);
    left: calc(var(--cl2) + var(--cxdif));
    top: var(--ct1);
  }

  #c14{
    background-color: var(--p2color);
    left: calc(var(--cl2) + 2*var(--cxdif));
    top: var(--ct1);
  }

  #c15{
    background-color: var(--p2color);
    left: calc(var(--cl2) + 3*var(--cxdif));
    top: var(--ct1);
  }

  #c16{
    background-color: var(--p2color);
    left: var(--cl2);
    top: calc(var(--ct1) + var(--cxdif));
  }

  #c17{
    background-color: var(--p2color);
    left: calc(var(--cl2) + var(--cxdif));
    top: calc(var(--ct1) + var(--cxdif));
  }

  #c18{
    background-color: var(--p2color);
    left: calc(var(--cl2) + 2*var(--cxdif));
    top: calc(var(--ct1) + var(--cxdif));
  }

  #c19{
    background-color: var(--p2color);
    left: calc(var(--cl2) + 3*var(--cxdif));
    top: calc(var(--ct1) + var(--cxdif));
  }

  #c20{
    background-color: var(--p2color);
    left: var(--cl2);
    top: calc(var(--ct1) + 2*var(--cxdif));
  }

  #c21{
    background-color: var(--p2color);
    left: calc(var(--cl2) + var(--cxdif));
    top: calc(var(--ct1) + 2*var(--cxdif));
  }

  #c22{
    background-color: var(--p2color);
    left: calc(var(--cl2) + 2*var(--cxdif));
    top: calc(var(--ct1) + 2*var(--cxdif));
  }
</style>

<div id="top"></div>
<div id="game">
  <div id="lines">
    <div class="line" id="l1"></div>
    <div class="line" id="l2"></div>
    <div class="line" id="l3"></div>
    <div class="line" id="l4"></div>
    <div class="line" id="l5"></div>
    <div class="line" id="l6"></div>
    <div class="line" id="l7"></div>
    <div class="line" id="l8"></div>
    <div class="line" id="l9"></div>
    <div class="line" id="l10"></div>
    <div class="line" id="l11"></div>
    <div class="line" id="l12"></div>
    <div class="line" id="l13"></div>
    <div class="line" id="l14"></div>
    <div class="line" id="l15"></div>
    <div class="line" id="l16"></div>
    <div class="line" id="l17"></div>
    <div class="line" id="l18"></div>
    <div class="line" id="l19"></div>
    <div class="line" id="l20"></div>
    <div class="line" id="l21"></div>
    <div class="line" id="l22"></div>
    <div class="line" id="l23"></div>
    <div class="line" id="l24"></div>
    <div class="line" id="l25"></div>
    <div class="line" id="l26"></div>
    <div class="line" id="l27"></div>
    <div class="line" id="l28"></div>
  </div>
  <div id="circles">
    <div class="circle" onclick="clickonc(t1)" id="c1"></div>
    <div class="circle" onclick="clickonc(t2)" id="c2"></div>
    <div class="circle" onclick="clickonc(t3)" id="c3"></div>
    <div class="circle" onclick="clickonc(t4)" id="c4"></div>
    <div class="circle" onclick="clickonc(t5)" id="c5"></div>
    <div class="circle" onclick="clickonc(t6)" id="c6"></div>
    <div class="circle" onclick="clickonc(t7)" id="c7"></div>
    <div class="circle" onclick="clickonc(t8)" id="c8"></div>
    <div class="circle" onclick="clickonc(t9)" id="c9"></div>
    <div class="circle" onclick="clickonc(t10)" id="c10"></div>
    <div class="circle" onclick="clickonc(t11)" id="c11"></div>
    <div class="circle" onclick="clickonc(t12)" id="c12"></div>
    <div class="circle" onclick="clickonc(t13)" id="c13"></div>
    <div class="circle" onclick="clickonc(t14)" id="c14"></div>
    <div class="circle" onclick="clickonc(t15)" id="c15"></div>
    <div class="circle" onclick="clickonc(t16)" id="c16"></div>
    <div class="circle" onclick="clickonc(t17)" id="c17"></div>
    <div class="circle" onclick="clickonc(t18)" id="c18"></div>
    <div class="circle" onclick="clickonc(t19)" id="c19"></div>
    <div class="circle" onclick="clickonc(t20)" id="c20"></div>
    <div class="circle" onclick="clickonc(t21)" id="c21"></div>
    <div class="circle" onclick="clickonc(t22)" id="c22"></div>
  </div>
  <div id="pointers">
    <div class="pointer" onclick="clickonp(1)" id="p1"></div>
    <div class="pointer" onclick="clickonp(2)" id="p2"></div>
    <div class="pointer" onclick="clickonp(3)" id="p3"></div>
    <div class="pointer" onclick="clickonp(4)" id="p4"></div>
    <div class="pointer" onclick="clickonp(5)" id="p5"></div>
    <div class="pointer" onclick="clickonp(6)" id="p6"></div>
    <div class="pointer" onclick="clickonp(7)" id="p7"></div>
    <div class="pointer" onclick="clickonp(8)" id="p8"></div>
    <div class="pointer" onclick="clickonp(9)" id="p9"></div>
    <div class="pointer" onclick="clickonp(10)" id="p10"></div>
    <div class="pointer" onclick="clickonp(11)" id="p11"></div>
    <div class="pointer" onclick="clickonp(12)" id="p12"></div>
    <div class="pointer" onclick="clickonp(13)" id="p13"></div>
    <div class="pointer" onclick="clickonp(14)" id="p14"></div>
    <div class="pointer" onclick="clickonp(15)" id="p15"></div>
    <div class="pointer" onclick="clickonp(16)" id="p16"></div>
    <div class="pointer" onclick="clickonp(17)" id="p17"></div>
    <div class="pointer" onclick="clickonp(18)" id="p18"></div>
    <div class="pointer" onclick="clickonp(19)" id="p19"></div>
    <div class="pointer" onclick="clickonp(20)" id="p20"></div>
    <div class="pointer" onclick="clickonp(21)" id="p21"></div>
    <div class="pointer" onclick="clickonp(22)" id="p22"></div>
    <div class="pointer" onclick="clickonp(23)" id="p23"></div>
    <div class="pointer" onclick="clickonp(24)" id="p24"></div>
  </div>
</div>

<script>
if (typeof player === 'undefined') {
  console.log("Error is intended");
  console.log("Stay calm and fill the form");
  throw new Error();
}

var opp_last_seen = null;
var opp_status;

function confirm_online() {
  //console.log("confirming if online");
  var time = new Date();
  $.ajax({
      url: "check_online.php",
      type: "POST",
      data: {
              gcode: gcode,
              player: player,
              time: Math.floor((new Date()).getTime() / 1000)
            },
      success: function(result) {
        //console.log(result);
        if ( parseInt(result) === 0 ) {
          opp_status = "Waiting for opponent";
          //console.log("waiting for opp");
        }
        else if ( Math.floor((new Date()).getTime() / 1000) - parseInt(result) < 10 ) {
          //console.log("opp online");
          opp_last_seen = parseInt(result);
          opp_status = "Online";
        }
        else {
          if ( Math.floor((new Date()).getTime() / 1000) - parseInt(result) > 10 ) {
            //console.log("deleting tables");
            $.ajax({
                url: "delete_tables.php",
                type: "POST",
                data: {
                        gcode: gcode
                      }
            });
            alert("Opponent has exited the game, you'll be returned to the home screen");
            window.location.replace("http://localhost/dhadi/");
          }
        }
      }
  });
  setTimeout('confirm_online()', 2000);
}
confirm_online();

var cgpos = [["calc(var(--pos1t) - var(--cd)/2)", "calc(var(--pos1l) - var(--cd)/2)"], ["calc(var(--pos2t) - var(--cd)/2)", "calc(var(--pos2l) - var(--cd)/2)"], ["calc(var(--pos3t) - var(--cd)/2)", "calc(var(--pos3l) - var(--cd)/2)"],
  ["calc(var(--pos4t) - var(--cd)/2)", "calc(var(--pos4l) - var(--cd)/2)"], ["calc(var(--pos5t) - var(--cd)/2)", "calc(var(--pos5l) - var(--cd)/2)"], ["calc(var(--pos6t) - var(--cd)/2)", "calc(var(--pos6l) - var(--cd)/2)"],
  ["calc(var(--pos7t) - var(--cd)/2)", "calc(var(--pos7l) - var(--cd)/2)"], ["calc(var(--pos8t) - var(--cd)/2)", "calc(var(--pos8l) - var(--cd)/2)"],["calc(var(--pos9t) - var(--cd)/2)", "calc(var(--pos9l) - var(--cd)/2)"],
  ["calc(var(--pos10t) - var(--cd)/2)", "calc(var(--pos10l) - var(--cd)/2)"], ["calc(var(--pos11t) - var(--cd)/2)", "calc(var(--pos11l) - var(--cd)/2)"], ["calc(var(--pos12t) - var(--cd)/2)", "calc(var(--pos12l) - var(--cd)/2)"],
  ["calc(var(--pos13t) - var(--cd)/2)", "calc(var(--pos13l) - var(--cd)/2)"], ["calc(var(--pos14t) - var(--cd)/2)", "calc(var(--pos14l) - var(--cd)/2)"], ["calc(var(--pos15t) - var(--cd)/2)", "calc(var(--pos15l) - var(--cd)/2)"],
  ["calc(var(--pos16t) - var(--cd)/2)", "calc(var(--pos16l) - var(--cd)/2)"], ["calc(var(--pos17t) - var(--cd)/2)", "calc(var(--pos17l) - var(--cd)/2)"], ["calc(var(--pos18t) - var(--cd)/2)", "calc(var(--pos18l) - var(--cd)/2)"],
  ["calc(var(--pos19t) - var(--cd)/2)", "calc(var(--pos19l) - var(--cd)/2)"], ["calc(var(--pos20t) - var(--cd)/2)", "calc(var(--pos20l) - var(--cd)/2)"], ["calc(var(--pos21t) - var(--cd)/2)", "calc(var(--pos21l) - var(--cd)/2)"],
  ["calc(var(--pos22t) - var(--cd)/2)", "calc(var(--pos22l) - var(--cd)/2)"], ["calc(var(--pos23t) - var(--cd)/2)", "calc(var(--pos23l) - var(--cd)/2)"], ["calc(var(--pos24t) - var(--cd)/2)", "calc(var(--pos24l) - var(--cd)/2)"]];
var dcpos_props = [["var(--dct1)", "var(--dcl1)", 0], ["var(--dct2)", "var(--dcl2)", 0], ["var(--dct3)", "var(--dcl3)", 0], ["var(--dct4)", "var(--dcl4)", 0], ["var(--dct5)", "var(--dcl5)", 0],
                    ["var(--dct6)", "var(--dcl6)", 0], ["var(--dct7)", "var(--dcl7)", 0], ["var(--dct8)", "var(--dcl8)", 0], ["var(--dct9)", "var(--dcl9)", 0], ["var(--dct10)", "var(--dcl10)", 0],
                    ["var(--dct11)", "var(--dcl11)", 0], ["var(--dct12)", "var(--dcl12)", 0], ["var(--dct13)", "var(--dcl13)", 0], ["var(--dct14)", "var(--dcl14)", 0], ["var(--dct15)", "var(--dcl15)", 0],
                    ["var(--dct16)", "var(--dcl16)", 0], ["var(--dct17)", "var(--dcl17)", 0], ["var(--dct18)", "var(--dcl18)", 0], ["var(--dct19)", "var(--dcl19)", 0], ["var(--dct20)", "var(--dcl20)", 0],
                    ["var(--dct21)", "var(--dcl21)", 0], ["var(--dct22)", "var(--dcl22)", 0]]
var pointers = document.getElementsByClassName("pointer");
var pcircles = document.getElementsByClassName("circle");
var tokens_list = [];
var position_props = [[1,1,1,0], [2,1,1,0], [3,1,1,0], [3,2,1,0], [3,3,1,0], [2,3,1,0], [1,3,1,0],
                      [1,2,1,0], [1,1,2,0], [2,1,2,0], [3,1,2,0], [3,2,2,0], [3,3,2,0], [2,3,2,0],
                      [1,3,2,0], [1,2,2,0], [1,1,3,0], [2,1,3,0], [3,1,3,0], [3,2,3,0], [3,3,3,0],
                      [2,3,3,0], [1,3,3,0], [1,2,3,0]];
var opp_last_move_data = null;
var first_player = 1;
var move_no = 1;
var opp_last_move_data;

//token class
function token (id, int_pos) {
  this.id = id;
  this.status = "sleep";
  this.selected = 0;
  this.pos_id = null;
  tokens_list.push(this);

  //this.style = document.getElementsByClassName("circle")[id].style;
}

//token objects
{
  t1 = new token(1, ["var(--ct1)", "var(--cl1)"]);
  t2 = new token(2, ["var(--ct1)", "calc(var(--cl1) + var(--cxdif))"]);
  t3 = new token(3, ["var(--ct1)", "calc(var(--cl1) + 2*var(--cxdif))"]);
  t4 = new token(4, ["var(--ct1)", "calc(var(--cl1) + 3*var(--cxdif))"]);
  t5 = new token(5, ["calc(var(--ct1) + var(--cxdif))", "var(cl1)"]);
  t6 = new token(6, ["calc(var(--ct1) + var(--cxdif))", "calc(var(--cl1) + var(--cxdif))"]);
  t7 = new token(7, ["calc(var(--ct1) + var(--cxdif))", "calc(var(--cl1) + 2*var(--cxdif))"]);
  t8 = new token(8, ["calc(var(--ct1) + var(--cxdif))", "calc(var(--cl1) + 3*var(--cxdif))"]);
  t9 = new token(9, ["calc(var(--ct1) + 2*var(--cxdif))", "var(--cl1)"]);
  t10 = new token(10, ["calc(var(--ct1) + 2*var(--cxdif))", "calc(var(--cl1) + var(--cxdif))"]);
  t11 = new token(11, ["calc(var(--ct1) + 2*var(--cxdif))", "calc(var(--cl1) + 2*var(--cxdif))"]);
  t12 = new token(12, ["var(--ct1)", "var(cl2)"]);
  t13 = new token(13, ["var(--ct1)", "calc(var(--cl2) + var(--cxdif))"]);
  t14 = new token(14, ["var(--ct1)", "calc(var(--cl2) + 2*var(--cxdif))"]);
  t15 = new token(15, ["var(--ct1)", "calc(var(--cl2) + 3*var(--cxdif))"]);
  t16 = new token(16, ["calc(var(--ct1) + var(--cxdif))", "var(cl2)"]);
  t17 = new token(17, ["calc(var(--ct1) + var(--cxdif))", "calc(var(--cl2) + var(--cxdif))"]);
  t18 = new token(18, ["calc(var(--ct1) + var(--cxdif))", "calc(var(--cl2) + 2*var(--cxdif))"]);
  t19 = new token(19, ["calc(var(--ct1) + var(--cxdif))", "calc(var(--cl2) + 3*var(--cxdif))"]);
  t20 = new token(20, ["calc(var(--ct1) + 2*var(--cxdif))", "var(cl2)"]);
  t21 = new token(21, ["calc(var(--ct1) + 2*var(--cxdif))", "calc(var(--cl2) + var(--cxdif))"]);
  t22 = new token(22, ["calc(var(--ct1) + 2*var(--cxdif))", "calc(var(--cl2) + 2*var(--cxdif))"]);
}

var play_status = "waiting";
var selected_token_obj = null;
var not_in_sleep = 0;
var tokens_in_dhadi = [];
var dhadi_tokens_list = [];
var old_tokens_in_dhadi = [];
var new_tokens_in_dhadi = [];

check_opp_move();

function clickonc(token_obj) {
  if ( opp_status !== "Online" ) { //review
    return;
  }
  canclickont(function(result) { //using callback, refer to https://stackoverflow.com/questions/14220321/how-do-i-return-the-response-from-an-asynchronous-call
    //console.log(result);
    if ( result === "1" ) {
      console.log("click is possible");
      if ( token_obj.selected === 0 && play_status === "waiting") {
        console.log("token selected");
        token_obj.selected = 1;
        selected_token_obj = token_obj;
        show_pointers();
        play_status = "selected";
      }
      else if ( play_status === "selected" ) {
        if ( token_obj === selected_token_obj ) {
          console.log("token unselected");
          token_obj.selected = 0;
          hide_pointers();
          play_status = "waiting";
          selected_token_obj = null;
        }
        else {
          hide_pointers();
          console.log("new token selected");
          selected_token_obj.selected = 0;
          selected_token_obj = token_obj;
          token_obj.selected = 1;
          show_pointers();
          play_status = "selected";
        }
      }
    }
    else {
      console.log("click not possible");
    }
  }, token_obj);
}

function clickonp(pointer_id) {
  if ( new_tokens_in_dhadi.length > 0 ) {
    console.log("clicked on opp token");
    move(tokens_list[position_props[pointer_id-1][3]-1], 0);
    move_no++;
    new_tokens_in_dhadi = [];
    send_move(tokens_list[position_props[pointer_id-1][3]-1].id, 0);
    tokens_list[position_props[pointer_id-1][3]-1].pos_id = 0;
    tokens_list[position_props[pointer_id-1][3]-1].status = "dead";
    position_props[pointer_id-1][3] = 0;
    play_status = "waiting";
  }
  else {
    console.log("clicked on pointer");
    move(selected_token_obj, pointer_id);
    move_no++;
    send_move(selected_token_obj.id, pointer_id);
    selected_token_obj.selected = 0;
    selected_token_obj = null;
    play_status = "waiting";
    hide_pointers();
    if ( new_tokens_in_dhadi.length > 0 ) {
      show_opp_token_pointers();
      move_no--;
    }
  }
}

function move (token_obj, pos_id) {
  if ( pos_id === 0 ) {
    hide_pointers();
    for ( var i = 0; i<22; i++ ) {
      if ( dcpos_props[i][2] === 0 && ( (i < 11 && token_obj.id > 11) || (i > 10 && token_obj.id < 12) ) ) {
        document.getElementsByClassName("circle")[token_obj.id-1].style.top = "-100px";
        document.getElementsByClassName("circle")[token_obj.id-1].style.left = "-100px";
        if ( (i === 10 && token_obj.id > 11) || (i === 21 && token_obj.id < 12)) {
          send_move(23, 23);
          alert("You Win!, you'll be returned to the home screen");
          window.location.replace("http://localhost/dhadi/");
        }
        return;
      }
    }
  }
  else {
    console.log("fagvrsfgr");
    document.getElementsByClassName("circle")[token_obj.id-1].style.top = cgpos[pos_id-1][0];
    document.getElementsByClassName("circle")[token_obj.id-1].style.left = cgpos[pos_id-1][1];
    console.log("rgqaer");
    position_props[pos_id-1][3] = token_obj.id;
    if ( token_obj.pos_id !== null) {
      position_props[token_obj.pos_id-1][3] = 0;
    }
    else {
      not_in_sleep++;
    }
    token_obj.pos_id = pos_id;
    token_obj.status = "onboard";
    check_new_dhadi();
    if (  new_tokens_in_dhadi.length > 0 && ( (new_tokens_in_dhadi[0] > 11 && player === 1) || (new_tokens_in_dhadi[0] < 12 && player === 2) ) ) {
      show_dhadi_pointers();
      move_no--;
    }
  }
}

function check_new_dhadi(pos_id) {
  if ( typeof pos_id !== "undefined" ){
    var test_tokens_in_dhadi = [];
    if ( player === 1 ) {
      position_props[pos_id-1][3] = -1;
    }
    else {
      position_props[pos_id-1][3] = 23;
    }
    for (var i = 0; i < 12; i++) {
      if( position_props[i*2][0] === 1 && position_props[i*2][1] === 3 ) {
        if( position_props[i*2][3] !== 0 && position_props[i*2+1][3] !== 0 && position_props[i*2-6][3] !== 0 ) {
          if ( position_props[i*2][3] < 12 && position_props[i*2+1][3] < 12 && position_props[i*2-6][3] < 12 ) {
            test_tokens_in_dhadi.push(position_props[i*2][3], position_props[i*2+1][3], position_props[i*2-6][3]);
          }
          else if ( position_props[i*2][3] > 11 && position_props[i*2+1][3] > 11 && position_props[i*2-6][3] > 11 ) {
            test_tokens_in_dhadi.push(position_props[i*2][3], position_props[i*2+1][3], position_props[i*2-6][3]);
          }
        }
      }
      else {
        if( position_props[i*2][3] !== 0 && position_props[i*2+1][3] !== 0 && position_props[i*2+2][3] !== 0 ) {
          if ( position_props[i*2][3] < 12 && position_props[i*2+1][3] < 12 && position_props[i*2+2][3] < 12 ) {
            test_tokens_in_dhadi.push(position_props[i*2][3], position_props[i*2+1][3], position_props[i*2+2][3]);
          }
          else if ( position_props[i*2][3] > 11 && position_props[i*2+1][3] > 11 && position_props[i*2+2][3] > 11 ) {
            test_tokens_in_dhadi.push(position_props[i*2][3], position_props[i*2+1][3], position_props[i*2+2][3]);
          }
        }
      }
    }
    for ( var i = 0; i<4; i++ ) {
      if ( position_props[i*2+1][3] !== 0 && position_props[i*2+9][3] !== 0 && position_props[i*2+17][3] !== 0 ) {
        if ( position_props[i*2+1][3] < 12 && position_props[i*2+9][3] < 12 && position_props[i*2+17][3] < 12 ) {
          test_tokens_in_dhadi.push(position_props[i*2+1][3], position_props[i*2+9][3], position_props[i*2+17][3]);
        }
        else if ( position_props[i*2+1][3] > 11 && position_props[i*2+9][3] > 11 && position_props[i*2+17][3] > 11 ) {
          test_tokens_in_dhadi.push(position_props[i*2+1][3], position_props[i*2+9][3], position_props[i*2+17][3]);
        }
      }
    }
    position_props[pos_id-1][3] = 0;
    if( difference(test_tokens_in_dhadi, old_tokens_in_dhadi).length > 0 ){
      return 1;
    }
    else {
      return 0;
    }
  }
  else {
    for (var i = 0; i < 12; i++) {
      if( position_props[i*2][0] === 1 && position_props[i*2][1] === 3 ) {
        if( position_props[i*2][3] !== 0 && position_props[i*2+1][3] !== 0 && position_props[i*2-6][3] !== 0 ) {
          if ( position_props[i*2][3] < 12 && position_props[i*2+1][3] < 12 && position_props[i*2-6][3] < 12 ) {
            tokens_in_dhadi.push(position_props[i*2][3], position_props[i*2+1][3], position_props[i*2-6][3]);
            dhadi_tokens_list.push([position_props[i*2][3], position_props[i*2+1][3], position_props[i*2-6][3]]);
          }
          else if ( position_props[i*2][3] > 11 && position_props[i*2+1][3] > 11 && position_props[i*2-6][3] > 11 ) {
            tokens_in_dhadi.push(position_props[i*2][3], position_props[i*2+1][3], position_props[i*2-6][3]);
            dhadi_tokens_list.push([position_props[i*2][3], position_props[i*2+1][3], position_props[i*2-6][3]]);
          }
        }
      }
      else {
        if( position_props[i*2][3] !== 0 && position_props[i*2+1][3] !== 0 && position_props[i*2+2][3] !== 0 ) {
          if ( position_props[i*2][3] < 12 && position_props[i*2+1][3] < 12 && position_props[i*2+2][3] < 12 ) {
            tokens_in_dhadi.push(position_props[i*2][3], position_props[i*2+1][3], position_props[i*2+2][3]);
            dhadi_tokens_list.push([position_props[i*2][3], position_props[i*2+1][3], position_props[i*2+2][3]]);
          }
          else if ( position_props[i*2][3] > 11 && position_props[i*2+1][3] > 11 && position_props[i*2+2][3] > 11 ) {
            tokens_in_dhadi.push(position_props[i*2][3], position_props[i*2+1][3], position_props[i*2+2][3]);
            dhadi_tokens_list.push([position_props[i*2][3], position_props[i*2+1][3], position_props[i*2+2][3]]);
          }
        }
      }
    }
    for ( var i = 0; i<4; i++ ) {
      if ( position_props[i*2+1][3] !== 0 && position_props[i*2+9][3] !== 0 && position_props[i*2+17][3] !== 0 ) {
        if ( position_props[i*2+1][3] < 12 && position_props[i*2+9][3] < 12 && position_props[i*2+17][3] < 12 ) {
          tokens_in_dhadi.push(position_props[i*2+1][3], position_props[i*2+9][3], position_props[i*2+17][3]);
          dhadi_tokens_list.push([position_props[i*2+1][3], position_props[i*2+9][3], position_props[i*2+17][3]]);
        }
        else if ( position_props[i*2+1][3] > 11 && position_props[i*2+9][3] > 11 && position_props[i*2+17][3] > 11 ) {
          tokens_in_dhadi.push(position_props[i*2+1][3], position_props[i*2+9][3], position_props[i*2+17][3]);
          dhadi_tokens_list.push([position_props[i*2+1][3], position_props[i*2+9][3], position_props[i*2+17][3]]);
        }
      }
    }
    //console.log("tokens in dhadi are: "+tokens_in_dhadi);
    for ( var i = 0; i < dhadi_tokens_list.length; i++ ) {
      if ( dhadi_tokens_list[i].every(element => old_tokens_in_dhadi.includes(element)) ) {
      }
      else {
        new_tokens_in_dhadi[0] = dhadi_tokens_list[i][0];
        new_tokens_in_dhadi[1] = dhadi_tokens_list[i][1];
        new_tokens_in_dhadi[2] = dhadi_tokens_list[i][2];
      }
    }
    old_tokens_in_dhadi = tokens_in_dhadi;
    tokens_in_dhadi = [];
    dhadi_tokens_list = [];
  }
}

function send_move (token_id, pos_id) {
  $.post("add_move.php",
  {
    gcode: gcode,
    player: player,
    token_id: token_id,
    new_pos_id: pos_id
  },
  function(data, status){
    console.log(data);
  });
}

function show_pointers () {
  console.log("showing pointers");
  if ( selected_token_obj.status === "sleep" ) {
    console.log("selected token was in sleep");
    for (var i = 0; i < 24; i++) {
      if ( position_props[i][3] !== 0 || (check_new_dhadi(i+1) === 1 && cannot_kill() === 1) ) {
        continue;
      }
      else{
        pointers[i].style.display = "block";
      }
    }
  }
  if ( selected_token_obj.status === "onboard" ) {
    console.log("selected token was onboard");
     var curr_pos_props = position_props[selected_token_obj.pos_id - 1];
     for (var i = 0; i < 24; i++) {
       if ( position_props[i][3] === 0 && !(check_new_dhadi(i+1) === 1 && cannot_kill() === 1)) {
         var check_pos_props = position_props[i];
         var x = Math.abs(curr_pos_props[0] - check_pos_props[0]);
         x += Math.abs(curr_pos_props[1] - check_pos_props[1]);
         x += Math.abs(curr_pos_props[2] - check_pos_props[2]);
         if ( x <= 1) {
           if ( !((curr_pos_props[0] + curr_pos_props[1])%2 === 0 &&
                Math.abs(check_pos_props[2] - curr_pos_props[2]) === 1) ) {
                  pointers[i].style.display = "block";
                }
         }
       }
     }
  }
}

function show_dhadi_pointers () {
  console.log("showing dhadi pointers");
  for (var i = 0; i < new_tokens_in_dhadi.length; i++) {
    pointers[tokens_list[new_tokens_in_dhadi[i]-1].pos_id-1].style.display = "block";
  }
}

function show_opp_token_pointers () {
  console.log("showing opponent token pointers");
  for ( var i = 0; i < 24; i++) {
    if ( ((position_props[i][3] > 11 && player === 1) || (position_props[i][3] < 12 && position_props[i][3] !== 0 && player === 2)) && !old_tokens_in_dhadi.includes(position_props[i][3]) ) {
      pointers[tokens_list[position_props[i][3]-1].pos_id-1].style.display = "block";
    }
  }
}

function hide_pointers () {
  console.log("hiding pointers");
  for (var i = 0; i < 24; i++) {
    pointers[i].style.display = "none";
  }
}

function canclickont(callback, token_obj) {
  console.log("Checking click...")
  //console.log("posting: " + gcode + ", " + player + ", " + first_player + ", " + token_obj.id);
  if ( token_obj.status === "onboard" && not_in_sleep < 22 ) {
    //console.log("All tokens need to be placed first");
    return;
  }
  console.log("sending post request");
  $.ajax({
      url: "checkclick.php",
      type: "POST",
      data: {
        gcode: gcode,
        player: player,
        first_player: first_player,
        token_id: token_obj.id
      },
      success: callback
  });
  return 0;
}

function check_opp_move() {
  console.log("entered check_opp_move function");
  console.log("new_tokens_in_dhadi: "+new_tokens_in_dhadi);
  console.log("old_tokens_in_dhadi: "+old_tokens_in_dhadi);
  console.log("tokens_in_dhadi: "+tokens_in_dhadi);
  if ( (move_no%2 === 0 && player === 1) || (move_no%2 !== 0 && player === 2) ) {
    console.log("checking for opponent move");
    $.ajax({
      url: "opp_move.php",
      type: "POST",
      data: {
        gcode: gcode,
        player: player
      },
      success: function(data) {
        console.log(data);
        if ( data !== "waiting" ) {
          if ( data !== opp_last_move_data) {
            if ( parseInt(data.split(",")[0]) === 0 ) {
              alert("Game Draw!!, you'll be returned to the home screen");
              window.location.replace("http://localhost/dhadi/");
            }
            if ( parseInt(data.split(",")[0]) === 23 ) {
              alert("You Win!, you'll be returned to the home screen");
              window.location.replace("http://localhost/dhadi/");
            }
            opp_last_move_data = data;
            opp_move_token_obj = tokens_list[parseInt(data.split(",")[0])-1];
            var opp_move_pos_id = parseInt(data.split(",")[1]);
            move_no++;
            move(opp_move_token_obj, opp_move_pos_id);
            if ( opp_move_pos_id === 0 ) {
              position_props[opp_move_token_obj.pos_id-1][3] = 0;
              opp_move_token_obj.pos_id = 0;
              opp_move_token_obj.status = "dead";
              new_tokens_in_dhadi = [];
            }
          }
        }
        /*if ( can_move() === 0 ) {
          send_move(0, 0);
          alert("Game Draw!!, you'll be returned to the home screen");
          window.location.replace("http://localhost/dhadi/");
        }*/
      }
    });
  }
  setTimeout('check_opp_move()', 500);
}

function difference(a, b) {
    return [...b.reduce( (acc, v) => acc.set(v, (acc.get(v) || 0) - 1),
            a.reduce( (acc, v) => acc.set(v, (acc.get(v) || 0) + 1), new Map() )
    )].reduce( (acc, [v, count]) => acc.concat(Array(Math.abs(count)).fill(v)), [] );
}

/*function difference(a, b) {
  var a_grouped = [];
  var b_grouped = [];
  var result = [];
  for ( var i = 0; i < a.length/3; i++ ) {
    a_grouped[i] = [a[i*3], a[i*3+1], a[i*3+2]];
  }
  for ( var i = 0; i < b.length/3; i++ ) {
    b_grouped[i] = [b[i*3], b[i*3+1], b[i*3+2]];
  }
  for ( var i = 0; i < a_grouped.length; i++ ) {
    if ( !b_grouped.includes(a_grouped[i]) ) {
      result[i*3] = a_grouped[i][0];
      result[i*3+1] = a_grouped[i][1];
      result[i*3+2] = a_grouped[i][2];
    }
  }
  return result;
}*/

function cannot_kill () {
  if ( player === 2 ) {
    for ( var i = 0; i < 11; i++ ) {
      if ( !old_tokens_in_dhadi.includes(tokens_list[i].id) && tokens_list[i].status === "onboard" ) {
        return 0;
      }
    }
  }
  else {
    for ( var i = 11; i < 22; i++ ) {
      if ( !old_tokens_in_dhadi.includes(tokens_list[i].id) && tokens_list[i].status === "onboard" ) {
        return 0;
      }
    }
  }
  return 1;
}

function check_game_over() {
  var count = 0;
  if ( move_no > 0 && not_in_sleep === 11 ) {
    for ( var i = 0; i < 24; i++ ) {
      if ( position_props[i][3] > 11*(player-1) && position_props[i][3] < 11*player+1 ) {
        count++;
      }
    }
    if ( count === 0 ) {
      send_move(23, 23);
      $.ajax({
          url: "delete_tables.php",
          type: "POST",
          data: {
                  gcode: gcode
                }
      });
      alert("You Lose!, you'll be returned to the home screen");
      window.location.replace("http://localhost/dhadi/");
    }
  }
}
</script>
