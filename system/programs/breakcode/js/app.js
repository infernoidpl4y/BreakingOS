// Main Application for BreakCode
class BreakCodeApp {
  constructor() {
    this.editor = null;
    this.fileExplorer = null;
    this.openTabs = new Map();
    this.activeTab = null;
    this.unsavedFiles = new Set();
    this.currentZoom = 14;
    this.showMinimap = true;
    this.undoStack = [];
    this.redoStack = [];
  }
  
  async init() {
    const editorElement = document.getElementById('code-editor');
    const lineNumbersElement = document.getElementById('line-numbers');
    this.editor = new CodeEditor(editorElement, lineNumbersElement);
    
    const fileTreeElement = document.getElementById('root-contents');
    this.fileExplorer = new FileExplorer(fileTreeElement);
    this.fileExplorer.onFileOpen = (file) => this.openFile(file.path);
    await this.fileExplorer.loadDirectory('.');
    
    this.setupSidebar();
    this.setupKeyboardShortcuts();
    this.updateMinimap();
    
    console.log('BreakCode initialized');
  }
  
  setupSidebar() {
    const sidebarBtns = document.querySelectorAll('.sidebar-btn');
    const panels = document.querySelectorAll('.sidebar-panel');
    
    sidebarBtns.forEach(btn => {
      btn.addEventListener('click', () => {
        const view = btn.dataset.view;
        sidebarBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        panels.forEach(p => p.classList.remove('active'));
        const panel = document.getElementById(`${view}-panel`);
        if (panel) panel.classList.add('active');
      });
    });
  }
  
  setupKeyboardShortcuts() {
    document.addEventListener('keydown', (e) => {
      if (e.ctrlKey || e.metaKey) {
        switch(e.key.toLowerCase()) {
          case 'n': e.preventDefault(); newFile(); break;
          case 's': 
            e.preventDefault(); 
            if (e.shiftKey) saveFileAs();
            else saveFile(); 
            break;
          case 'w': e.preventDefault(); closeFile(); break;
          case 'f': e.preventDefault(); find(); break;
          case 'h': e.preventDefault(); replace(); break;
          case 'b': e.preventDefault(); toggleSidebar(); break;
          case 'g': e.preventDefault(); goToLine(); break;
          case 'z': 
            e.preventDefault(); 
            if (e.shiftKey) redo();
            else undo(); 
            break;
          case 'y': e.preventDefault(); redo(); break;
          case '=':
          case '+': e.preventDefault(); zoomIn(); break;
          case '-': e.preventDefault(); zoomOut(); break;
          case '0': e.preventDefault(); resetZoom(); break;
        }
      }
    });
  }
  
  async openFile(path) {
    if (this.openTabs.has(path)) {
      this.switchToTab(path);
      return;
    }
    
    try {
      const data = await FileAPI.readFile(path);
      if (data.error) {
        alert('Error: ' + data.error);
        return;
      }
      this.createTab(path, data.content);
    } catch (error) {
      alert('Error opening file: ' + error.message);
    }
  }
  
  createTab(path, content) {
    const tabsContainer = document.getElementById('tabs');
    const filename = path.split('/').pop();
    
    const tab = document.createElement('div');
    tab.className = 'tab active';
    tab.dataset.file = path;
    tab.innerHTML = `
      <span class="tab-icon">${this.getFileIcon(filename)}</span>
      <span class="tab-title">${filename}</span>
      <button class="tab-close" title="Close">×</button>
    `;
    
    tab.addEventListener('click', (e) => {
      if (!e.target.classList.contains('tab-close')) {
        this.switchToTab(path);
      }
    });
    
    tab.querySelector('.tab-close').addEventListener('click', (e) => {
      e.stopPropagation();
      this.closeTab(path);
    });
    
    tabsContainer.appendChild(tab);
    this.openTabs.set(path, content);
    this.switchToTab(path);
    
    const language = SyntaxHighlighter.detectLanguage(path);
    this.editor.openFile(path, content, language);
  }
  
  switchToTab(path) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    const tab = document.querySelector(`.tab[data-file="${path}"]`);
    if (tab) tab.classList.add('active');
    
