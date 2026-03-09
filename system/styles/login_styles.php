<style>
    .Login{
        background-image: url('system/imgs/uwp4960630.jpeg');
        width: 100%;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
    }
    .form{
        color: white;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        z-index: 100;
        width: auto;
        height: auto;
        border: 1px solid rgba(255,255,255,0.3);
        border-radius: 5vh;
        padding: 50px;
        box-shadow: 0 10px 30px -5px rgba(0,0,0,3);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .form:hover{
        transform: translateY(-2px);
        box-shadow: 0 20px 40px -5px rgba(0,0,0,4);
    }
    input:focus{
      outline: 2px solid blue;
    }
    input:disabled{
      background-color: #eee;
    }
    input:checked + label{
      color green;
    }
    .ivc{
        box-shadow: 0 5px 30px -5px;
    }
    .ivc:link{
      color: white;
    }
    .ivc:visited{
      color: white;
    }
    .ivc:active{
      color: orange;
    }
    .ivc:hover{
      color: red;
      box-shadow: 0 5px 10px -5px;
    }
    input{
      margin: 2px;
      background-color: #ffffffa7;
      border-radius: 50px;
    }
    .bform{
      width: 20vh;
      height: 20vh;
    }
    .eform{
      width: 100vh;
      height 100vh
    }
    form{
      display: flex;
      flex-direction: column;
      align-items: center;
    }
    @media(min-width: 550px){
      .eform{
        border-radius: 100px;
        height: 50px;
        width: 500px;
      }
      .bform{
        width: 100px;
        height: 50px;
      }
    }
</style>