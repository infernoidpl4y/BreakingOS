<?php
session_start();

class HyperTerminal {
    private $cwd, $historyFile, $maxHistory = 2000, $aliases = [], $theme = 'dark';
    private $restricted = ['rm -rf', 'mkfs', 'dd if=', '> /dev/sd', 'chmod 000', 'sudo', 'su', 'passwd'];
    
    public function __construct() {
        $this->cwd = $_SESSION['cwd'] ?? getcwd();
        $this->historyFile = sys_get_temp_dir() . '/hyper_' . session_id() . '.json';
        $this->aliases = $_SESSION['aliases'] ?? ['ll' => 'ls -la', 'la' => 'ls -a', '..' => 'cd ..', '...' => 'cd ../..'];
        $this->theme = $_SESSION['theme'] ?? 'dark';
        $this->init();
        
        if (isset($_GET['ajax'])) {
            $this->handleAjax();
        }
    }
    
    private function init() {
        if (!file_exists($this->historyFile)) {
            file_put_contents($this->historyFile, json_encode([]));
        }
    }
    
    private function handleAjax() {
        header('Content-Type: application/json');
        header('X-Powered-By: HyperTerminal Pro');
        
        $action = $_GET['action'] ?? '';
        $cmd = $_GET['cmd'] ?? '';
        
        switch ($action) {
            case 'exec':
                echo json_encode($this->execute($cmd));
                break;
            case 'history':
                echo json_encode($this->getHistory());
                break;
            case 'suggest':
                echo json_encode($this->suggest($cmd));
                break;
            case 'clear_history':
                file_put_contents($this->historyFile, json_encode([]));
                echo json_encode(['ok' => true]);
                break;
            case 'theme':
                $_SESSION['theme'] = $cmd;
                echo json_encode(['ok' => true]);
                break;
            case 'alias':
                echo json_encode($this->aliases);
                break;
            default:
                echo json_encode(['error' => 'Unknown action']);
        }
        exit;
    }
    
    private function execute($command) {
        $start = microtime(true);
        $command = trim($command);
        
        if (empty($command)) {
            return ['output' => [], 'cwd' => $this->cwd, 'time' => 0];
        }
        
        // Security check
        foreach ($this->restricted as $pattern) {
            if (stripos($command, $pattern) !== false) {
                return $this->response(["⛔ Comando bloqueado por seguridad: {$pattern}"], $start);
            }
        }
        
        // Save to history
        $this->saveHistory($command);
        
        // Built-in commands
        if ($command === 'clear' || $command === 'cls') {
            return $this->response(['__CLEAR__'], $start);
        }
        
        if ($command === 'pwd') {
            return $this->response([$this->cwd], $start);
        }
        
        if ($command === 'history') {
            return $this->response($this->formatHistory(), $start);
        }
        
        if ($command === 'alias') {
            return $this->response($this->formatAliases(), $start);
        }
        
        if (preg_match('/^cd\s+(.+)$/', $command, $matches)) {
            return $this->changeDir($matches[1], $start);
        }
        
        if (preg_match('/^ls(\s+.*)?$/', $command) || preg_match('/^dir(\s+.*)?$/', $command)) {
            return $this->listDir($command, $start);
        }
        
        if (preg_match('/^alias\s+(\w+)=["\']?(.+)["\']?$/', $command, $matches)) {
            return $this->addAlias($matches[1], $matches[2], $start);
        }
        
        if (preg_match('/^unalias\s+(\w+)$/', $command, $matches)) {
            return $this->removeAlias($matches[1], $start);
        }
        
        // Apply aliases
        $cmdParts = explode(' ', $command, 2);
        if (isset($this->aliases[$cmdParts[0]])) {
            $aliasCmd = $this->aliases[$cmdParts[0]];
            if (isset($cmdParts[1])) {
                $command = $aliasCmd . ' ' . $cmdParts[1];
            } else {
                $command = $aliasCmd;
            }
        }
        
        // Execute system command
        return $this->runSystemCommand($command, $start);
    }
    
    private function runSystemCommand($command, $start) {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];
        
        $env = array_merge($_ENV, [
            'PWD' => $this->cwd,
            'TERM' => 'xterm-256color',
            'LANG' => 'en_US.UTF-8',
            'SHELL' => '/bin/bash'
        ]);
        
        $process = proc_open($command, $descriptors, $pipes, $this->cwd, $env);
        
        if (!is_resource($process)) {
            return $this->response(["❌ No se pudo ejecutar el comando"], $start);
        }
        
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        
        $returnCode = proc_close($process);
        
        $lines = [];
        
        if (!empty($output)) {
            $lines = array_merge($lines, explode("\n", rtrim($output, "\n")));
        }
        
