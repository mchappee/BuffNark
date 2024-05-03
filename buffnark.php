<?php
  
  include ("classes.php");

  if (isset ($argv[1]))
    $wowlogfile = $argv[1];
  else
    die ("no wow logfile\n");

  if (isset ($argv[2]))
    $reportname = $argv[2];
  else
    die ("no reportname\n");

  if (isset ($argv[3]))
    $reporttitle = $argv[3];
  else
    die ("no report title\n");

  $auraarray = Array ("Mana Regeneration", "Rallying Cry of the Dragonslayer", "Fengus' Ferocity", "Mol'dar's Moxie", "Slip'kik's Savvy", "Spirit of Zandalar", "Songflower Serenade", 
                       "Regeneration", "Shadow Protection ", "Blessed Sunfruit", "Spirit of Zanza", "Warchief's Blessing");
  $persistarray = Array ("Rallying Cry of the Dragonslayer", "Fengus' Ferocity", "Mol'dar's Moxie", "Slip'kik's Savvy", "Spirit of Zandalar", "Songflower Serenade", "Warchief's Blessing");

  $castarray = Array ("Brilliant Wizard Oil", "Brilliant Mana Oil", "Mana Regeneration", "Greater Firepower", "Greater Arcane Elixir", "Distilled Wisdom", "Supreme Power", 
                      "Flask of the Titans", "Elixir of the Mongoose", "Elixir of Giants", "Sharpen Blade V", "Sharpen Blade III", "Sharpen Weapon - Critical", "Greater Armor", 
                      "Fire Protection","Frost Protection", "Strike of the Scorpok", "Invulnerability", "Winterfall Firewater", "Gordok Green Grog",
                      "Free Action", "Swiftness of Zanza", "Sheen of Zanza", "Nature Protection ", "Infallible Mind", "Health II", "Arcane Protection", "Mighty Rage",
                      "Stratholme Holy Water", "Rage of Ages", "Enhance Blunt Weapon V", "Goblin Sapper Charge");

  $iconarray = Array (Array ("Brilliant Wizard Oil", "brilliantwizard.jpg"), Array ("Brilliant Mana Oil", "brilliantmana.jpg"), Array ("Mana Regeneration", "mageblood.jpg"), 
                      Array ("Greater Firepower", "firepower.jpg"), Array ("Greater Arcane Elixir","arcane.jpg"), Array ("Distilled Wisdom","distilled.jpg"), Array ("Supreme Power", "supremepower.jpg"),
                      Array ("Flask of the Titans","titans.jpg"), Array ("Elixir of the Mongoose","mongoose.jpg"), Array ("Elixir of Giants","giants.jpg"),
                      Array ("Sharpen Blade V","sharpening.jpg"), Array ("Sharpen Blade III","sharpening.jpg"), Array ("Sharpen Weapon - Critical", "sharpening.jpg"),
                      Array ("Rallying Cry of the Dragonslayer", "wb.png"), Array ("Fengus' Ferocity", "wb.png"), Array ("Mol'dar's Moxie", "wb.png"), Array ("Slip'kik's Savvy", "wb.png"),
                      Array ("Spirit of Zandalar", "wb.png"), Array ("Songflower Serenade", "wb.png"), Array ("Greater Armor", "greaterdefense.jpg"), Array ("Regeneration", "trollsblood.jpg"), 
                      Array ("Free Action", "freeaction.jpg"), Array ("Swiftness of Zanza", "swiftzanza.jpg"), Array ("Spirit of Zanza", "spiritzanza.jpg"), Array ("Sheen of Zanza", "sheenzanza.jpg"),
                      Array ("Winterfall Firewater", "firewater.jpg"), Array ("Gordok Green Grog", "greengrog.jpg"), Array ("Shadow Protection ", "shadowprot.jpg"), Array ("Frost Protection", "frostprot.jpg"),
                      Array ("Strike of the Scorpok", "scorpok.jpg"), Array ("Invulnerability", "invulnerability.jpg"), Array ("Fire Protection", "fireprot.jpg"), Array ("Nature Protection ", "natureprot.jpg"),
                      Array ("Infallible Mind", "infallible.jpg"), Array ("Health II", "fort.jpg"), Array ("Arcane Protection", "arcaneprot.jpg"), Array ("Blessed Sunfruit", "sunfruit.jpg"),
                      Array ("Warchief's Blessing", "wb.png"), Array ("Mighty Rage", "mightyrage.jpg"), Array ("Stratholme Holy Water", "holywater.jpg"), Array ("Rage of Ages", "roids.jpg"),
                      Array ("Enhance Blunt Weapon V", "blunt5.jpg"), Array ("Goblin Sapper Charge", "sapper.jpg"));

  $playerarray = Array ();

  $wowlog = trimlog (file ($wowlogfile), $auraarray, $castarray);
  $playerarray = get_players ($wowlog);

  foreach ($playerarray as $player) {
    $consumepair = get_consumes ($wowlog, $player->id, $persistarray);
    $player->consumearray = $consumepair[0];
    $player->consumedetailarray = $consumepair[1];
  }

  $playerarray = condensereport ($playerarray);
  create_output ($playerarray, $reportname, $iconarray, $reporttitle);
  create_longoutput ($playerarray, "long-" . $reportname, $iconarray, $reporttitle);

  function geticon ($consume, $iconarray) {
    foreach ($iconarray as $iconpair) {
      if ($iconpair[0] == $consume)
        return $iconpair[1];
    }
    return $iconpair[1];
  }

  function get_count ($consume, $player) {
    $ccount = 0;
    foreach ($player->consumearray as $oldconsume) {
      if ($oldconsume->consumename == $consume->consumename)
        $ccount++;
    }
    return $ccount;
  }

  function dedupe_objects ($array, $keep_key_assoc = false){
    $duplicate_keys = array();
    $tmp = Array ();       

    foreach ($array as $key => $val){
        if (is_object($val))
            $val = (array)$val;

        if (!in_array($val, $tmp))
            $tmp[] = $val;
        else
            $duplicate_keys[] = $key;
    }

    foreach ($duplicate_keys as $key)
        unset($array[$key]);

    return $keep_key_assoc ? $array : array_values($array);
  }

  function condensereport ($playerarray) {
    foreach ($playerarray as $player) {
      foreach ($player->consumearray as $consume) {
        $ccount = get_count ($consume, $player);
        $consume->count = $ccount;
      }

      $player->condensedarray = dedupe_objects ($player->consumearray, $keep_key_assoc = false);
    }

    return $playerarray;
  }

  function create_longoutput ($playerarray, $reportname, $iconarray) {
    $hf = fopen ("reports/$reportname", "w");
    fputs ($hf, "<table border=0>\n");

    foreach ($playerarray as $player) {
      $name = str_replace ("\"", "",$player->name);
      fputs ($hf, "<tr><td colspan=5><b>$name</b></td></tr>\n");
      foreach ($player->consumedetailarray as $consume) {
        $icon = geticon ($consume->consumename, $iconarray);
        fputs ($hf, "<tr><td>&nbsp&nbsp<img src=/unyielding/buffnark/icons/$icon width=20></td><td></td><td>$consume->consumename</td><td>&nbsp&nbsp<b>Start:</b> $consume->start</td><td>&nbsp&nbsp<b>End:</b> $consume->end</td></tr>\n");
      }

      fputs ($hf, "<tr><td colspan=5>&nbsp</td></tr>\n");
    }
    fputs ($hf, "</table>\n");
  }

  function create_stats ($playerarray) {
    $statarray = Array ();
    foreach ($playerarray as $player) {
      //print_r ($player);
      foreach ($player->condensedarray as $consume) {
        if (isset ($statarray[$consume->consumename]))
          $statarray[$consume->consumename] = $statarray[$consume->consumename] + $consume->count;
        else
          $statarray[$consume->consumename] = $consume->count;
      }
    }
    return $statarray;
  }

  function create_output ($playerarray, $reportname, $iconarray, $reporttitle) {
    $count = 0;
    $hf = fopen ("reports/$reportname", "w");
    fputs ($hf, "<html><body bgcolor=#ffffff><h2>$reporttitle</h2></body>\n");

    $statarray = create_stats ($playerarray);

    fputs ($hf, "<table border=1><tr><td>\n");
    fputs ($hf, "<table border=0>\n");

    foreach (array_keys ($statarray) as $stat) {
      if ($count == 0)
        fputs ($hf, "<tr><td valign=top>\n");
      if ($count == 1)
        fputs ($hf, "</td><td valign=top>\n");
      if ($count == 2)
        fputs ($hf, "</td><td valign=top>\n");
      if ($count == 3)
        fputs ($hf, "</td><td valign=top>\n");

      fputs ($hf, "&nbsp&nbsp&nbsp$stat:</td><td>" . $statarray[$stat]);

      if ($count == 3) {
        fputs ($hf, "</td></tr>\n");
        $count = -1;
      }
      $count++;
    }

    fputs ($hf, "</tr></table></td></tr></table><br>\n");

    fputs ($hf, "<table border=0>\n");
    $count = 0; 

    foreach ($playerarray as $player) {
      if ($count == 0)
        fputs ($hf, "<tr><td valign=top>\n");
      if ($count == 1)
        fputs ($hf, "</td><td valign=top>\n");
      if ($count == 2)
        fputs ($hf, "</td><td valign=top>\n");
      if ($count == 3)
        fputs ($hf, "</td><td valign=top>\n");
      

      $name = str_replace ("\"", "",$player->name);
      fputs ($hf, "<table border=0><tr><td colspan=3><b>$name</b></td></tr>\n");
      foreach ($player->condensedarray as $consume) {
        $icon = geticon ($consume->consumename, $iconarray);
 
        if ($consume->count > 1)
          $x = "x" . $consume->count;
        else
          $x = "";
        fputs ($hf, "<tr><td>&nbsp&nbsp<img src=/unyielding/buffnark/icons/$icon width=20></td><td></td><td>$consume->consumename $x</td></tr>");
      }

      fputs ($hf, "<tr><td colspan=3>&nbsp</td></tr></table>\n");
      if ($count == 3) {
        fputs ($hf, "</td></tr>\n");
        $count = -1;
      }
      $count++;
    }
    fputs ($hf, "</table>\n");
  }

  function check_persist ($buff, $persistarray, $consumearray) {
    $pflag = false;
    foreach ($persistarray as $pbuff) {
      if ($pbuff == $buff)
        $pflag = true;
    }

    if ($pflag == false)
      return true;

    foreach ($consumearray as $consume) {
      if ($consume->consumename == $buff)
        return false;
    }
    return true;
  }

  function get_consumes ($wowlog, $playerid, $persistarray) {
    $consumearray = Array ();
    $consumedetailarray = Array ();
    foreach ($wowlog as $line) {
      $tstamparray = explode (" ", $line);
      $tstamp = $tstamparray[0] . " " . $tstamparray[1];
      $larraysp = explode ("  ", $line);
      $larraycm = explode (",", $larraysp[1]);

      if (isset ($larraycm[10]))
        $buff = str_replace ("\"", "", $larraycm[10]);

      if ($larraycm[0] == "SPELL_AURA_APPLIED" && $larraycm[1] == $playerid) {
        if (check_persist ($buff, $persistarray, $consumearray)) {
          $consume  = New Consume;
          $consumedetail = New ConsumeDetail;
          $consume->consumename = $buff;
          $consumedetail->consumename = $buff;
          $consumedetail->start = $tstamp;
          $consumedetail->end = get_endconsume ($wowlog, $larraycm[9], $playerid, $tstamp);
          array_push ($consumearray, $consume);
          array_push ($consumedetailarray, $consumedetail);
        }
      }

      if ($larraycm[0] == "SPELL_CAST_SUCCESS" && $larraycm[1] == $playerid) {
        $consume  = New Consume;
        $consumedetail = New ConsumeDetail;
        $consume->consumename = $buff;
        $consumedetail->consumename = $buff;
        $consumedetail->start = $tstamp;
        $consumedetail->end = get_endconsume ($wowlog, $larraycm[9], $playerid, $tstamp);
        array_push ($consumearray, $consume);
        array_push ($consumedetailarray, $consumedetail);
      }
    }

    return Array ($consumearray, $consumedetailarray);

  }

  function get_endconsume ($wowlog, $consumeid, $playerid, $tstamp) {
    $tstampflag = false;

    foreach ($wowlog as $line) {
      $tstamparray = explode (" ", $line);
      $newtstamp = $tstamparray[0] . " " . $tstamparray[1];
      $larraysp = explode ("  ", $line);
      $larraycm = explode (",", $larraysp[1]);

      if ($tstampflag) {
        if (($larraycm[0] == "SPELL_AURA_REMOVED" && $larraycm[9] == $consumeid) && $larraycm[1] == $playerid)
          return $newtstamp;
        //if ((($larraycm[0] == "SPELL_AURA_APPLIED" && $larraycm[9] == $consumeid) || ($larraycm[0] == "SPELL_AURA_REMOVED" && $larraycm[9] == $consumeid)) && $larraycm[1] == $playerid)
          //return $newtstamp;
        //if (($larraycm[0] == "SPELL_AURA_APPLIED" && $larraycm[9] == $consumeid) || ($larraycm[0] == "SPELL_AURA_REMOVED" && $larraycm[9] == $consumeid))
      }

      if ($newtstamp == $tstamp)
        $tstampflag = true;
    }
    return 0;
  }

  function get_players ($wowlog) {
    $playerarray = Array ();
    $testarray = Array ();
    foreach ($wowlog as $line) {
      $tstamparray = explode (" ", $line);
      $tstamp = $tstamparray[0] . " " . $tstamparray[1];
      $larraysp = explode ("  ", $line);
      $larraycm = explode (",", $larraysp[1]);

      if (!in_array ($larraycm[1], $testarray)) {
        if (strstr ($larraycm[1], "Player")) { 
          array_push ($testarray, $larraycm[1]);
          $player = new Player;
          $player->name = $larraycm[2];
          $player->id = $larraycm[1];
          $player->consumearray = Array ();
          array_push ($playerarray, $player);
        }
      }
    }

    return $playerarray;

  }

  function trimlog ($wowlog, $auraarray, $castarray) {
    $trimlog = Array ();
    $headers = Array ("ENCOUNTER_START", "ENCOUNTER_END");

    foreach ($wowlog as $line) {
      $tstamparray = explode (" ", $line);
      if (!isset ($tstamparray[1]))
        die ("Not a valid combatlog\n");
      $tstamp = $tstamparray[0] . " " . $tstamparray[1];
      $larraysp = explode ("  ", $line);
      $larraycm = explode (",", $larraysp[1]);
      if (isset ($larraycm[10]))
        $buff = str_replace ("\"", "", $larraycm[10]);

      if (in_array ($larraycm[0], $headers))
        array_push ($trimlog, $line);

      switch ($larraycm[0]) {
        case "SPELL_AURA_APPLIED":
          if (in_array ($buff, $auraarray))
            array_push ($trimlog, $line);
        break;

        case "SPELL_AURA_REMOVED":
          if (in_array ($buff, $auraarray))
            array_push ($trimlog, $line);
          if (in_array ($buff, $castarray))
            array_push ($trimlog, $line);
        break;

        case "SPELL_CAST_SUCCESS":
          if (in_array ($buff, $castarray))
            array_push ($trimlog, $line);
        break;

      }
    }
    //print_r ($trimlog);
    //die ();
    return $trimlog;
  }

?>

