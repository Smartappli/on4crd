(function () {
  const textareas = document.querySelectorAll('textarea:not([data-wysiwyg="off"])');
  if (!textareas.length) return;
  const currentRoute = new URLSearchParams(window.location.search).get('route') || 'home';
  const currentScriptUrl = document.currentScript instanceof HTMLScriptElement ? document.currentScript.src : '';
  const mammothAssetUrl = currentScriptUrl
    ? new URL('../../vendor/mammoth/1.8.0/mammoth.browser.min.js', currentScriptUrl).href
    : '/assets/vendor/mammoth/1.8.0/mammoth.browser.min.js';

  const syncHandlers = [];
  const toolbarButtons = [
    { label: 'B', command: 'bold', title: 'Gras' },
    { label: 'I', command: 'italic', title: 'Italique' },
    { label: 'U', command: 'underline', title: 'Souligne' },
    { label: 'S', command: 'strikeThrough', title: 'Barre' },
    { label: 'Liste puces', command: 'insertUnorderedList', title: 'Liste a puces' },
    { label: 'Liste 1-2', command: 'insertOrderedList', title: 'Liste numerotee' },
    { label: 'Citation', command: 'formatBlock', value: 'blockquote', title: 'Citation' },
    { label: 'Code', command: 'formatBlock', value: 'pre', title: 'Bloc de code' },
    { label: 'Lien', command: 'createLink', title: 'Inserer un lien' },
    { label: 'HR', command: 'insertHorizontalRule', title: 'Ligne horizontale' },
    { label: 'Annuler', command: 'undo', title: 'Annuler' },
    { label: 'Retablir', command: 'redo', title: 'Retablir' },
    { label: 'Nettoyer', command: 'removeFormat', title: 'Supprimer le formatage' },
  ];

  const applyCommand = (editor, command) => {
    editor.focus();
    if (command === 'createLink') {
      const url = window.prompt('URL du lien (https://...)', 'https://');
      if (!url) return;
      document.execCommand('createLink', false, url);
      return;
    }
    if (command === 'insertImage') {
      const url = window.prompt('URL de l image (https://...)', 'https://');
      if (!url) return;
      document.execCommand('insertImage', false, url);
      return;
    }
    if (command === 'insertTable') {
      document.execCommand('insertHTML', false, '<table><tbody><tr><th>Colonne 1</th><th>Colonne 2</th></tr><tr><td>Valeur 1</td><td>Valeur 2</td></tr></tbody></table>');
      return;
    }
    document.execCommand(command, false, null);
  };

  const applyCommandWithValue = (editor, command, value) => {
    editor.focus();
    document.execCommand(command, false, value);
  };

  const importedHtmlAllowedTags = new Set([
    'p', 'br', 'strong', 'b', 'em', 'i', 's', 'sub', 'sup', 'u', 'ul', 'ol', 'li', 'h2', 'h3', 'h4',
    'blockquote', 'pre', 'code', 'a', 'img', 'figure', 'figcaption', 'table',
    'thead', 'tbody', 'tr', 'th', 'td', 'hr',
  ]);
  const importedHtmlBlockedTags = new Set([
    'script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button',
    'textarea', 'select', 'option', 'meta', 'link',
  ]);
  const importedHtmlAllowedAttributes = {
    a: new Set(['href', 'title', 'target', 'rel']),
    img: new Set(['src', 'alt', 'title', 'width', 'height', 'loading']),
    th: new Set(['colspan', 'rowspan', 'scope']),
    td: new Set(['colspan', 'rowspan']),
  };

  const sanitizeImportedUrl = (value, allowImageData = false) => {
    const raw = String(value || '').trim().replace(/[\u0000-\u001f\u007f]+/g, '');
    if (raw === '') return '';
    if (allowImageData && /^data:image\/(?:png|jpe?g|gif|webp);base64,[a-z0-9+/=\s]+$/i.test(raw)) {
      return raw.replace(/\s+/g, '');
    }
    if (raw.startsWith('#')) return raw;
    try {
      const parsed = new URL(raw, window.location.href);
      const protocol = parsed.protocol.toLowerCase();
      if (protocol === 'http:' || protocol === 'https:' || protocol === 'mailto:' || protocol === 'tel:') {
        return raw;
      }
    } catch (error) {
      return '';
    }
    return '';
  };

  const sanitizeIntegerAttribute = (element, attributeName, maxValue) => {
    if (!element.hasAttribute(attributeName)) return;
    const raw = element.getAttribute(attributeName) || '';
    if (!/^\d+$/.test(raw)) {
      element.removeAttribute(attributeName);
      return;
    }
    const value = Number(raw);
    if (!Number.isInteger(value) || value <= 0 || value > maxValue) {
      element.removeAttribute(attributeName);
      return;
    }
    element.setAttribute(attributeName, String(value));
  };

  const unwrapImportedElement = (element) => {
    const parent = element.parentNode;
    if (!parent) return;
    while (element.firstChild) {
      parent.insertBefore(element.firstChild, element);
    }
    parent.removeChild(element);
  };

  const sanitizeImportedHtml = (html) => {
    const template = document.createElement('template');
    template.innerHTML = String(html || '');

    const sanitizeNode = (node) => {
      if (node.nodeType === Node.COMMENT_NODE) {
        node.parentNode && node.parentNode.removeChild(node);
        return;
      }
      if (node.nodeType !== Node.ELEMENT_NODE) return;

      const element = node;
      const tag = element.tagName.toLowerCase();
      if (importedHtmlBlockedTags.has(tag)) {
        element.parentNode && element.parentNode.removeChild(element);
        return;
      }

      Array.from(element.childNodes).forEach(sanitizeNode);

      if (!importedHtmlAllowedTags.has(tag)) {
        unwrapImportedElement(element);
        return;
      }

      const allowedAttributes = importedHtmlAllowedAttributes[tag] || new Set();
      Array.from(element.attributes).forEach((attribute) => {
        if (!allowedAttributes.has(attribute.name.toLowerCase())) {
          element.removeAttribute(attribute.name);
        }
      });

      if (tag === 'a') {
        const href = element.hasAttribute('href') ? sanitizeImportedUrl(element.getAttribute('href')) : '';
        if (href === '') {
          element.removeAttribute('href');
        } else {
          element.setAttribute('href', href);
        }
        const target = (element.getAttribute('target') || '').toLowerCase().trim();
        if (target !== '' && target !== '_blank' && target !== '_self') {
          element.removeAttribute('target');
        }
        if (target === '_blank') {
          element.setAttribute('rel', 'noopener noreferrer');
        }
      }

      if (tag === 'img') {
        const src = element.hasAttribute('src') ? sanitizeImportedUrl(element.getAttribute('src'), true) : '';
        if (src === '') {
          element.parentNode && element.parentNode.removeChild(element);
          return;
        }
        element.setAttribute('src', src);
        element.setAttribute('loading', 'lazy');
        sanitizeIntegerAttribute(element, 'width', 2000);
        sanitizeIntegerAttribute(element, 'height', 2000);
      }

      if (tag === 'td' || tag === 'th') {
        sanitizeIntegerAttribute(element, 'colspan', 20);
        sanitizeIntegerAttribute(element, 'rowspan', 20);
        if (tag === 'th' && element.hasAttribute('scope')) {
          const scope = (element.getAttribute('scope') || '').toLowerCase().trim();
          if (!['col', 'row', 'colgroup', 'rowgroup'].includes(scope)) {
            element.removeAttribute('scope');
          } else {
            element.setAttribute('scope', scope);
          }
        }
      }
    };

    Array.from(template.content.childNodes).forEach(sanitizeNode);
    return template.innerHTML.trim();
  };

  let mammothLoader = null;
  const loadMammoth = async () => {
    if (window.mammoth) return window.mammoth;
    if (!mammothLoader) {
      mammothLoader = new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = mammothAssetUrl;
        script.async = true;
        script.onload = () => resolve(window.mammoth);
        script.onerror = () => reject(new Error('Impossible de charger le convertisseur Word.'));
        document.head.appendChild(script);
      });
    }
    return mammothLoader;
  };

  textareas.forEach((textarea, index) => {
    if (textarea.dataset.wysiwygApplied === '1') return;
    textarea.dataset.wysiwygApplied = '1';
    const isFullEditor = textarea.dataset.wysiwyg === 'full';

    const wrapper = document.createElement('div');
    wrapper.className = 'wysiwyg';

    const toolbar = document.createElement('div');
    toolbar.className = 'wysiwyg-toolbar';

    const formatSelect = document.createElement('select');
    formatSelect.className = 'wysiwyg-control';
    formatSelect.title = 'Format';
    [
      { value: 'p', label: 'Paragraphe' },
      { value: 'h2', label: 'Titre 2' },
      { value: 'h3', label: 'Titre 3' },
      { value: 'h4', label: 'Titre 4' },
      { value: 'blockquote', label: 'Citation' },
      { value: 'pre', label: 'Code' },
    ].forEach((optionConfig) => {
      const option = document.createElement('option');
      option.value = optionConfig.value;
      option.textContent = optionConfig.label;
      formatSelect.appendChild(option);
    });
    formatSelect.addEventListener('change', () => {
      applyCommandWithValue(editor, 'formatBlock', formatSelect.value || 'p');
      editor.dispatchEvent(new Event('input'));
    });
    toolbar.appendChild(formatSelect);

    const fontSizeSelect = document.createElement('select');
    fontSizeSelect.className = 'wysiwyg-control';
    fontSizeSelect.title = 'Taille de police';
    [
      { value: '3', label: 'Texte normal' },
      { value: '2', label: 'Petit' },
      { value: '4', label: 'Grand' },
      { value: '5', label: 'Tres grand' },
    ].forEach((optionConfig) => {
      const option = document.createElement('option');
      option.value = optionConfig.value;
      option.textContent = optionConfig.label;
      fontSizeSelect.appendChild(option);
    });
    fontSizeSelect.addEventListener('change', () => {
      applyCommandWithValue(editor, 'fontSize', fontSizeSelect.value || '3');
      editor.dispatchEvent(new Event('input'));
    });
    toolbar.appendChild(fontSizeSelect);

    const fontColorInput = document.createElement('input');
    fontColorInput.type = 'color';
    fontColorInput.className = 'wysiwyg-control wysiwyg-color';
    fontColorInput.title = 'Couleur du texte';
    fontColorInput.value = '#111111';
    fontColorInput.addEventListener('input', () => {
      applyCommandWithValue(editor, 'foreColor', fontColorInput.value);
      editor.dispatchEvent(new Event('input'));
    });
    toolbar.appendChild(fontColorInput);

    const alignSelect = document.createElement('select');
    alignSelect.className = 'wysiwyg-control';
    alignSelect.title = 'Alignement';
    [
      { value: '', label: 'Alignement' },
      { value: 'justifyLeft', label: 'Gauche' },
      { value: 'justifyCenter', label: 'Centre' },
      { value: 'justifyRight', label: 'Droite' },
      { value: 'justifyFull', label: 'Justifie' },
    ].forEach((optionConfig) => {
      const option = document.createElement('option');
      option.value = optionConfig.value;
      option.textContent = optionConfig.label;
      alignSelect.appendChild(option);
    });
    alignSelect.addEventListener('change', () => {
      if (alignSelect.value) {
        applyCommand(editor, alignSelect.value);
        editor.dispatchEvent(new Event('input'));
      }
      alignSelect.value = '';
    });
    toolbar.appendChild(alignSelect);

    if (isFullEditor) {
      [
        { label: 'Image', command: 'insertImage', title: 'Inserer une image par URL' },
        { label: 'Tableau', command: 'insertTable', title: 'Inserer un tableau' },
        { label: 'Delier', command: 'unlink', title: 'Supprimer le lien' },
      ].forEach((buttonConfig) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'ghost small';
        button.textContent = buttonConfig.label;
        button.title = buttonConfig.title;
        button.addEventListener('click', () => {
          applyCommand(editor, buttonConfig.command);
          editor.dispatchEvent(new Event('input'));
        });
        toolbar.appendChild(button);
      });
    }

    toolbarButtons.forEach((buttonConfig) => {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'ghost small';
      button.textContent = buttonConfig.label;
      button.title = buttonConfig.title;
      button.addEventListener('click', () => {
        if (buttonConfig.value) {
          applyCommandWithValue(editor, buttonConfig.command, buttonConfig.value);
          editor.dispatchEvent(new Event('input'));
          return;
        }
        applyCommand(editor, buttonConfig.command);
        editor.dispatchEvent(new Event('input'));
      });
      toolbar.appendChild(button);
    });

    const editor = document.createElement('div');
    editor.className = 'wysiwyg-editor';
    editor.contentEditable = 'true';
    editor.setAttribute('role', 'textbox');
    editor.setAttribute('aria-multiline', 'true');
    editor.setAttribute('data-wysiwyg-editor-index', String(index));
    editor.innerHTML = textarea.value && textarea.value.trim() !== '' ? textarea.value : '<p><br></p>';

    const sync = () => {
      textarea.value = editor.innerHTML;
    };

    if ((currentRoute === 'admin_news' || isFullEditor) && textarea.name === 'content') {
      const importButton = document.createElement('button');
      importButton.type = 'button';
      importButton.className = 'ghost small';
      importButton.textContent = 'Importer Word';
      importButton.title = 'Importer un document Word (.docx)';
      const importButtonLabel = importButton.textContent;

      const fileInput = document.createElement('input');
      fileInput.type = 'file';
      fileInput.accept = '.doc,.docx,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document';
      fileInput.hidden = true;

      importButton.addEventListener('click', () => fileInput.click());
      fileInput.addEventListener('change', async () => {
        const file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
        if (!file) return;

        const extension = file.name.toLowerCase();
        if (extension.endsWith('.doc')) {
          window.alert('Le format .doc n est pas supporte directement. Merci d enregistrer en .docx puis de reimporter.');
          fileInput.value = '';
          return;
        }

        importButton.disabled = true;
        importButton.textContent = 'Import...';
        try {
          const mammoth = await loadMammoth();
          if (!mammoth) {
            throw new Error('Convertisseur indisponible.');
          }
          const arrayBuffer = await file.arrayBuffer();
          const result = await mammoth.convertToHtml({ arrayBuffer });
          const importedHtml = sanitizeImportedHtml(result.value);
          if (importedHtml === '') {
            throw new Error('Aucun contenu Word exploitable.');
          }
          editor.innerHTML = importedHtml;
          sync();
        } catch (error) {
          window.alert('Import Word impossible pour le moment.');
        } finally {
          fileInput.value = '';
          importButton.disabled = false;
          importButton.textContent = importButtonLabel;
        }
      });

      toolbar.appendChild(importButton);
      toolbar.appendChild(fileInput);
    }

    if (isFullEditor) {
      const sourceButton = document.createElement('button');
      sourceButton.type = 'button';
      sourceButton.className = 'ghost small';
      sourceButton.textContent = 'HTML';
      sourceButton.title = 'Afficher ou masquer le HTML source';
      let sourceVisible = false;
      sourceButton.addEventListener('click', () => {
        if (sourceVisible) {
          editor.innerHTML = textarea.value && textarea.value.trim() !== '' ? textarea.value : '<p><br></p>';
          editor.hidden = false;
          textarea.style.display = '';
          textarea.classList.add('wysiwyg-source');
          sourceVisible = false;
          return;
        }
        sync();
        editor.hidden = true;
        textarea.style.display = 'block';
        textarea.classList.remove('wysiwyg-source');
        textarea.focus();
        sourceVisible = true;
      });
      toolbar.appendChild(sourceButton);
    }

    editor.addEventListener('input', sync);
    editor.addEventListener('blur', sync);
    syncHandlers.push(sync);

    textarea.classList.add('wysiwyg-source');
    textarea.insertAdjacentElement('beforebegin', wrapper);
    wrapper.appendChild(toolbar);
    wrapper.appendChild(editor);
  });

  document.querySelectorAll('form').forEach((form) => {
    form.addEventListener('submit', () => {
      syncHandlers.forEach((sync) => sync());
    });
  });
})();

