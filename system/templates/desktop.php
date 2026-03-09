<?php 
require_once(OS_STYLES_DIR."desktop_styles.php");

function getAppInfo(string $path): ?array {
    $infoFile = $path . "/info.json";
    if (!file_exists($infoFile)) {
        return null;
    }
    $info = json_decode(file_get_contents($infoFile), true);
    if (!$info || !isset($info['name'])) {
        return null;
    }
    return $info;
}

function getDesktopApps(): array {
    $apps = [];
    $userAppsFile = OS_USER_DIR . "/apps.desk";
    
    if (!file_exists($userAppsFile)) {
        return $apps;
    }
    
    $lines = file($userAppsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        $type = 'user';
        $appName = $line;
        
        if (strpos($line, '[extr]') === 0) {
            $type = 'external';
            $appName = substr($line, 6);
        } elseif (strpos($line, '[user]') === 0) {
            $appName = substr($line, 6);
        }
        
        $appPath = '';
        if ($type === 'external') {
            $appPath = OS_PROGRAMS_DIR . $appName;
        } else {
            $appPath = OS_USER_DIR . "/programs/" . $appName;
        }
        
        $appInfo = getAppInfo($appPath);
        if ($appInfo) {
            $apps[] = [
                'name' => $appName,
                'displayName' => $appInfo['name'],
                'type' => $type,
                'path' => $appPath,
                'info' => $appInfo
            ];
        }
    }
    
    return $apps;
}

