<style>
  .desktop{
    margin: 0 auto;
    width: 100%;
    min-height: 100vh;
    background-image: url("system/imgs/wallpaper.jpg");
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    position: relative;
    padding: 10px;
    box-sizing: border-box;
  }
  .desktop-icons{
    display: flex;
    flex-direction: column;
    flex-wrap: wrap;
    align-content: flex-start;
    height: calc(100vh - 80px);
    gap: 15px;
    color: white;
  }
  .app-icon{
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 80px;
    padding: 5px;
    cursor: pointer;
    border-radius: 5px;
    transition: background 0.2s;
  }
  .app-icon:hover{
    background: rgba(255,255,255,0.2);
  }
  .app-icon img{
    width: 48px;
    height: 48px;
    object-fit: contain;
  }
  .app-icon span{
    font-size: 12px;
    text-align: center;
    margin-top: 5px;
    text-shadow: 1px 1px 2px black;
    word-break: break-word;
  }
  .window{
    position: absolute;
    background: #2d2d2d;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.5);
    min-width: 300px;
    min-height: 200px;
    display: none;
  }
  .window.active{
    display: block;
  }
  .window-header{
    background: #1a1a1a;
    padding: 8px 12px;
    border-radius: 8px 8px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: move;
  }
  .window-title{
    color: white;
    font-size: 14px;
  }
  .window-close{
    background: #ff5f57;
    border: none;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    cursor: pointer;
    color: white;
    font-size: 12px;
  }
  .window-content{
    padding: 15px;
    color: white;
  }
  .taskbar{
    display: flex;
    flex-direction: row;
    align-items: center;
    height: 60px;
    width: 100%;
    position: fixed;
    bottom: 10px;
    left: 0;
    right: 0;
    margin: 0 auto;
    max-width: 800px;
    background-color: rgba(30,30,30,0.9);
    border-radius: 30px;
    padding: 0 20px;
    box-sizing: border-box;
  }
  .taskbar-apps{
    display: flex;
    gap: 10px;
    flex: 1;
  }
  .taskbar-app{
    width: 40px;
    height: 40px;
    border-radius: 8px;
    cursor: pointer;
    overflow: hidden;
  }
  .taskbar-app img{
    width: 100%;
    height: 100%;
    object-fit: contain;
  }
  .start-button{
    background: #ff5722;
    border: none;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    cursor: pointer;
    color: white;
    font-weight: bold;
  }
  .start-menu{
    position: fixed;
    bottom: 80px;
    left: 10px;
    background: linear-gradient(180deg, #1e3a5f 0%, #0d1b2a 100%);
    border-radius: 10px 10px 0 0;
    padding: 0;
    display: none;
    width: 350px;
    min-height: 400px;
    box-shadow: 0 0 20px rgba(0,0,0,0.5);
  }
  .start-menu.active{
    display: flex;
    flex-direction: column;
  }
  .start-menu-header{
    padding: 15px;
    color: white;
    font-size: 14px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
  }
  .start-menu-apps{
    display: flex;
    flex-direction: column;
    gap: 5px;
    padding: 10px;
    flex: 1;
    overflow-y: auto;
  }
  .start-menu-app{
    display: flex;
    align-items: center;
    padding: 8px 12px;
    border-radius: 5px;
    cursor: pointer;
    color: white;
    transition: background 0.2s;
  }
  .start-menu-app:hover{
    background: rgba(255,255,255,0.15);
  }
  .start-menu-app img{
    width: 32px;
    height: 32px;
    margin-right: 12px;
  }
  .start-menu-footer{
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 15px;
    background: rgba(0,0,0,0.3);
    border-radius: 0 0 10px 10px;
  }
  .user-info{
    color: white;
    font-size: 13px;
  }
  .power-btn{
    background: #e74c3c;
    border: none;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s;
  }
  .power-btn:hover{
    background: #c0392b;
  }
  .power-btn svg{
    width: 18px;
    height: 18px;
    fill: white;
  }
</style>
