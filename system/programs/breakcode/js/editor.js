// Code Editor implementation
class CodeEditor {
  constructor(editorElement, lineNumbersElement) {
    this.editor = editorElement;
    this.lineNumbers = lineNumbersElement;
    this.currentFile = null;
    this.files = new Map(); // Store open files
    this.unsavedChanges = new Map();
    this.language = 'plaintext';
    
    this.init();
  }
  
  init() {
    this.editor.addEventListener('input', () => this.onInput());
    this.editor.addEventListener('scroll', () => this.syncScroll());
    this.editor.addEventListener('keydown', (e) => this.handleKeyDown(e));
    this.editor.addEventListener('click', () => this.updateCursorPosition());
    this.editor.addEventListener('keyup', () => this.updateCursorPosition());
    
    this.updateLineNumbers();
  }
  
  onInput() {
    this.updateLineNumbers();
    this.updateCursorPosition();
    this.updateMinimap();
    
    // Mark as unsaved
    if (this.currentFile) {
      this.unsavedChanges.set(this.currentFile, true);
      // Notify app to update tab
      if (window.app) {
        window.app.markUnsaved(this.currentFile);
      }
    }
  }
  
  updateMinimap() {
    const minimap = document.getElementById('minimap');
    if (!minimap) return;
    
    let content = minimap.querySelector('.minimap-content');
    if (!content) {
      content = document.createElement('div');
      content.className = 'minimap-content';
      minimap.appendChild(content);
    }
    
    content.textContent = this.editor.value || ' ';
    
    // Update cursor position in minimap
    const cursor = minimap.querySelector('.minimap-cursor');
    if (cursor) {
      const lines = this.editor.value.substring(0, this.editor.selectionStart).split('\n');
      const lineNum = lines.length - 1;
      cursor.style.top = (lineNum * 1.5) + 'em';
    }
  }
      this.updateTabTitle(this.currentFile);
    }
  }
  
  handleKeyDown(e) {
    // Tab key handling
    if (e.key === 'Tab') {
      e.preventDefault();
      const start = this.editor.selectionStart;
      const end = this.editor.selectionEnd;
      const value = this.editor.value;
      
      // Insert spaces or tab
      this.editor.value = value.substring(0, start) + '  ' + value.substring(end);
      this.editor.selectionStart = this.editor.selectionEnd = start + 2;
      
      this.onInput();
    }
    
    // Ctrl+S to save
    if (e.ctrlKey && e.key === 's') {
      e.preventDefault();
      if (this.currentFile && window.codeEditorApp) {
        window.codeEditorApp.saveFile(this.currentFile);
      }
    }
  }
  
  syncScroll() {
    this.lineNumbers.scrollTop = this.editor.scrollTop;
  }
  
  updateLineNumbers() {
    const lines = this.editor.value.split('\n');
    const lineCount = lines.length;
    
    let html = '';
    for (let i = 1; i <= lineCount; i++) {
      html += i + '\n';
    }
    
    this.lineNumbers.textContent = html;
  }
  
  updateCursorPosition() {
    const value = this.editor.value;
    const pos = this.editor.selectionStart;
    
    const lines = value.substring(0, pos).split('\n');
    const line = lines.length;
    const col = lines[lines.length - 1].length + 1;
    
    // Update status bar
    const cursorPos = document.getElementById('cursor-position');
    if (cursorPos) {
      cursorPos.textContent = `Ln ${line}, Col ${col}`;
    }
  }
  
  openFile(filename, content, language = null) {
    // Detect language if not provided
    if (!language) {
      language = SyntaxHighlighter.detectLanguage(filename);
    }
    
    this.language = language;
    this.currentFile = filename;
    this.editor.value = content;
    
    // Store in files map
    this.files.set(filename, content);
    
    // Update line numbers
    this.updateLineNumbers();
    this.updateCursorPosition();
    
    // Update status bar
    const fileType = document.getElementById('file-type');
    if (fileType) {
      fileType.textContent = this.getLanguageDisplayName(language);
    }
    
    // Clear unsaved marker
    this.unsavedChanges.delete(filename);
    this.updateTabTitle(filename);
    
    // Focus editor
    this.editor.focus();
  }
  
  getLanguageDisplayName(lang) {
    const names = {
      'php': 'PHP',
      'javascript': 'JavaScript',
      'typescript': 'TypeScript',
      'html': 'HTML',
      'css': 'CSS',
      'json': 'JSON',
      'xml': 'XML',
      'markdown': 'Markdown',
      'python': 'Python',
      'ruby': 'Ruby',
      'sql': 'SQL',
      'bash': 'Bash',
      'yaml': 'YAML',
      'plaintext': 'Plain Text'
    };
    return names[lang] || lang;
  }
  
  updateTabTitle(filename) {
    const tab = document.querySelector(`.tab[data-file="${filename}"]`);
    if (tab) {
      const titleEl = tab.querySelector('.tab-title');
      if (titleEl) {
        const hasChanges = this.unsavedChanges.get(filename);
        const name = filename.split('/').pop();
        titleEl.textContent = hasChanges ? `${name} •` : name;
      }
    }
  }
  
  getContent() {
    return this.editor.value;
  }
  
  setContent(content) {
    this.editor.value = content;
    this.updateLineNumbers();
  }
  
  clear() {
    this.currentFile = null;
    this.editor.value = '';
    this.updateLineNumbers();
  }
  
  focus() {
    this.editor.focus();
  }
}

// Export
window.CodeEditor = CodeEditor;