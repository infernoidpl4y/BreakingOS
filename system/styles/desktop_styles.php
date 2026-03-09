<style>
  .desktop{
    margin: 0 auto;
    width: 100%;
    min-height: 550px;
    background-image: url("system/imgs/wallpaper.jpg");
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
  }<h1>Welcome <?= $_SESSION['user']; ?></h1>
  .apps{
    display: flex;
    flex-direction: row;
    color: red;
  }
  .taskbar{
    display: flex;
    flex-direction: row;
    align-items: center;
    height: 10vh;
    width: 100%;
    bottom: 0;
    position: absolute;
    background-color: #ffffff0e;
    border-radius: 50vh;
  }
  .taskbar input{
    height: 50px;
    width: 50px;
  }
</style>
