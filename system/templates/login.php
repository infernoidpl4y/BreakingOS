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
        mkdir($userpath."/apps");
        file_put_contents($userpath."/desktop.json","{}");
        file_put_contents($userpath."/userfile.json","{\"name\":\"$name\", \"password\":\"$password\"}");
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
          header("BWebOS.php");
        }
      }
      if(!isset($_SESSION['username'])) $LOGIN_STATE="Incorrect credentials";
    }
  }
?>
<div class="Login">
  <div class="form">
    <h1><?= $LOGIN_PAGE ?></h1>
    <form method="POST">
      <?php
        if($LOGIN_PAGE=="Register") echo "<input type='text' name='name' placeholder='username...' class='eform'><br>";
      ?>
      <input type="text" name="username" placeholder="user..." class="eform"><br>
      <input type="password" name="password" placeholder="password..." class="eform"><br>
      <input type="submit" value="SEND" class="bform">
    </form>
    <?php
      if(isset($LOGIN_STATE)) echo "<span style='color: red'>$LOGIN_STATE</span><br>";
      if(isset($_GET['r']) and $_GET['r']=="register"){
        echo "<a href='/BWebOS.php' class='ivc'><i>I have a account</i></a>";
      }else{
        echo "<a href='/BWebOS.php?r=register' class='ivc'><i>I don't have a account</i></a>";
      }
    ?>
  </div>
</div>