    const content = this.openTabs.get(path);
    if (content !== undefined) {
      const language = SyntaxHighlighter.detectLanguage(path);
      this.editor.openFile(path, content, language);
    }
    
    this.activeTab = path;
    this.updateMinimap();
  }
  
  closeTab(path) {
    if (this.unsavedFiles.has(path)) {
      if (!confirm('Unsaved changes. Close anyway?')) return;
    }
    
    this.openTabs.delete(path);
    this.unsavedFiles.delete(path);
    
    const tab = document.querySelector(`.tab[data-file="${path}"]`);
    if (tab) tab.remove();
    
    if (this.openTabs.size > 0) {
      const nextPath = this.openTabs.keys().next().value;
      this.switchToTab(nextPath);
    } else {
      this.editor.clear();
      document.getElementById('file-type').textContent = 'Plain Text';
    }
  }
  
  async saveFile() {
    if (!this.activeTab) return;
    const content = this.editor.getContent();
    
    try {
      const result = await FileAPI.writeFile(this.activeTab, content);
      if (result.error) {
        alert('Error saving: ' + result.error);
        return;
      }
      this.openTabs.set(this.activeTab, content);
      this.unsavedFiles.delete(this.activeTab);
      this.updateTabTitle(this.activeTab);
      console.log('Saved:', this.activeTab);
    } catch (error) {
      alert('Error saving file: ' + error.message);
    }
  }
  
  updateTabTitle(path) {
    const tab = document.querySelector(`.tab[data-file="${path}"]`);
    if (tab) {
      const titleEl = tab.querySelector('.tab-title');
      const name = path.split('/').pop();
      titleEl.textContent = this.unsavedFiles.has(path) ? `${name} •` : name;
    }
  }
  
  markUnsaved(path) {
    this.unsavedFiles.add(path);
    this.updateTabTitle(path);
  }
  
  getFileIcon(filename) {
    const ext = filename.split('.').pop().toLowerCase();
    const icons = {
      'php': '🐘', 'js': '📜', 'ts': '📘', 'html': '🌐', 
      'css': '🎨', 'json': '📋', 'md': '📝', 'txt': '📄',
      'py': '🐍', 'rb': '💎', 'java': '☕', 'c': '🔧',
      'cpp': '🔧', 'h': '📋', 'sh': '⚡', 'sql': '🗃️'
    };
    return icons[ext] || '📄';
  }
  
  updateMinimap() {
    const minimap = document.getElementById('minimap');
    const content = this.editor ? this.editor.getContent() : '';
    
    let minimapContent = minimap.querySelector('.minimap-content');
    if (!minimapContent) {
      minimapContent = document.createElement('div');
      minimapContent.className = 'minimap-content';
      minimap.appendChild(minimapContent);
    }
    
    minimapContent.textContent = content || ' ';
    
    if (this.showMinimap) {
      minimap.classList.add('visible');
    }
  }
}

// Global functions for menu and dialogs
let app;

document.addEventListener('DOMContentLoaded', () => {
  app = new BreakCodeApp();
  app.init();
});

// File operations
function newFile() {
  const tabsContainer = document.getElementById('tabs');
  let counter = 1;
  let newPath = `untitled-${counter}.txt`;
  
  while (app.openTabs.has(newPath)) {
    counter++;
    newPath = `untitled-${counter}.txt`;
  }
  
  app.createTab(newPath, '');
  app.switchToTab(newPath);
}

function saveFile() {
  if (app) app.saveFile();
}

async function saveFileAs() {
  if (!app.activeTab) return;
  const newName = prompt('Enter new filename:', app.activeTab.split('/').pop());
  if (!newName) return;
  
  const dir = app.activeTab.includes('/') ? app.activeTab.substring(0, app.activeTab.lastIndexOf('/')) : '.';
  const newPath = dir + '/' + newName;
  
  const content = app.editor.getContent();
  const result = await FileAPI.writeFile(newPath, content);
  
  if (result.error) {
    alert('Error: ' + result.error);
    return;
  }
  
  app.openTabs.delete(app.activeTab);
  app.createTab(newPath, content);
}