$desktopApps = getDesktopApps();
?>
<body class="desktop" onload="requestFullScreen()">
  <div class="desktop-icons">
    <?php foreach ($desktopApps as $app): ?>
      <div class="app-icon" onclick="openApp('<?= htmlspecialchars($app['name']) ?>', '<?= htmlspecialchars($app['info']['name']) ?>', '<?= htmlspecialchars($app['type']) ?>')">
        <?php if (isset($app['info']['icon'])): ?>
          <img src="<?= htmlspecialchars($app['path'] . '/' . $app['info']['icon']) ?>" alt="<?= htmlspecialchars($app['info']['name']) ?>" onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 48 48%22><rect fill=%22%23444%22 width=%2248%22 height=%2248%22 rx=%228%22/><text x=%2224%22 y=%2230%22 text-anchor=%22middle%22 fill=%22white%22 font-size=%2220%22><?= substr($app['info']['name'], 0, 1) ?></text></svg>'">
        <?php else: ?>
          <img src="data:image/svg+xml,<?= urlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48"><rect fill="#444" width="48" height="48" rx="8"/><text x="24" y="30" text-anchor="middle" fill="white" font-size="20">' . substr($app['info']['name'], 0, 1) . '</text></svg>') ?>" alt="<?= htmlspecialchars($app['info']['name']) ?>">
        <?php endif; ?>
        <span><?= htmlspecialchars($app['info']['name']) ?></span>
      </div>
    <?php endforeach; ?>
  </div>

  <div id="windows"></div>

  <footer class="taskbar">
    <button class="start-button" onclick="toggleStartMenu()">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="white">
        <path d="M3 3h8v8H3V3zm10 0h8v8h-8V3zM3 13h8v8H3v-8zm10 0h8v8h-8v-8z"/>
      </svg>
    </button>
    
    <div class="start-menu" id="startMenu">
      <div class="start-menu-header">BWebOS</div>
      <div class="start-menu-apps" id="startMenuApps">
        <?php foreach ($desktopApps as $app): ?>
          <div class="start-menu-app" onclick="openApp('<?= htmlspecialchars($app['name']) ?>', '<?= htmlspecialchars($app['info']['name']) ?>', '<?= htmlspecialchars($app['type']) ?>'); toggleStartMenu();">
            <?php if (isset($app['info']['icon'])): ?>
              <img src="<?= htmlspecialchars($app['path'] . '/' . $app['info']['icon']) ?>" alt="">
            <?php else: ?>
              <img src="data:image/svg+xml,<?= urlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><rect fill="#444" width="32" height="32" rx="4"/><text x="16" y="22" text-anchor="middle" fill="white" font-size="14">' . substr($app['info']['name'], 0, 1) . '</text></svg>') ?>" alt="">
            <?php endif; ?>
            <span><?= htmlspecialchars($app['info']['name']) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="start-menu-footer">
        <div class="user-info"><?= htmlspecialchars($_SESSION['username']) ?></div>
        <button class="power-btn" onclick="powerOff()" title="Apagar">
          <svg viewBox="0 0 24 24"><path d="M13 3h-2v10h2V3zm4.83 2.17l-1.42 1.42C17.99 7.86 19 9.81 19 12c0 3.87-3.13 7-7 7s-7-3.13-7-7c0-2.19 1.01-4.14 2.58-5.42L6.17 5.17C4.23 6.82 3 9.26 3 12c0 4.97 4.03 9 9 9s9-4.03 9-9c0-2.74-1.23-5.18-3.17-6.83z"/></svg>
        </button>
      </div>
    </div>
    
    <div class="taskbar-apps" id="taskbarApps"></div>
  </footer>

  <script>
    function requestFullScreen() {
      const elem = document.documentElement;
      if (elem.requestFullscreen) {
        elem.requestFullscreen().catch(() => {});
      } else if (elem.webkitRequestFullscreen) {
        elem.webkitRequestFullscreen();
      }
    }
    
    const appWindows = {};
    
    function openApp(appName, displayName, type) {
      if (appWindows[appName]) {
        const win = document.getElementById('window-' + appName);
        win.classList.add('active');
        win.style.zIndex = 100;
        return;
      }
      
      const windowsDiv = document.getElementById('windows');
      const winId = 'window-' + appName;
      
      const appPath = type === 'external' 
        ? 'system/programs/' + appName + '/' 
        : 'users/<?= $_SESSION['username'] ?>/programs/' + appName + '/';
      
      const iframe = document.createElement('iframe');
      iframe.style.cssText = 'width:100%;height:100%;border:none;background:#1a1a1a;';
      
      let entryFile = 'index.html';
      if (type === 'external' || type === 'user') {
        fetch(appPath + 'info.json')
          .then(r => r.json())
          .then(info => {
            entryFile = info.entry || 'index.html';
            iframe.src = appPath + entryFile;
          })
          .catch(() => {
            iframe.src = appPath + entryFile;
          });
      }
      
      const win = document.createElement('div');
      win.id = winId;
      win.className = 'window active';
      win.style.cssText = 'width:400px;height:350px;top:50px;left:50px;z-index:100;';
      win.innerHTML = `
        <div class="window-header">
          <span class="window-title">${displayName}</span>
          <button class="window-close" onclick="closeApp('${appName}')">×</button>
        </div>
        <div class="window-content" style="height:calc(100% - 40px);padding:0;">
        </div>
      `;
      
      win.querySelector('.window-content').appendChild(iframe);
      windowsDiv.appendChild(win);
      
      appWindows[appName] = win;
      
      makeDraggable(win);
      
      addToTaskbar(appName, displayName, type);
    }
    
    function closeApp(appName) {
      if (appWindows[appName]) {
        appWindows[appName].remove();
        delete appWindows[appName];
        
        const taskbarApp = document.getElementById('taskbar-' + appName);
        if (taskbarApp) taskbarApp.remove();
      }
    }
    
    function addToTaskbar(appName, displayName, type) {
      const taskbar = document.getElementById('taskbarApps');
      const existing = document.getElementById('taskbar-' + appName);
      if (existing) return;
      
      const btn = document.createElement('div');
      btn.id = 'taskbar-' + appName;
      btn.className = 'taskbar-app';
      btn.style.background = '#444';
      btn.style.borderRadius = '8px';
      btn.innerHTML = '<span style="display:flex;align-items:center;justify-content:center;height:100%;color:white;font-size:12px;">' + displayName.charAt(0) + '</span>';
      btn.onclick = () => openApp(appName, displayName, type);
      taskbar.appendChild(btn);
    }
    
    function toggleStartMenu() {
      document.getElementById('startMenu').classList.toggle('active');
    }
    
    function makeDraggable(el) {
      const header = el.querySelector('.window-header');
      let isDragging = false, offsetX, offsetY;
      
      header.onmousedown = (e) => {
        isDragging = true;
        offsetX = e.clientX - el.offsetLeft;
        offsetY = e.clientY - el.offsetTop;
      };
      
      document.onmousemove = (e) => {
        if (!isDragging) return;
        el.style.left = (e.clientX - offsetX) + 'px';
        el.style.top = (e.clientY - offsetY) + 'px';
      };
      
      document.onmouseup = () => { isDragging = false; };
    }
    
    function powerOff() {
      if (confirm('¿Seguro que quieres apagar?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="command" value="poweroff">';
        document.body.appendChild(form);
        form.submit();
      }
    }
    
    document.addEventListener('click', function(e) {
      const startMenu = document.getElementById('startMenu');
      const startButton = document.querySelector('.start-button');
      if (!startMenu.contains(e.target) && !startButton.contains(e.target)) {
        startMenu.classList.remove('active');
      }
    });
  </script>
</body>