        if (!empty($error)) {
            $lines[] = '';
            $lines[] = '╔══════════════════════════════╗';
            $lines[] = '║         ERROR OUTPUT         ║';
            $lines[] = '╚══════════════════════════════╝';
            $lines = array_merge($lines, explode("\n", rtrim($error, "\n")));
        }
        
        if (empty($output) && empty($error)) {
            $lines[] = $returnCode === 0 ? '✅ Comando ejecutado correctamente' : "⚠️ Código de salida: {$returnCode}";
        }
        
        return $this->response($lines, $start);
    }
    
    private function changeDir($path, $start) {
        $path = trim($path);
        
        if ($path === '') {
            return $this->response([], $start);
        }
        
        // Handle ~
        if ($path[0] === '~') {
            $home = $_SERVER['HOME'] ?? getenv('USERPROFILE') ?? '/home/' . ($_SERVER['USER'] ?? 'user');
            $path = $home . substr($path, 1);
        }
        
        // Handle relative paths
        if ($path[0] !== DIRECTORY_SEPARATOR) {
            $path = $this->cwd . DIRECTORY_SEPARATOR . $path;
        }
        
        $realPath = realpath($path);
        
        if ($realPath && is_dir($realPath) && chdir($realPath)) {
            $this->cwd = $realPath;
            $_SESSION['cwd'] = $realPath;
            return $this->response(["📂 {$realPath}"], $start);
        }
        
        return $this->response(["❌ Directorio no encontrado: {$path}"], $start);
    }
    
    private function listDir($command, $start) {
        $showAll = strpos($command, '-a') !== false || strpos($command, '/a') !== false;
        $long = strpos($command, '-l') !== false || strpos($command, '/l') !== false;
        
        $files = scandir($this->cwd);
        $result = [];
        
        if ($long) {
            $result[] = "total " . count($files);
        }
        
        foreach ($files as $file) {
            if (!$showAll && $file[0] === '.') {
                continue;
            }
            
            $fullPath = $this->cwd . DIRECTORY_SEPARATOR . $file;
            
            if ($long) {
                $perms = $this->getPermissions($fullPath);
                $size = $this->formatSize(filesize($fullPath));
                $mtime = date('M d H:i', filemtime($fullPath));
                $icon = $this->getFileIcon($fullPath);
                $result[] = "{$perms} {$size} {$mtime} {$icon} {$file}";
            } else {
                $icon = $this->getFileIcon($fullPath);
                $result[] = "{$icon} {$file}";
            }
        }
        
        return $this->response($result, $start);
    }
    
    private function getFileIcon($path) {
        if (is_dir($path)) return '📁';
        if (is_link($path)) return '🔗';
        if (is_executable($path)) return '⚙️';
        
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        $icons = [
            'php' => '🐘', 'js' => '🟨', 'html' => '🌐', 'css' => '🎨',
            'json' => '📋', 'xml' => '📰', 'md' => '📝', 'txt' => '📄',
            'jpg' => '🖼️', 'png' => '🖼️', 'gif' => '🎞️', 'svg' => '✨',
            'mp3' => '🎵', 'mp4' => '🎬', 'zip' => '📦', 'tar' => '📦',
            'pdf' => '📕', 'doc' => '📘', 'xls' => '📊'
        ];
        
        return $icons[$ext] ?? '📄';
    }
    
    private function getPermissions($path) {
        $perms = fileperms($path);
        $info = '';
        
        $info .= (is_readable($path) ? 'r' : '-');
        $info .= (is_writable($path) ? 'w' : '-');
        $info .= (is_executable($path) ? 'x' : '-');
        
        return $info;
    }
    
    private function formatSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 1) . $units[$i];
    }
    
    private function addAlias($name, $value, $start) {
        $this->aliases[$name] = $value;
        $_SESSION['aliases'] = $this->aliases;
        return $this->response(["✅ Alias creado: {$name} = {$value}"], $start);
    }
    
    private function removeAlias($name, $start) {
        if (isset($this->aliases[$name])) {
            unset($this->aliases[$name]);
            $_SESSION['aliases'] = $this->aliases;
            return $this->response(["✅ Alias eliminado: {$name}"], $start);
        }
        return $this->response(["❌ Alias no encontrado: {$name}"], $start);
    }
    
    private function formatHistory() {
        $history = $this->getHistory();
        $result = ['┌────────────────────────────────────┐', '│         HISTORIAL DE COMANDOS       │', '└────────────────────────────────────┘'];
        
        foreach (array_slice($history, 0, 50) as $i => $item) {
            $date = date('H:i:s', $item['time']);
            $result[] = sprintf("%3d  [%s]  %s", $i + 1, $date, $item['cmd']);
        }
        
        $result[] = '';
        $result[] = "Total: " . count($history) . " comandos";
        
        return $result;
    }
    
    private function formatAliases() {
        $result = ['┌────────────────────────────────────┐', '│              ALIASES                │', '└────────────────────────────────────┘'];
        
        foreach ($this->aliases as $name => $cmd) {
            $result[] = sprintf("  %-10s = %s", $name, $cmd);
        }
        
        return $result;
    }
    
    private function saveHistory($cmd) {
        $history = $this->getHistory();
        array_unshift($history, [
            'cmd' => $cmd,
            'time' => time(),
            'cwd' => $this->cwd
        ]);
        
        $history = array_slice($history, 0, $this->maxHistory);
        file_put_contents($this->historyFile, json_encode($history, JSON_PRETTY_PRINT));
    }
    
    public function getHistory() {
        $content = file_get_contents($this->historyFile);
        return json_decode($content, true) ?: [];
    }
    
    public function suggest($prefix) {
        $prefix = strtolower(trim($prefix));
        if (strlen($prefix) < 2) return [];
        
        $commands = [
            'cd', 'ls', 'pwd', 'clear', 'history', 'alias', 'unalias',
            'cat', 'echo', 'grep', 'find', 'head', 'tail', 'wc',
            'mkdir', 'rmdir', 'touch', 'rm', 'cp', 'mv', 'chmod',
            'php', 'composer', 'npm', 'node', 'git', 'python',
            'date', 'whoami', 'hostname', 'uname', 'df', 'du', 'free'
        ];
        
        $suggestions = array_merge($commands, array_keys($this->aliases));
        
        return array_values(array_filter($suggestions, function($cmd) use ($prefix) {
            return strpos(strtolower($cmd), $prefix) === 0;
        }));
    }
    
    private function response($lines, $start) {
        return [
            'output' => $lines,
            'cwd' => $this->cwd,
            'time' => round(microtime(true) - $start, 3),
            'prompt' => $this->getPrompt()
        ];
    }
    
    public function getPrompt() {
        $user = $_SERVER['USER'] ?? $_SERVER['USERNAME'] ?? 'user';
        $host = gethostname();
        $path = str_replace(
            $_SERVER['HOME'] ?? getenv('USERPROFILE') ?? '',
            '~',
            $this->cwd
        );
        
        return [
            'html' => "<span class='prompt-user'>{$user}</span><span class='prompt-at'>@</span><span class='prompt-host'>{$host}</span><span class='prompt-colon'>:</span><span class='prompt-path'>{$path}</span><span class='prompt-dollar'>$</span>",
            'user' => $user,
            'host' => $host,
            'path' => $path
        ];
    }
    
    public function getTheme() {
        return $this->theme;
    }
}