function closeFile() {
  if (app && app.activeTab) {
    app.closeTab(app.activeTab);
  }
}

// Edit operations
function undo() {
  document.execCommand('undo');
}

function redo() {
  document.execCommand('redo');
}

function cutLine() {
  const editor = document.getElementById('code-editor');
  const start = editor.selectionStart;
  const end = editor.selectionEnd;
  const lines = editor.value.split('\n');
  
  let lineStart = editor.value.lastIndexOf('\n', start - 1) + 1;
  let lineEnd = editor.value.indexOf('\n', end);
  if (lineEnd === -1) lineEnd = editor.value.length;
  
  const line = editor.value.substring(lineStart, lineEnd);
  editor.setSelectionRange(lineStart, lineEnd);
  document.execCommand('cut');
}

function copyLine() {
  const editor = document.getElementById('code-editor');
  const start = editor.selectionStart;
  const end = editor.selectionEnd;
  
  let lineStart = editor.value.lastIndexOf('\n', start - 1) + 1;
  let lineEnd = editor.value.indexOf('\n', end);
  if (lineEnd === -1) lineEnd = editor.value.length;
  
  editor.setSelectionRange(lineStart, lineEnd);
  document.execCommand('copy');
}

function paste() {
  document.execCommand('paste');
}

function deleteLine() {
  const editor = document.getElementById('code-editor');
  const start = editor.selectionStart;
  const end = editor.selectionEnd;
  
  let lineStart = editor.value.lastIndexOf('\n', start - 1) + 1;
  let lineEnd = editor.value.indexOf('\n', end);
  if (lineEnd === -1) lineEnd = editor.value.length;
  
  editor.setSelectionRange(lineStart, lineEnd + 1);
  document.execCommand('delete');
}

// Find and Replace
let findIndex = 0;
let findResults = [];

function find() {
  document.getElementById('find-dialog').style.display = 'flex';
  document.getElementById('find-input').focus();
}

function replace() {
  document.getElementById('find-dialog').style.display = 'flex';
  document.getElementById('find-input').focus();
}

function closeFindDialog() {
  document.getElementById('find-dialog').style.display = 'none';
}

function findNext() {
  const searchText = document.getElementById('find-input').value;
  if (!searchText) return;
  
  const editor = document.getElementById('code-editor');
  const content = editor.value;
  const regex = new RegExp(searchText, 'gi');
  let match;
  findResults = [];
  
  while ((match = regex.exec(content)) !== null) {
    findResults.push(match.index);
  }
  
  if (findResults.length === 0) {
    alert('No results found');
    return;
  }
  
  findIndex = (findIndex + 1) % findResults.length;
  const pos = findResults[findIndex];
  editor.setSelectionRange(pos, pos + searchText.length);
  editor.focus();
}

function replaceOne() {
  const searchText = document.getElementById('find-input').value;
  const replaceText = document.getElementById('replace-input').value;
  if (!searchText) return;
  
  const editor = document.getElementById('code-editor');
  const start = editor.selectionStart;
  const end = editor.selectionEnd;
  const selected = editor.value.substring(start, end);
  
  if (selected.toLowerCase() === searchText.toLowerCase()) {
    editor.setSelectionRange(start, end);
    document.execCommand('insertText', false, replaceText);
  }
  
  findNext();
}

function replaceAll() {
  const searchText = document.getElementById('find-input').value;
  const replaceText = document.getElementById('replace-input').value;
  if (!searchText) return;
  
  const editor = document.getElementById('code-editor');
  const content = editor.value;
  const regex = new RegExp(searchText, 'gi');
  const newContent = content.replace(regex, replaceText);
  editor.value = newContent;
  app.markUnsaved(app.activeTab);
}

// View operations
function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  sidebar.style.display = sidebar.style.display === 'none' ? 'flex' : 'none';
}

function toggleMinimap() {
  const minimap = document.getElementById('minimap');
  app.showMinimap = !app.showMinimap;
  if (app.showMinimap) {
    minimap.classList.add('visible');
  } else {
    minimap.classList.remove('visible');
  }
}

