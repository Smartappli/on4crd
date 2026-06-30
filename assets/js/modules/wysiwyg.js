(function () {
  const textareas = document.querySelectorAll('textarea:not([data-wysiwyg="off"])');
  if (!textareas.length) return;
  const currentRoute = new URLSearchParams(window.location.search).get('route') || 'home';
  const currentScriptUrl = document.currentScript instanceof HTMLScriptElement ? document.currentScript.src : '';
  const mammothAssetUrl = currentScriptUrl
    ? new URL('../../vendor/mammoth/1.8.0/mammoth.browser.min.js', currentScriptUrl).href
    : '/assets/vendor/mammoth/1.8.0/mammoth.browser.min.js';
  const maxImportedImageDataUriLength = 700000;
  const maxImportedImageDataUriTotalLength = 1400000;

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
  const windows1252EncodeMap = new Map([
    ['\u20ac', 0x80], ['\u201a', 0x82], ['\u0192', 0x83], ['\u201e', 0x84],
    ['\u2026', 0x85], ['\u2020', 0x86], ['\u2021', 0x87], ['\u02c6', 0x88],
    ['\u2030', 0x89], ['\u0160', 0x8a], ['\u2039', 0x8b], ['\u0152', 0x8c],
    ['\u017d', 0x8e], ['\u2018', 0x91], ['\u2019', 0x92], ['\u201c', 0x93],
    ['\u201d', 0x94], ['\u2022', 0x95], ['\u2013', 0x96], ['\u2014', 0x97],
    ['\u02dc', 0x98], ['\u2122', 0x99], ['\u0161', 0x9a], ['\u203a', 0x9b],
    ['\u0153', 0x9c], ['\u017e', 0x9e], ['\u0178', 0x9f],
  ]);
  const windows1252ControlMap = new Map([
    ['\u0080', '\u20ac'], ['\u0082', '\u201a'], ['\u0083', '\u0192'], ['\u0084', '\u201e'],
    ['\u0085', '\u2026'], ['\u0086', '\u2020'], ['\u0087', '\u2021'], ['\u0088', '\u02c6'],
    ['\u0089', '\u2030'], ['\u008a', '\u0160'], ['\u008b', '\u2039'], ['\u008c', '\u0152'],
    ['\u008e', '\u017d'], ['\u0091', '\u2018'], ['\u0092', '\u2019'], ['\u0093', '\u201c'],
    ['\u0094', '\u201d'], ['\u0095', '\u2022'], ['\u0096', '\u2013'], ['\u0097', '\u2014'],
    ['\u0098', '\u02dc'], ['\u0099', '\u2122'], ['\u009a', '\u0161'], ['\u009b', '\u203a'],
    ['\u009c', '\u0153'], ['\u009e', '\u017e'], ['\u009f', '\u0178'],
  ]);
  const mojibakeEntityPattern = /&(?:amp;)?(?:Atilde|Acirc|AElig|aring|sbquo|fnof|bdquo|hellip|dagger|Dagger|circ|permil|Scaron|lsaquo|OElig|Zcaron|lsquo|rsquo|ldquo|rdquo|bull|ndash|mdash|tilde|trade|scaron|rsaquo|oelig|zcaron|Yuml|euro);|&(?:amp;)?#(?:x[89][0-9a-f]|1[2-5][0-9]);/i;
  const createTextDecoder = (encoding, options) => {
    if (typeof TextDecoder !== 'function') return null;
    try {
      return new TextDecoder(encoding, options);
    } catch (error) {
      return null;
    }
  };
  const utf8Decoder = createTextDecoder('utf-8');
  const fatalUtf8Decoder = createTextDecoder('utf-8', { fatal: true });
  const windows1252Decoder = createTextDecoder('windows-1252');

  const decodeMojibakeHtmlEntities = (value) => {
    let current = String(value || '');
    for (let index = 0; index < 2 && mojibakeEntityPattern.test(current); index += 1) {
      const decoder = document.createElement('textarea');
      decoder.innerHTML = current.replace(/&amp;/g, '&');
      const decoded = decoder.value;
      if (decoded === current) break;
      current = decoded;
    }
    return current;
  };

  const mojibakeScore = (value) => {
    const text = String(value || '');
    if (text === '') return 0;
    const markerMatches = text.match(/[\u00c3\u00c2\u00e2\u00c5\u00c4]/g) || [];
    const controlMatches = text.match(/[\u0080-\u009f]/g) || [];
    const entityMatches = text.match(new RegExp(mojibakeEntityPattern.source, 'gi')) || [];
    return markerMatches.length + controlMatches.length + entityMatches.length;
  };

  const normalizeWindows1252Controls = (value) => String(value || '').replace(
    /[\u0080-\u009f]/g,
    (character) => windows1252ControlMap.get(character) || character,
  );

  const normalizeLostMojibakeSpaces = (value) => String(value || '')
    .replace(/\u00c3[ \t\u00a0]+/g, '\u00c3\u00a0 ')
    .replace(/\u00c2[ \t]+/g, ' ');

  const windows1252BytesFromString = (value) => {
    const bytes = [];
    for (const character of String(value || '')) {
      const codePoint = character.codePointAt(0);
      if (codePoint <= 0x7f) {
        bytes.push(codePoint);
      } else if (codePoint >= 0xa0 && codePoint <= 0xff) {
        bytes.push(codePoint);
      } else if (windows1252EncodeMap.has(character)) {
        bytes.push(windows1252EncodeMap.get(character));
      } else {
        return null;
      }
    }
    return new Uint8Array(bytes);
  };

  const validUtf8SequenceLength = (bytes, offset, sequenceLength) => {
    if (!fatalUtf8Decoder || offset + sequenceLength > bytes.length) return 0;
    try {
      fatalUtf8Decoder.decode(bytes.slice(offset, offset + sequenceLength));
      return sequenceLength;
    } catch (error) {
      return 0;
    }
  };

  const utf8FromMixedWindows1252Bytes = (bytes) => {
    if (!utf8Decoder || !windows1252Decoder) return '';
    let result = '';
    for (let index = 0; index < bytes.length; index += 1) {
      const byte = bytes[index];
      if (byte < 0x80) {
        result += String.fromCharCode(byte);
        continue;
      }

      let sequenceLength = 0;
      if (byte >= 0xc2 && byte <= 0xdf) {
        sequenceLength = 2;
      } else if (byte >= 0xe0 && byte <= 0xef) {
        sequenceLength = 3;
      } else if (byte >= 0xf0 && byte <= 0xf4) {
        sequenceLength = 4;
      }

      if (sequenceLength > 0 && validUtf8SequenceLength(bytes, index, sequenceLength) > 0) {
        result += utf8Decoder.decode(bytes.slice(index, index + sequenceLength));
        index += sequenceLength - 1;
        continue;
      }

      result += windows1252Decoder.decode(bytes.slice(index, index + 1));
    }
    return result;
  };

  const repairImportedMojibakeText = (value) => {
    if (!utf8Decoder || !windows1252Decoder) return value;

    let current = normalizeLostMojibakeSpaces(
      normalizeWindows1252Controls(decodeMojibakeHtmlEntities(value)),
    );
    let currentScore = mojibakeScore(current);
    for (let index = 0; index < 5 && currentScore > 0; index += 1) {
      const bytes = windows1252BytesFromString(current);
      if (!bytes || bytes.length === 0) break;

      const candidate = normalizeLostMojibakeSpaces(
        normalizeWindows1252Controls(utf8FromMixedWindows1252Bytes(bytes)),
      );
      if (candidate === '' || (candidate.match(/\?/g) || []).length > (current.match(/\?/g) || []).length) {
        break;
      }

      const candidateScore = mojibakeScore(candidate);
      if (candidateScore >= currentScore) break;
      current = candidate;
      currentScore = candidateScore;
    }

    return current;
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
    let importedImageDataUriTotalLength = 0;

    const sanitizeNode = (node) => {
      if (node.nodeType === Node.COMMENT_NODE) {
        node.parentNode && node.parentNode.removeChild(node);
        return;
      }
      if (node.nodeType === Node.TEXT_NODE) {
        node.nodeValue = repairImportedMojibakeText(node.nodeValue || '');
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
        if (src.startsWith('data:image/')) {
          const srcLength = src.length;
          if (
            srcLength > maxImportedImageDataUriLength
            || importedImageDataUriTotalLength + srcLength > maxImportedImageDataUriTotalLength
          ) {
            element.parentNode && element.parentNode.removeChild(element);
            return;
          }
          importedImageDataUriTotalLength += srcLength;
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

