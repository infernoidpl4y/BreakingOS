<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<style>
    *{
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    html, body{
        height: 100%;
        overflow: hidden;
    }
    .Login{
        background-image: url('system/imgs/uwp4960630.jpeg');
        width: 100%;
        height: 100vh;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        padding: 20px;
    }
    .form{
        color: white;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        z-index: 100;
        width: 100%;
        max-width: 400px;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,0.2);
        border-radius: 20px;
        padding: 40px 30px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.4);
        transition: transform 0.3s, box-shadow 0.3s;
    }
    .form:hover{
        transform: translateY(-5px);
        box-shadow: 0 25px 50px rgba(0,0,0,0.5);
    }
    .form h1{
        font-size: 2rem;
        margin-bottom: 30px;
        text-align: center;
        font-weight: 300;
        letter-spacing: 2px;
    }
    .form input[type="text"],
    .form input[type="password"]{
        width: 100%;
        padding: 15px 20px;
        margin: 10px 0;
        background-color: rgba(255, 255, 255, 0.9);
        border: none;
        border-radius: 50px;
        font-size: 16px;
        transition: all 0.3s;
        outline: none;
    }
    .form input.name-input{
        width: 100%;
        padding: 15px 20px;
        margin: 10px 0;
        background-color: rgba(255, 255, 255, 0.9);
        border: none;
        border-radius: 50px;
        font-size: 16px;
        transition: all 0.3s;
        outline: none;
    }
    .form input:focus{
        background-color: #fff;
        box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.5);
    }
    .form input::placeholder{
        color: #666;
    }
    .bform{
        width: 100%;
        max-width: 200px;
        height: 50px;
        margin-top: 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        border-radius: 50px;
        color: white;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .bform:hover{
        transform: scale(1.05);
        box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
    }
    .bform:active{
        transform: scale(0.98);
    }
    .ivc{
        display: inline-block;
        margin-top: 25px;
        color: rgba(255, 255, 255, 0.8);
        text-decoration: none;
        font-size: 0.9rem;
        transition: color 0.3s;
    }
    .ivc:hover{
        color: #fff;
        text-decoration: underline;
    }
    .error-msg{
        color: #ff6b6b;
        margin-top: 15px;
        font-size: 0.9rem;
        text-align: center;
    }

    /* Mobile optimizations */
    @media (max-width: 480px){
        .Login{
            padding: 15px;
        }
        .form{
            padding: 30px 20px;
            border-radius: 15px;
        .form h1{
            font-size: 1.5rem;
        }
        }
        .form input[type="text"],
        .form input[type="password"]{
            padding: 14px 18px;
            font-size: 16px;
        }
        .bform{
            height: 48px;
            font-size: 0.9rem;
        }
    }

    /* Tablet */
    @media (min-width: 481px) and (max-width: 768px){
        .form{
            max-width: 350px;
            padding: 35px 25px;
        }
    }

    /* Touch device improvements */
    @media (hover: none){
        .bform:hover{
            transform: none;
            box-shadow: none;
        }
        .bform:active{
            transform: scale(0.98);
        }
    }

    /* Prevent zoom on iOS */
    @supports (-webkit-touch-callout: none){
        input[type="text"],
        input[type="password"]{
            font-size: 16px;
        }
    }
</style>
</head>
<body>
