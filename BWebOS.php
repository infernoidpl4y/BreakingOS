<?php
  session_start();
  if(isset($_POST['command'])){
    if($_POST['command']=="poweroff"){
      session_destroy();
      session_abort();
      header("BWebOS.php");
      echo OS_REDIRECT_HOME;
    }
  }
  //SYSTEM CONST
  const OS_SYSTEM_DIR="system/";
  const OS_USERS_DIR="users/";
  const OS_TEMPLATES_DIR=OS_SYSTEM_DIR."templates/";
  const OS_STYLES_DIR=OS_SYSTEM_DIR."styles/";
  const OS_IMAGES_DIR=OS_SYSTEM_DIR."imgs/";
  const OS_SCRIPTS_DIR=OS_SYSTEM_DIR."scripts/";
  const OS_PROGRAMS_DIR=OS_SYSTEM_DIR."programs/";
  const OS_VIDEOS_DIR=OS_SYSTEM_DIR."videos/";
  
  //UTILS VARS
  const OS_REDIRECT_HOME="<script>window.location='/BWebOS.php'</script>";
  if(isset($_SESSION['username'])){
    $OS_USER_DIR=OS_USERS_DIR.$_SESSION['username'];
  }

  if(!isset($_SESSION['username'])){
    require_once(OS_TEMPLATES_DIR."login.php");
  }else{
    require_once(OS_TEMPLATES_DIR."desktop.php");
  }
?>