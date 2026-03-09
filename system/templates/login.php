<?php
  require_once(OS_STYLES_DIR."login_styles.php");
  if(isset($_GET['r']) and $_GET['r']=="register"){
    $LOGIN_PAGE="Register";
    if(isset($_POST['username'])){
      $users=scandir("users/");
      foreach($users as $user){
        if($user!==$_POST['username']){
          continue;
        }else{
          $LOGIN_STATE="User exists";
          break;
        }
      }if(!isset($LOGIN_STATE) and trim($_POST['username'])!=="" and trim($_POST['name'])!==""){
        $name=$_POST['name'];
        $user=$_POST['username'];
        $password=$_POST['password'];
        $userpath=OS_USERS_DIR.$user;
        mkdir($userpath);
        mkdir($userpath."/programs");
        file_put_contents($userpath."/desktop.json","{}");
        file_put_contents($userpath."/userfile.json","{\"name\":\"$name\", \"username\":\"$user\", \"password\":\"$password\"}");
        file_put_contents($userpath."/apps.desk","");
      }
    }
  }else{
    $LOGIN_PAGE="Login";
    if(isset($_POST['username'])){
      $users=scandir("users/");
      foreach($users as $user){
        if($user=="." or $user=="..") continue;
        $c_userfile=file_get_contents(OS_USERS_DIR.$user."/userfile.json");
        $userfile=json_decode($c_userfile, true);
        if($user==$_POST['username'] and $userfile['password']==$_POST['password']){
          $_SESSION['username']=$user;
          $_SESSION['user']=$userfile['name'];
          header("Location: /");
          exit;
        }
      }
      if(!isset($_SESSION['username'])) $LOGIN_STATE="Incorrect credentials";
    }
  }
?>
<div class="Login">
  <button class="fullscreen-btn" onclick="toggleFullScreen()" title="Pantalla completa">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="white">
      <path d="M7 14H5v5h5v-2H7v-3zm-2-4h2V7h3V5H5v5zm12 7h-3v2h5v-5h-2v3zM14 5v2h3v3h2V5h-5z"/>
    </svg>
  </button>
  <div class="form">
    <h1><?= $LOGIN_PAGE ?></h1>
    <form method="POST">
      <?php
        if(isset($_GET['r']) and $_GET['r']=="register") echo '<input type="text" name="name" placeholder="Full name" class="name-input">';
      ?>
      <input type="text" name="username" placeholder="Username">
      <input type="password" name="password" placeholder="Password">
      <input type="submit" value="Send" class="bform">
    </form>
    <?php
      if(isset($LOGIN_STATE)) echo '<p class="error-msg">' . htmlspecialchars($LOGIN_STATE) . '</p>';
      if(isset($_GET['r']) and $_GET['r']=="register"){
        echo "<a href='/' class='ivc'><i>I have a account</i></a>";
      }else{
        echo "<a href='/?r=register' class='ivc'><i>I don't have a account</i></a>";
      }
    ?>
  </div>
</div>
<script>
function toggleFullScreen(){
    if(!document.fullscreenElement){
        document.documentElement.requestFullscreen().catch(() => {});
    }else{
        if(document.exitFullscreen){
            document.exitFullscreen();
        }
    }
}
</script>
</body>
</html>