function zoomIn() {
  app.currentZoom = Math.min(app.currentZoom + 2, 24);
  document.getElementById('code-editor').style.fontSize = app.currentZoom + 'px';
  document.getElementById('line-numbers').style.fontSize = app.currentZoom + 'px';
}

function zoomOut() {
  app.currentZoom = Math.max(app.currentZoom - 2, 10);
  document.getElementById('code-editor').style.fontSize = app.currentZoom + 'px';
  document.getElementById('line-numbers').style.fontSize = app.currentZoom + 'px';
}

function resetZoom() {
  app.currentZoom = 14;
  document.getElementById('code-editor').style.fontSize = '14px';
  document.getElementById('line-numbers').style.fontSize = '14px';
}

function goToLine() {
  document.getElementById('goto-dialog').style.display = 'flex';
  document.getElementById('goto-input').focus();
}

function closeGotoDialog() {
  document.getElementById('goto-dialog').style.display = 'none';
}

function goToLineNumber() {
  const lineNum = parseInt(document.getElementById('goto-input').value);
  if (!lineNum || lineNum < 1) return;
  
  const editor = document.getElementById('code-editor');
  const lines = editor.value.split('\n');
  let pos = 0;
  
  for (let i = 0; i < lineNum - 1 && i < lines.length; i++) {
    pos += lines[i].length + 1;
  }
  
  editor.setSelectionRange(pos, pos);
  editor.focus();
  closeGotoDialog();
}

// Dialogs
function showAbout() {
  document.getElementById('about-dialog').style.display = 'flex';
}

function closeAboutDialog() {
  document.getElementById('about-dialog').style.display = 'none';
}

function showShortcuts() {
  const shortcuts = `
Keyboard Shortcuts:

File:
  Ctrl+N - New File
  Ctrl+O - Open File
  Ctrl+S - Save
  Ctrl+Shift+S - Save As
  Ctrl+W - Close File

Edit:
  Ctrl+Z - Undo
  Ctrl+Y - Redo
  Ctrl+F - Find
  Ctrl+H - Replace
  Ctrl+X - Cut Line
  Ctrl+C - Copy Line
  Ctrl+V - Paste
  Ctrl+Shift+K - Delete Line

View:
  Ctrl+B - Toggle Sidebar
  Ctrl+G - Go to Line
  Ctrl++ - Zoom In
  Ctrl+- - Zoom Out
  Ctrl+0 - Reset Zoom
  `;
  alert(shortcuts);
}

// File dialogs
function openFileDialog() {
  document.getElementById('file-dialog').style.display = 'flex';
  document.getElementById('new-file-input').focus();
}

function closeFileDialog() {
  document.getElementById('file-dialog').style.display = 'none';
}

async function createNewFileConfirm() {
  const filename = document.getElementById('new-file-input').value.trim();
  if (!filename) {
    alert('Please enter a filename');
    return;
  }
  
  const result = await FileAPI.createFile(filename);
  if (result.error) {
    alert('Error: ' + result.error);
    return;
  }
  
  closeFileDialog();
  document.getElementById('new-file-input').value = '';
  app.fileExplorer.loadDirectory(app.fileExplorer.currentPath);
  app.openFile(filename);
}

function createFolderDialog() {
  document.getElementById('folder-dialog').style.display = 'flex';
  document.getElementById('new-folder-input').focus();
}

function closeFolderDialog() {
  document.getElementById('folder-dialog').style.display = 'none';
}

async function createNewFolderConfirm() {
  const foldername = document.getElementById('new-folder-input').value.trim();
  if (!foldername) {
    alert('Please enter a folder name');
    return;
  }
  
  const result = await FileAPI.createDirectory(foldername);
  if (result.error) {
    alert('Error: ' + result.error);
    return;
  }
  
  closeFolderDialog();
  document.getElementById('new-folder-input').value = '';
  app.fileExplorer.loadDirectory(app.fileExplorer.currentPath);
}

// Alias for compatibility
function createFolder() {
  createFolderDialog();
}

function createNewFile() {
  openFileDialog();
}