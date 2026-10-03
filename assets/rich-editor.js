'use strict';
(() => {
  const field = document.querySelector('#body-editor');
  const shell = document.querySelector('#rich-editor');
  if (!field || !shell) return;

  const canvas = shell.querySelector('.rich-canvas');
  const source = shell.querySelector('.rich-source');
  const status = shell.querySelector('.rich-status');
  const format = document.querySelector('#body-format');
  const linkDialog = document.querySelector('#rich-link-dialog');
  const linkUrl = linkDialog.querySelector('[name=rich-link-url]');
  const linkText = linkDialog.querySelector('[name=rich-link-text]');
  const linkRemove = linkDialog.querySelector('[data-link-remove]');
  let savedRange = null;
  let activeLink = null;
  let sourceMode = false;
  let touched = false;
  let syncTimer = null;

  const allowed = new Set(['p', 'h2', 'h3', 'h4', 'ul', 'ol', 'li', 'strong', 'b', 'em', 'i', 'blockquote', 'a', 'br', 'hr', 'code', 'pre', 'img', 'table', 'thead', 'tbody', 'tr', 'th', 'td']);
  const drop = new Set(['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math', 'form', 'input', 'button', 'textarea', 'select', 'video', 'audio', 'meta', 'link']);
  const safeUrl = value => /^\/(?!\/)[^\\\x00-\x20]*$/.test(value) || /^https?:\/\/[^\s]+$/i.test(value) && (() => { try { return ['http:', 'https:'].includes(new URL(value).protocol); } catch { return false; } })();
  const safeImage = value => !value.includes('..') && /^\/assets\/(images|uploads)\/[a-zA-Z0-9_/-]+\.(jpg|jpeg|png|webp|gif|svg)$/.test(value);

  function cleanFragment(html) {
    const parsed = new DOMParser().parseFromString(html, 'text/html');
    const fragment = document.createDocumentFragment();
    const copy = (node, parent) => {
      if (node.nodeType === Node.TEXT_NODE) { parent.appendChild(document.createTextNode(node.textContent)); return; }
      if (node.nodeType !== Node.ELEMENT_NODE) return;
      const tag = node.tagName.toLowerCase();
      if (drop.has(tag)) return;
      let output = parent;
      const style = node.getAttribute('style') || '';
      const mapped = tag === 'div' ? 'p' : tag === 'span' && /font-weight\s*:\s*(bold|[7-9]00)/i.test(style) ? 'strong' : tag === 'span' && /font-style\s*:\s*italic/i.test(style) ? 'em' : tag;
      if (allowed.has(mapped)) {
        if (mapped === 'a' && !safeUrl(node.getAttribute('href') || '')) return node.childNodes.forEach(child => copy(child, parent));
        if (mapped === 'img' && !safeImage(node.getAttribute('src') || '')) return;
        output = document.createElement(mapped);
        if (mapped === 'a') output.setAttribute('href', node.getAttribute('href'));
        if (mapped === 'img') { output.setAttribute('src', node.getAttribute('src')); output.setAttribute('alt', (node.getAttribute('alt') || '').slice(0, 200)); }
        parent.appendChild(output);
      }
      node.childNodes.forEach(child => copy(child, output));
    };
    parsed.body.childNodes.forEach(node => copy(node, fragment));
    const wrapper = document.createElement('div'); wrapper.appendChild(fragment);
    return wrapper.innerHTML;
  }

  function updateStatus() {
    const count = (sourceMode ? source.value.replace(/<[^>]*>/g, ' ') : canvas.textContent).trim().split(/\s+/).filter(Boolean).length;
    status.textContent = count + ' kelime · ' + (sourceMode ? 'HTML görünümü' : 'Görsel düzenleme');
  }
  function sync() {
    if (!touched) return;
    field.value = cleanFragment(sourceMode ? source.value : canvas.innerHTML);
    format.value = 'html';
    field.dispatchEvent(new Event('input', { bubbles: true }));
    updateStatus();
  }
  function changed() {
    touched = true;
    updateStatus();
    field.dispatchEvent(new Event('input', { bubbles: true }));
    clearTimeout(syncTimer);
    syncTimer = setTimeout(sync, 300);
  }
  function remember() {
    const selection = window.getSelection();
    if (selection.rangeCount && canvas.contains(selection.anchorNode)) savedRange = selection.getRangeAt(0).cloneRange();
  }
  function restore() {
    canvas.focus();
    if (!savedRange || !canvas.contains(savedRange.commonAncestorContainer)) return;
    const selection = window.getSelection(); selection.removeAllRanges(); selection.addRange(savedRange);
  }
  function insertHtml(html) { restore(); document.execCommand('insertHTML', false, html); changed(); remember(); }
  function containingLink(node) { return (node.nodeType === Node.ELEMENT_NODE ? node : node.parentElement)?.closest('a') || null; }
  function openLink() {
    restore(); const selection = window.getSelection();
    activeLink = containingLink(selection.anchorNode);
    if (activeLink && !canvas.contains(activeLink)) activeLink = null;
    remember();
    linkUrl.value = activeLink?.getAttribute('href') || '';
    linkText.value = activeLink?.textContent || selection.toString();
    linkRemove.hidden = !activeLink;
    linkDialog.showModal(); linkUrl.focus();
  }
  function closeLink() { linkDialog.close(); activeLink = null; }

  shell.querySelectorAll('[data-rich]').forEach(button => {
    button.addEventListener('pointerdown', remember);
    button.addEventListener('click', () => {
      const command = button.dataset.rich;
      if (command === 'source') {
        if (touched) { clearTimeout(syncTimer); sync(); }
        if (sourceMode) { canvas.innerHTML = cleanFragment(source.value); source.hidden = true; canvas.hidden = false; sourceMode = false; changed(); canvas.focus(); }
        else { source.value = touched ? field.value : canvas.innerHTML; canvas.hidden = true; source.hidden = false; sourceMode = true; source.focus(); updateStatus(); }
        button.setAttribute('aria-pressed', String(sourceMode)); return;
      }
      if (sourceMode) return;
      if (command === 'link') return openLink();
      if (command === 'unlink') { restore(); document.execCommand('unlink'); changed(); return; }
      if (command === 'image') { shell.dispatchEvent(new CustomEvent('rich-image-request', { bubbles: true })); return; }
      if (command === 'table') return insertHtml('<table><tbody><tr><th>Başlık 1</th><th>Başlık 2</th></tr><tr><td>Hücre 1</td><td>Hücre 2</td></tr></tbody></table><p><br></p>');
      restore();
      if (command === 'p' || /^h[2-4]$/.test(command) || command === 'blockquote') document.execCommand('formatBlock', false, command);
      else document.execCommand(command, false, null);
      changed(); remember();
    });
  });
  linkDialog.querySelector('[data-link-save]').addEventListener('click', () => {
    const url = linkUrl.value.trim();
    if (!safeUrl(url)) { linkUrl.setCustomValidity('Site içi /sayfa-yolu veya https:// ile başlayan geçerli bir adres girin.'); linkUrl.reportValidity(); return; }
    linkUrl.setCustomValidity('');
    const label = linkText.value.trim() || url;
    if (activeLink) { activeLink.href = url; activeLink.textContent = label; closeLink(); changed(); canvas.focus(); }
    else { closeLink(); restore(); const anchor = document.createElement('a'); anchor.href = url; anchor.textContent = label; const selection = window.getSelection(); if (selection.rangeCount) { selection.deleteFromDocument(); selection.getRangeAt(0).insertNode(anchor); changed(); } canvas.focus(); }
  });
  linkUrl.addEventListener('input', () => linkUrl.setCustomValidity(''));
  linkDialog.querySelector('[data-link-remove]').addEventListener('click', () => { if (activeLink) { activeLink.replaceWith(document.createTextNode(activeLink.textContent)); changed(); } closeLink(); canvas.focus(); });
  linkDialog.querySelector('[data-link-cancel]').addEventListener('click', closeLink);
  canvas.addEventListener('keyup', remember);
  canvas.addEventListener('mouseup', remember);
  canvas.addEventListener('input', changed);
  canvas.addEventListener('paste', event => {
    const html = event.clipboardData?.getData('text/html');
    const plain = event.clipboardData?.getData('text/plain');
    if (!html && !plain) return;
    event.preventDefault();
    const safe = html ? cleanFragment(html) : plain.split(/\n\s*\n/).map(p => '<p>' + p.replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('\n', '<br>') + '</p>').join('');
    if (safe) insertHtml(safe);
  });
  canvas.addEventListener('drop', event => { if (event.dataTransfer?.types.includes('Files') || event.dataTransfer?.types.includes('text/html')) event.preventDefault(); });
  canvas.addEventListener('keydown', event => { if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') { event.preventDefault(); openLink(); } });
  source.addEventListener('input', changed);
  field.closest('form').addEventListener('submit', event => {
    if (touched) { clearTimeout(syncTimer); sync(); }
    if ([...field.value].length > 150000) { event.preventDefault(); alert('İçerik 150.000 karakteri aşamaz.'); (sourceMode ? source : canvas).focus(); }
  });
  shell.addEventListener('rich-image-selected', event => {
    const { path, alt } = event.detail;
    if (!safeImage(path)) return;
    const img = document.createElement('img'); img.src = path; img.alt = alt || '';
    insertHtml(img.outerHTML);
  });
  field.hidden = true;
  document.querySelector('.editor-toolbar')?.setAttribute('hidden', '');
  document.querySelector('[data-format-select]')?.setAttribute('hidden', '');
  document.querySelector('#body-format-hint')?.setAttribute('hidden', '');
  shell.hidden = false;
  updateStatus();
})();