$terminal = new HyperTerminal();
?>
<!DOCTYPE html>
<html lang="es" data-theme="<?= $terminal->getTheme() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HyperTerminal Pro · PHP Edition</title>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            /* Dark theme (default) */
            --bg-primary: #0a0c10;
            --bg-secondary: #0f1117;
            --bg-tertiary: #1a1c25;
            --bg-input: #1e212a;
            --text-primary: #e2e8f0;
            --text-secondary: #94a3b8;
            --text-dim: #64748b;
            --accent-primary: #3b82f6;
            --accent-secondary: #8b5cf6;
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
            --border: #2d313c;
            --shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            --glow: 0 0 30px rgba(59, 130, 246, 0.3);
            --font: 'JetBrains Mono', monospace;
        }

        [data-theme="light"] {
            --bg-primary: #f8fafc;
            --bg-secondary: #ffffff;
            --bg-tertiary: #f1f5f9;
            --bg-input: #e2e8f0;
            --text-primary: #0f172a;
            --text-secondary: #334155;
            --text-dim: #64748b;
            --accent-primary: #2563eb;
            --accent-secondary: #7c3aed;
            --success: #059669;
            --warning: #d97706;
            --error: #dc2626;
            --border: #cbd5e1;
            --shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.2);
            --glow: 0 0 30px rgba(37, 99, 235, 0.2);
        }

        [data-theme="matrix"] {
            --bg-primary: #000000;
            --bg-secondary: #0c0c0c;
            --bg-tertiary: #1a1a1a;
            --bg-input: #0f0f0f;
            --text-primary: #00ff41;
            --text-secondary: #00cc33;
            --text-dim: #008800;
            --accent-primary: #00ff88;
            --accent-secondary: #00ffff;
            --success: #00ff41;
            --warning: #ffff00;
            --error: #ff0040;
            --border: #00aa00;
            --shadow: 0 25px 50px -12px #00ff411a;
            --glow: 0 0 30px #00ff4140;
        }

        [data-theme="hacker"] {
            --bg-primary: #0d0208;
            --bg-secondary: #1a0f1a;
            --bg-tertiary: #2a152a;
            --bg-input: #1f0f1f;
            --text-primary: #ff2a6d;
            --text-secondary: #d1f7ff;
            --text-dim: #b0e0e6;
            --accent-primary: #05d9e8;
            --accent-secondary: #ff2a6d;
            --success: #05d9e8;
            --warning: #ff2a6d;
            --error: #d3004c;
            --border: #4a1e4a;
            --shadow: 0 25px 50px -12px #ff2a6d80;
            --glow: 0 0 30px #05d9e880;
        }

        body {
            font-family: var(--font);
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            transition: background 0.3s, color 0.3s;
        }

        .terminal {
            width: 100%;
            max-width: 1200px;
            height: 85vh;
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: var(--shadow), var(--glow);
            display: flex;
            flex-direction: column;
            backdrop-filter: blur(10px);
        }

        .header {
            padding: 16px 24px;
            background: linear-gradient(135deg, var(--bg-tertiary), var(--bg-secondary));
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .window-controls {
            display: flex;
            gap: 10px;
        }

        .window-dot {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s;
        }

        .window-dot.red {
            background: #ff5f56;
            box-shadow: 0 0 15px #ff5f5680;
        }
        .window-dot.yellow {
            background: #ffbd2e;
            box-shadow: 0 0 15px #ffbd2e80;
        }
        .window-dot.green {
            background: #27ca40;
            box-shadow: 0 0 15px #27ca4080;
        }

        .window-dot:hover {
            transform: scale(1.2);
            filter: brightness(1.2);
        }

        .header-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-dim);
            background: var(--bg-primary);
            padding: 6px 16px;
            border-radius: 30px;
            border: 1px solid var(--border);
            letter-spacing: 0.5px;
        }

        .header-path {
            font-size: 12px;
            color: var(--text-secondary);
            background: var(--bg-input);
            padding: 4px 12px;
            border-radius: 20px;
            margin-left: auto;
        }

        .body {
            flex: 1;
            overflow-y: auto;
            padding: 24px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            scroll-behavior: smooth;
        }

        .body::-webkit-scrollbar {
            width: 8px;
        }

        .body::-webkit-scrollbar-track {
            background: var(--bg-primary);
        }

        .body::-webkit-scrollbar-thumb {
            background: var(--border);
            border-radius: 4px;
        }

        .body::-webkit-scrollbar-thumb:hover {
            background: var(--accent-primary);
        }

        .welcome {
            padding: 24px;
            background: var(--bg-tertiary);
            border-radius: 16px;
            border-left: 4px solid var(--accent-primary);
            margin-bottom: 20px;
            animation: slideDown 0.5s ease;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .welcome h2 {
            color: var(--accent-primary);
            font-size: 16px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .welcome pre {
            color: var(--text-secondary);
            font-size: 12px;
            line-height: 1.8;
            margin: 12px 0;
        }

        .welcome-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
            margin-top: 16px;
        }

        .info-badge {
            background: var(--bg-input);
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 12px;
            color: var(--text-secondary);
        }

        .output-block {
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateX(-10px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .command-line {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 6px;
            font-size: 14px;
        }

        .command-prompt {
            color: var(--success);
            font-weight: 600;
        }

        .command-text {
            color: var(--accent-primary);
            font-weight: 500;
            word-break: break-word;
        }

        .command-time {
            font-size: 11px;
            color: var(--text-dim);
            margin-left: auto;
        }

        .output-content {
            padding-left: 28px;
            border-left: 2px solid var(--border);
            display: flex;
            flex-direction: column;
            gap: 3px;
            margin-bottom: 16px;
            position: relative;
        }

        .output-line {
            font-size: 14px;
            line-height: 1.7;
            word-break: break-word;
            padding: 2px 8px 2px 0;
            transition: all 0.2s;
            cursor: pointer;
        }

        .output-line:hover {
            background: var(--bg-tertiary);
        }

        .output-line.error {
            color: var(--error);
            background: rgba(239, 68, 68, 0.1);
            padding: 6px 12px;
            border-radius: 6px;
            margin: 4px 0;
        }

        .output-line.success {
            color: var(--success);
        }

        .output-line.warning {
            color: var(--warning);
        }

        .output-line.meta {
            color: var(--text-dim);
            font-style: italic;
        }

        .output-line.file {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .copy-hint {
            position: absolute;
            right: 8px;
            top: 0;
            opacity: 0;
            transition: opacity 0.2s;
            font-size: 12px;
            color: var(--text-dim);
            cursor: pointer;
        }

        .output-content:hover .copy-hint {
            opacity: 1;
        }

        .input-area {
            padding: 20px 24px;
            background: var(--bg-tertiary);
            border-top: 1px solid var(--border);
        }

        .input-wrapper {
            display: flex;
            align-items: center;
            gap: 16px;
            background: var(--bg-input);
            border: 2px solid var(--border);
            border-radius: 16px;
            padding: 4px 4px 4px 20px;
            transition: all 0.3s;
            position: relative;
        }

        .input-wrapper:focus-within {
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 4px var(--accent-primary)20;
            transform: translateY(-2px);
        }

        .prompt {
            font-size: 15px;
            font-weight: 600;
            white-space: nowrap;
        }

        .prompt-user { color: var(--accent-primary); }
        .prompt-at { color: var(--text-dim); }
        .prompt-host { color: var(--success); }
        .prompt-colon { color: var(--text-dim); }
        .prompt-path { color: var(--warning); }
        .prompt-dollar { color: var(--error); margin-left: 2px; }

        .command-input {
            flex: 1;
            background: transparent;
            border: none;
            color: var(--text-primary);
            font-family: var(--font);
            font-size: 15px;
            padding: 14px 0;
            outline: none;
        }

        .command-input::placeholder {
            color: var(--text-dim);
            opacity: 0.5;
        }

        .exec-button {
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
            border: none;
            border-radius: 14px;
            padding: 12px 28px;
            color: white;
            font-family: var(--font);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .exec-button:hover {
            transform: scale(1.05);
            box-shadow: 0 0 30px var(--accent-primary)80;
        }

        .exec-button:active {
            transform: scale(0.95);
        }

        .exec-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .suggestions {
            position: absolute;
            bottom: 100%;
            left: 0;
            right: 0;
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 12px;
            max-height: 250px;
            overflow-y: auto;
            z-index: 100;
            display: none;
            margin-bottom: 8px;
            box-shadow: var(--shadow);
        }

        .suggestion-item {
            padding: 12px 20px;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
            color: var(--text-primary);
            border-bottom: 1px solid var(--border);
        }

        .suggestion-item:last-child {
            border-bottom: none;
        }

        .suggestion-item:hover,
        .suggestion-item.active {
            background: var(--accent-primary);
            color: white;
        }

        .status-bar {
            padding: 12px 24px;
            background: var(--bg-primary);
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
        }

        .status-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--success);
            box-shadow: 0 0 15px var(--success);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.2); }
        }

        .status-dot.busy {
            background: var(--warning);
            box-shadow: 0 0 15px var(--warning);
            animation: pulse 1s infinite;
        }

        .status-dot.error {
            background: var(--error);
            box-shadow: 0 0 15px var(--error);
        }

        .cwd-badge {
            background: var(--bg-tertiary);
            padding: 4px 16px;
            border-radius: 30px;
            border: 1px solid var(--border);
        }

        .time-badge {
            background: var(--bg-tertiary);
            padding: 4px 12px;
            border-radius: 30px;
            border: 1px solid var(--border);
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .action-btn {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-dim);
            padding: 6px 14px;
            border-radius: 30px;
            font-family: var(--font);
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .action-btn:hover {
            border-color: var(--accent-primary);
            color: var(--accent-primary);
            background: var(--accent-primary)10;
            transform: translateY(-2px);
        }

        .action-btn.danger:hover {
            border-color: var(--error);
            color: var(--error);
            background: var(--error)10;
        }

        .theme-switcher {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: var(--bg-tertiary);
            border: 1px solid var(--border);
            border-radius: 60px;
            padding: 6px;
            display: flex;
            gap: 4px;
            z-index: 200;
            box-shadow: var(--shadow);
        }

        .theme-option {
            width: 40px;
            height: 40px;
            border-radius: 40px;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .theme-option:hover {
            transform: scale(1.2);
        }

        .theme-option.dark { background: #0a0c10; color: #e2e8f0; }
        .theme-option.light { background: #f8fafc; color: #0f172a; }
        .theme-option.matrix { background: #000; color: #00ff41; }
        .theme-option.hacker { background: #0d0208; color: #ff2a6d; }

        .help-panel {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            z-index: 300;
            box-shadow: var(--shadow), var(--glow);
            display: none;
        }

        .help-panel.active {
            display: block;
            animation: scaleIn 0.3s ease;
        }

        @keyframes scaleIn {
            from { transform: translate(-50%, -50%) scale(0.9); opacity: 0; }
            to { transform: translate(-50%, -50%) scale(1); opacity: 1; }
        }

        .help-panel h3 {
            color: var(--accent-primary);
            margin-bottom: 20px;
            font-size: 20px;
        }

        .help-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .help-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .help-key {
            background: var(--bg-input);
            padding: 8px 12px;
            border-radius: 8px;
            font-family: var(--font);
            font-size: 12px;
            font-weight: 600;
            color: var(--accent-primary);
            border: 1px solid var(--border);
            text-align: center;
        }

        .help-desc {
            font-size: 11px;
            color: var(--text-dim);
            text-align: center;
        }

        .overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
            z-index: 250;
            display: none;
        }

        .overlay.active {
            display: block;
        }

        .history-panel {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 30px;
            max-width: 600px;
            width: 90%;
            max-height: 70vh;
            overflow-y: auto;
            z-index: 300;
            box-shadow: var(--shadow);
            display: none;
        }

        .history-panel.active {
            display: block;
        }

        .history-item {
            padding: 8px 12px;
            border-bottom: 1px solid var(--border);
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .history-item:hover {
            background: var(--bg-tertiary);
            color: var(--accent-primary);
        }

        .history-time {
            color: var(--text-dim);
            font-size: 11px;
            margin-left: 10px;
        }

        @media (max-width: 768px) {
            .terminal {
                height: 100vh;
                border-radius: 0;
                padding: 0;
            }
            
            .header {
                padding: 12px 16px;
            }
            
            .header-path {
                display: none;
            }
            
            .input-wrapper {
                flex-wrap: wrap;
            }
            
            .exec-button {
                width: 100%;
                margin-top: 8px;
            }
            
            .status-bar {
                flex-wrap: wrap;
                gap: 12px;
            }
            
            .status-left {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <div class="terminal">
        <div class="header">
            <div class="window-controls">
                <div class="window-dot red" onclick="window.close()"></div>
                <div class="window-dot yellow" onclick="document.querySelector('.body').innerHTML = ''"></div>
                <div class="window-dot green" onclick="location.reload()"></div>
            </div>
            <span class="header-title">HYPERTERMINAL PRO · v5.0</span>
            <span class="header-path" id="headerPath"><?= $terminal->getPrompt()['path'] ?></span>
        </div>

        <div class="body" id="body">
            <div class="welcome">
                <h2>
                    <span>⚡ HYPER TERMINAL PRO</span>
                </h2>
                <pre>
┌─────────────────────────────────────────────────┐
│  PHP <?= phpversion() ?> · Sistema: <?= php_uname('s') ?> · <?= $terminal->getPrompt()['user'] ?>@<?= gethostname() ?>  │
└─────────────────────────────────────────────────┘
                </pre>
                <div class="welcome-info">
                    <div class="info-badge">📁 Directorio: <?= $terminal->getPrompt()['path'] ?></div>
                    <div class="info-badge">⚙️ Comandos: 100+</div>
                    <div class="info-badge">🎨 Temas: 4</div>
                    <div class="info-badge">⌨️ Atajos: ?</div>
                </div>
            </div>
            <div id="output"></div>
        </div>

        <div class="input-area">
            <div class="input-wrapper">
                <span class="prompt" id="prompt"><?= $terminal->getPrompt()['html'] ?></span>
                <input type="text" class="command-input" id="input" placeholder="Escribe tu comando..." autocomplete="off" spellcheck="false" autofocus>
                <div class="suggestions" id="suggestions"></div>
                <button class="exec-button" id="execBtn">EJECUTAR</button>
            </div>
        </div>

        <div class="status-bar">
            <div class="status-left">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <div class="status-dot" id="statusDot"></div>
                    <span id="statusText">LISTO</span>
                </div>
                <span class="cwd-badge" id="cwdBadge"><?= $terminal->getPrompt()['path'] ?></span>
                <span class="time-badge" id="timeBadge">0.000s</span>
                <span class="time-badge" id="historyCount">0 cmd</span>
            </div>
            <div class="action-buttons">
                <button class="action-btn" id="helpBtn">AYUDA</button>
                <button class="action-btn" id="historyBtn">HISTORIAL</button>
                <button class="action-btn danger" id="clearBtn">LIMPIAR</button>
            </div>
        </div>
    </div>

    <div class="theme-switcher">
        <button class="theme-option dark" onclick="setTheme('dark')">🌙</button>
        <button class="theme-option light" onclick="setTheme('light')">☀️</button>
        <button class="theme-option matrix" onclick="setTheme('matrix')">💚</button>
        <button class="theme-option hacker" onclick="setTheme('hacker')">💜</button>
    </div>

    <div class="overlay" id="overlay"></div>

    <div class="help-panel" id="helpPanel">
        <h3>⌨️ Atajos de Teclado</h3>
        <div class="help-grid">
            <div class="help-item">
                <span class="help-key">Enter</span>
                <span class="help-desc">Ejecutar comando</span>
            </div>
            <div class="help-item">
                <span class="help-key">↑ / ↓</span>
                <span class="help-desc">Navegar historial</span>
            </div>
            <div class="help-item">
                <span class="help-key">Tab</span>
                <span class="help-desc">Autocompletar</span>
            </div>
            <div class="help-item">
                <span class="help-key">Ctrl + L</span>
                <span class="help-desc">Limpiar pantalla</span>
            </div>
            <div class="help-item">
                <span class="help-key">Esc</span>
                <span class="help-desc">Cerrar sugerencias</span>
            </div>
            <div class="help-item">
                <span class="help-key">? / F1</span>
                <span class="help-desc">Mostrar ayuda</span>
            </div>
        </div>
        <div style="text-align: right;">
            <button class="action-btn" onclick="closeHelp()">Cerrar</button>
        </div>
    </div>

    <div class="history-panel" id="historyPanel">
        <h3 style="margin-bottom: 20px;">📜 Historial de Comandos</h3>
        <div id="historyList"></div>
        <div style="text-align: right; margin-top: 20px;">
            <button class="action-btn danger" onclick="clearHistory()">Borrar Historial</button>
            <button class="action-btn" onclick="closeHistory()">Cerrar</button>
        </div>
    </div>

    <script>
        (function() {
            const body = document.getElementById('body');
            const output = document.getElementById('output');
            const input = document.getElementById('input');
            const execBtn = document.getElementById('execBtn');
            const prompt = document.getElementById('prompt');
            const cwdBadge = document.getElementById('cwdBadge');
            const timeBadge = document.getElementById('timeBadge');
            const statusDot = document.getElementById('statusDot');
            const statusText = document.getElementById('statusText');
            const suggestions = document.getElementById('suggestions');
            const helpPanel = document.getElementById('helpPanel');
            const historyPanel = document.getElementById('historyPanel');
            const overlay = document.getElementById('overlay');
            const historyCount = document.getElementById('historyCount');

            let history = [];
            let historyIndex = -1;
            let isLoading = false;

            function scrollToBottom() {
                body.scrollTop = body.scrollHeight;
            }

            function addOutput(command, lines, time = null) {
                const block = document.createElement('div');
                block.className = 'output-block';
                
                const cmdLine = document.createElement('div');
                cmdLine.className = 'command-line';
                cmdLine.innerHTML = `
                    <span class="command-prompt">❯</span>
                    <span class="command-text">${escapeHtml(command)}</span>
                    ${time ? `<span class="command-time">${time}s</span>` : ''}
                `;
                
                const content = document.createElement('div');
                content.className = 'output-content';
                
                lines.forEach(line => {
                    if (line === '__CLEAR__') {
                        output.innerHTML = '';
                        return;
                    }
                    
                    const lineDiv = document.createElement('div');
                    lineDiv.className = 'output-line';
                    
                    if (typeof line === 'string') {
                        if (line.includes('❌') || line.includes('Error') || line.includes('ERROR')) {
                            lineDiv.classList.add('error');
                        } else if (line.includes('✅') || line.includes('✓')) {
                            lineDiv.classList.add('success');
                        } else if (line.includes('⚠️')) {
                            lineDiv.classList.add('warning');
                        } else if (line.includes('╔') || line.includes('╚') || line.includes('┌') || line.includes('└')) {
                            lineDiv.classList.add('meta');
                        } else if (line.includes('📁') || line.includes('📄') || line.includes('🔗') || line.includes('⚙️')) {
                            lineDiv.classList.add('file');
                        }
                        
                        lineDiv.textContent = line;
                    }
                    
                    content.appendChild(lineDiv);
                });
                
                const copyHint = document.createElement('span');
                copyHint.className = 'copy-hint';
                copyHint.textContent = '📋';
                copyHint.onclick = () => {
                    navigator.clipboard.writeText(content.innerText);
                    copyHint.textContent = '✓';
                    setTimeout(() => copyHint.textContent = '📋', 1500);
                };
                content.appendChild(copyHint);
                
                block.appendChild(cmdLine);
                block.appendChild(content);
                output.appendChild(block);
                
                if (time) {
                    timeBadge.textContent = time + 's';
                }
                
                scrollToBottom();
            }

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            function setBusy(busy) {
                isLoading = busy;
                if (busy) {
                    statusDot.classList.add('busy');
                    statusText.textContent = 'EJECUTANDO...';
                    execBtn.disabled = true;
                    input.disabled = true;
                } else {
                    statusDot.classList.remove('busy', 'error');
                    statusText.textContent = 'LISTO';
                    execBtn.disabled = false;
                    input.disabled = false;
                    input.focus();
                }
            }

            async function executeCommand() {
                const cmd = input.value.trim();
                if (!cmd || isLoading) return;
                
                input.value = '';
                setBusy(true);
                
                try {
                    const response = await fetch(`?ajax=1&action=exec&cmd=${encodeURIComponent(cmd)}`);
                    const data = await response.json();
                    
                    addOutput(cmd, data.output, data.time);
                    
                    if (data.prompt) {
                        prompt.innerHTML = data.prompt.html;
                    }
                    
                    if (data.cwd) {
                        cwdBadge.textContent = data.cwd;
                        document.querySelector('.header-path').textContent = data.cwd;
                    }
                    
                    await loadHistory();
                    
                } catch (error) {
                    addOutput(cmd, [`❌ Error: ${error.message}`]);
                    statusDot.classList.add('error');
                }
                
                setBusy(false);
            }

            async function loadHistory() {
                try {
                    const response = await fetch('?ajax=1&action=history');
                    history = await response.json();
                    historyCount.textContent = history.length + ' cmd';
                } catch (error) {
                    console.error('Error loading history:', error);
                }
            }

            async function showSuggestions(prefix) {
                if (prefix.length < 2) {
                    suggestions.style.display = 'none';
                    return;
                }
                
                try {
                    const response = await fetch(`?ajax=1&action=suggest&cmd=${encodeURIComponent(prefix)}`);
                    const list = await response.json();
                    
                    if (!list.length) {
                        suggestions.style.display = 'none';
                        return;
                    }
                    
                    suggestions.innerHTML = list.map((s, i) => 
                        `<div class="suggestion-item ${i === 0 ? 'active' : ''}" data-value="${s}">${s}</div>`
                    ).join('');
                    
                    suggestions.style.display = 'block';
                } catch (error) {
                    console.error('Error getting suggestions:', error);
                }
            }

            function hideSuggestions() {
                suggestions.style.display = 'none';
            }

            function selectSuggestion(value) {
                if (value) {
                    input.value = value;
                    hideSuggestions();
                }
            }

            function showHelp() {
                helpPanel.classList.add('active');
                overlay.classList.add('active');
            }

            function closeHelp() {
                helpPanel.classList.remove('active');
                overlay.classList.remove('active');
            }

            async function showHistory() {
                const list = document.getElementById('historyList');
                list.innerHTML = '';
                
                history.slice(0, 50).forEach((item, i) => {
                    const div = document.createElement('div');
                    div.className = 'history-item';
                    div.innerHTML = `
                        <span style="color: var(--accent-primary);">${i + 1}.</span>
                        <span>${escapeHtml(item.cmd)}</span>
                        <span class="history-time">[${new Date(item.time * 1000).toLocaleTimeString()}]</span>
                    `;
                    div.onclick = () => {
                        input.value = item.cmd;
                        closeHistory();
                        input.focus();
                    };
                    list.appendChild(div);
                });
                
                historyPanel.classList.add('active');
                overlay.classList.add('active');
            }

            function closeHistory() {
                historyPanel.classList.remove('active');
                overlay.classList.remove('active');
            }

            async function clearHistory() {
                await fetch('?ajax=1&action=clear_history');
                await loadHistory();
                closeHistory();
            }

            function clearTerminal() {
                output.innerHTML = '';
                addOutput('clear', ['✅ Terminal limpiado']);
            }

            execBtn.onclick = executeCommand;

            input.onkeydown = (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    executeCommand();
                    hideSuggestions();
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    if (historyIndex < history.length - 1) {
                        historyIndex++;
                        input.value = history[historyIndex].cmd;
                    }
                } else if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    if (historyIndex > 0) {
                        historyIndex--;
                        input.value = history[historyIndex].cmd;
                    } else if (historyIndex === 0) {
                        historyIndex = -1;
                        input.value = '';
                    }
                } else if (e.key === 'Tab') {
                    e.preventDefault();
                    if (suggestions.style.display === 'block') {
                        const active = suggestions.querySelector('.active');
                        if (active) {
                            selectSuggestion(active.dataset.value);
                        }
                    } else {
                        showSuggestions(input.value);
                    }
                } else if (e.key === 'Escape') {
                    hideSuggestions();
                } else if (e.key === 'l' && e.ctrlKey) {
                    e.preventDefault();
                    clearTerminal();
                } else if (e.key === '?' || e.key === 'F1') {
                    e.preventDefault();
                    showHelp();
                }
            };

            input.oninput = () => {
                historyIndex = -1;
                showSuggestions(input.value);
            };

            suggestions.onclick = (e) => {
                if (e.target.classList.contains('suggestion-item')) {
                    selectSuggestion(e.target.dataset.value);
                }
            };

            document.onclick = (e) => {
                if (!e.target.closest('.input-wrapper')) {
                    hideSuggestions();
                }
                
                if (e.target.closest('.output-line')) {
                    const line = e.target.closest('.output-line');
                    navigator.clipboard.writeText(line.textContent);
                    
                    const hint = line.parentElement.querySelector('.copy-hint');
                    if (hint) {
                        hint.textContent = '✓';
                        setTimeout(() => hint.textContent = '📋', 1500);
                    }
                }
            };

            document.getElementById('helpBtn').onclick = showHelp;
            document.getElementById('historyBtn').onclick = showHistory;
            document.getElementById('clearBtn').onclick = clearTerminal;
            overlay.onclick = () => {
                closeHelp();
                closeHistory();
            };

            loadHistory();
            input.focus();

            setInterval(() => {
                if (!isLoading && statusDot.classList.contains('busy')) {
                    statusDot.classList.remove('busy');
                    statusText.textContent = 'LISTO';
                }
            }, 3000);
        })();

        function setTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            fetch(`?ajax=1&action=theme&cmd=${theme}`);
            localStorage.setItem('terminal-theme', theme);
        }

        (function() {
            const savedTheme = localStorage.getItem('terminal-theme') || 'dark';
            setTheme(savedTheme);
        })();
    </script>
</body>
</html>
