<!DOCTYPE html>
<html>
<head>
  <style>
    body{font-family:monospace;padding:10px;background:#2d2d2d;color:white;margin:0}
    textarea{width:100%;height:300px;background:#1a1a1a;color:#0f0;border:1px solid #444;padding:10px;box-sizing:border-box;resize:none}
    button{background:#4caf50;border:none;padding:10px 20px;color:white;cursor:pointer;margin:5px 0}
    button:hover{background:#45a049}
  </style>
</head>
<body>
  <h3>Notepad</h3>
  <form method="post">
    <textarea name="content" placeholder="Write here..."><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
    <button type="submit">Save</button>
  </form>
  <?php if(isset($_POST['content'])): ?>
    <p style="color:#4caf50">Content saved!</p>
  <?php endif; ?>
</body>
</html>