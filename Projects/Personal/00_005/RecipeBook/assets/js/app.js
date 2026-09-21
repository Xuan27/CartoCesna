(function () {
  'use strict';

  const API = 'api/recipes_api.php';

  const state = {
    categories: [],
    catFilter: 'all',
    langFilter: 'all',
    formatFilter: 'all',
    favOnly: false,
    q: '',
    editingId: null,
    editingPhotoRemoved: false,
  };

  // ---------------------------------------------------------- utilities
  function $(sel, root) { return (root || document).querySelector(sel); }
  function $all(sel, root) { return Array.from((root || document).querySelectorAll(sel)); }
  function el(tag, attrs, children) {
    const node = document.createElement(tag);
    if (attrs) {
      for (const [k, v] of Object.entries(attrs)) {
        if (k === 'class') node.className = v;
        else if (k === 'html') node.innerHTML = v;
        else if (k.startsWith('on') && typeof v === 'function') node.addEventListener(k.slice(2), v);
        else node.setAttribute(k, v);
      }
    }
    (children || []).forEach(c => { if (c) node.appendChild(typeof c === 'string' ? document.createTextNode(c) : c); });
    return node;
  }
  function escapeHtml(s) {
    return (s ?? '').toString().replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m]));
  }
  function debounce(fn, ms) {
    let t;
    return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
  }
  function showToast(msg, isError) {
    const t = $('#toast');
    t.textContent = msg;
    t.className = 'toast' + (isError ? ' error' : '');
    t.hidden = false;
    clearTimeout(showToast._timer);
    showToast._timer = setTimeout(() => { t.hidden = true; }, 3200);
  }
  function catAccent(catId) {
    const n = state.categories.findIndex(c => String(c.id) === String(catId));
    return (n >= 0 && n < 5) ? (n + 1) : 'x';
  }
  function catName(catId, field) {
    const c = state.categories.find(c => String(c.id) === String(catId));
    return c ? c[field] : '';
  }

  // ---------------------------------------------------------- data loading
  async function api(action, opts = {}) {
    const { method = 'GET', params = {}, body = null } = opts;
    let url = `${API}?action=${encodeURIComponent(action)}`;
    for (const [k, v] of Object.entries(params)) {
      if (v !== undefined && v !== null && v !== '') url += `&${k}=${encodeURIComponent(v)}`;
    }
    const res = await fetch(url, { method, body });
    let data;
    try { data = await res.json(); } catch { data = { success: false, message: 'Invalid server response' }; }
    return data;
  }

  async function loadCategories() {
    const data = await api('categories');
    if (data.success) {
      state.categories = data.categories;
      renderCategoryPills();
      populateCategorySelect();
    }
  }

  function renderCategoryPills() {
    const wrap = $('#categoryPills');
    wrap.innerHTML = '';
    const totalCount = state.categories.reduce((s, c) => s + Number(c.recipe_count), 0);
    const allPill = el('button', {
      class: 'cat-pill' + (state.catFilter === 'all' ? ' active' : ''),
      type: 'button',
      onclick: () => { state.catFilter = 'all'; renderCategoryPills(); loadRecipes(); },
    }, [`All `, el('span', { class: 'count' }, [`(${totalCount})`])]);
    wrap.appendChild(allPill);
    state.categories.forEach(c => {
      const pill = el('button', {
        class: 'cat-pill' + (String(state.catFilter) === String(c.id) ? ' active' : ''),
        type: 'button',
        onclick: () => { state.catFilter = c.id; renderCategoryPills(); loadRecipes(); },
      }, [`${c.name_en} `, el('span', { class: 'count' }, [`(${c.recipe_count})`])]);
      wrap.appendChild(pill);
    });
  }

  function populateCategorySelect() {
    const sel = $('#f_category');
    sel.innerHTML = '';
    state.categories.forEach(c => {
      const label = c.name_es ? `${c.name_en} / ${c.name_es}` : c.name_en;
      sel.appendChild(el('option', { value: c.id }, [label]));
    });
  }

  async function loadRecipes() {
    const data = await api('list', {
      params: {
        q: state.q,
        category_id: state.catFilter,
        lang: state.langFilter,
        format: state.formatFilter,
        favorite: state.favOnly ? '1' : '',
      },
    });
    if (!data.success) { showToast(data.message || 'Failed to load recipes', true); return; }
    renderGrid(data.recipes);
    const summary = $('#resultsSummary');
    summary.textContent = data.count === 1 ? '1 recipe' : `${data.count} recipes`;
  }

  function renderGrid(recipes) {
    const grid = $('#recipeGrid');
    const empty = $('#emptyState');
    grid.innerHTML = '';
    if (!recipes.length) { empty.hidden = false; return; }
    empty.hidden = true;
    recipes.forEach(r => grid.appendChild(renderTile(r)));
  }

  function renderTile(r) {
    const accent = catAccent(r.category_id);
    const media = r.photo_url
      ? el('img', { src: r.photo_url, alt: '' })
      : document.createTextNode((r.title || '?').trim()[0].toUpperCase());
    const mediaWrap = el('div', { class: 'tile-media', style: `background:var(--cat-${accent}-bg); color:var(--cat-${accent})` }, [media]);
    mediaWrap.appendChild(el('div', { class: 'cat-stripe', style: `background:var(--cat-${accent})` }));

    const favBtn = el('button', {
      class: 'fav-star' + (r.is_favorite ? ' is-fav' : ''),
      type: 'button',
      title: r.is_favorite ? 'Remove from favorites' : 'Add to favorites',
      onclick: async (e) => {
        e.stopPropagation();
        const res = await api('toggle_favorite', { method: 'POST', body: new URLSearchParams({ id: r.id }) });
        if (res.success) loadRecipes();
      },
    }, [r.is_favorite ? '★' : '☆']);
    mediaWrap.appendChild(favBtn);

    const metaBits = [];
    if (r.serves) metaBits.push(`Serves ${escapeHtml(r.serves)}`);
    if (r.cook_time) metaBits.push(escapeHtml(r.cook_time));

    const tile = el('div', { class: 'recipe-card-tile', onclick: () => openDetail(r.id) }, [
      mediaWrap,
      el('div', { class: 'tile-body' }, [
        el('div', { class: 'tile-category', style: `color:var(--cat-${accent})` }, [r.category_name_en]),
        el('div', { class: 'tile-title' }, [r.title]),
        el('div', { class: 'tile-meta' }, [
          el('span', { class: 'badge badge-lang' }, [r.lang.toUpperCase()]),
          el('span', { class: 'badge badge-format' }, [r.format === 'page' ? 'Page' : 'Card']),
          ...(metaBits.length ? [el('span', {}, [metaBits.join(' · ')])] : []),
        ]),
      ]),
    ]);
    return tile;
  }

  // ---------------------------------------------------------- detail view
  async function openDetail(id) {
    const data = await api('get', { params: { id } });
    if (!data.success) { showToast(data.message || 'Could not load recipe', true); return; }
    renderDetail(data.recipe);
    $('#detailModal').hidden = false;
  }

  function sectionsHtml(sections, tag) {
    return sections.map(s => `
      ${s.header ? `<div class="detail-sub-header">${escapeHtml(s.header)}</div>` : ''}
      <${tag}>${s.items.map(i => `<li>${escapeHtml(i)}</li>`).join('')}</${tag}>
    `).join('');
  }

  function renderDetail(r) {
    const accent = catAccent(r.category_id);
    const metaCells = [];
    if (r.serves) metaCells.push(['Serves', r.serves]);
    if (r.prep_time) metaCells.push(['Prep time', r.prep_time]);
    if (r.cook_time) metaCells.push(['Cook time', r.cook_time]);

    const noteBits = [];
    if (r.note) noteBits.push(escapeHtml(r.note));
    if (r.source) noteBits.push(`<span class="detail-source">Source: ${escapeHtml(r.source)}</span>`);

    $('#detailContent').innerHTML = `
      <div class="detail-page">
        <div class="detail-kicker">
          ${r.format === 'page' ? 'Recipe Page' : 'Recipe Card'}
          <span class="badge badge-lang">${r.lang.toUpperCase()}</span>
          <span class="tile-category" style="color:var(--cat-${accent})">${escapeHtml(r.category_name_en)}</span>
        </div>
        <div class="detail-toolbar">
          <button class="btn btn-tiny btn-outline" id="detailEditBtn">Edit</button>
          <button class="btn btn-tiny btn-outline" id="detailPrintBtn">Print this recipe (PDF)</button>
          <button class="btn btn-tiny ${r.is_favorite ? '' : 'btn-outline'}" id="detailFavBtn" style="${r.is_favorite ? 'background:#d8b03e;border-color:#d8b03e;' : ''}">${r.is_favorite ? '★ Favorited' : '☆ Add to favorites'}</button>
        </div>
        <div class="detail-header-box">
          <div class="detail-name-row">${escapeHtml(r.title)}</div>
          ${metaCells.length ? `<div class="detail-meta-row">${metaCells.map(([k, v]) => `<div class="detail-meta-cell"><b>${k}</b>${escapeHtml(v)}</div>`).join('')}</div>` : ''}
        </div>
        <div class="detail-body">
          ${r.format === 'page' ? `<div class="detail-photo">${r.photo_url ? `<img src="${r.photo_url}" alt="">` : 'PHOTO'}</div>` : ''}
          <div class="detail-section">
            <div class="detail-label">Ingredients</div>
            ${sectionsHtml(r.ingredients, 'ul')}
          </div>
          ${r.format === 'card' ? `<div class="detail-section">
            <div class="detail-label">Directions</div>
            ${sectionsHtml(r.directions, 'ol')}
          </div>` : ''}
        </div>
        ${r.format === 'page' ? `<div class="detail-directions">
          <div class="detail-label">Directions</div>
          ${sectionsHtml(r.directions, 'ol')}
        </div>` : ''}
        ${noteBits.length ? `<div class="detail-note"><b>Note</b>${noteBits.join('<br>')}</div>` : ''}
      </div>
    `;

    $('#detailEditBtn').addEventListener('click', () => { closeModal($('#detailModal')); openEditor(r); });
    $('#detailPrintBtn').addEventListener('click', () => window.open(`api/export_pdf.php?id=${r.id}`, '_blank'));
    $('#detailFavBtn').addEventListener('click', async () => {
      const res = await api('toggle_favorite', { method: 'POST', body: new URLSearchParams({ id: r.id }) });
      if (res.success) { r.is_favorite = res.is_favorite; renderDetail(r); loadRecipes(); }
    });
  }

  // ---------------------------------------------------------- section editor (ingredients / directions)
  function makeItemRow(container, value, placeholder) {
    const input = el('input', { type: 'text', value: value || '', placeholder });
    const row = el('div', { class: 'item-row' }, [
      input,
      el('button', { class: 'icon-btn', type: 'button', title: 'Remove', onclick: () => row.remove() }, ['×']),
    ]);
    container.appendChild(row);
    return row;
  }

  function makeSectionBlock(header, items, kind) {
    const isIng = kind === 'ingredient';
    const itemsWrap = el('div', { class: 'items-wrap' });
    (items && items.length ? items : ['']).forEach(v => makeItemRow(itemsWrap, v, isIng ? 'e.g. 2 cups flour' : 'e.g. Preheat oven to 350°F'));

    const block = el('div', { class: 'section-block' }, [
      el('div', { class: 'section-block-header' }, [
        el('input', { class: 'section-header-input', type: 'text', value: header || '', placeholder: 'Section header (optional)' }),
      ]),
      itemsWrap,
      el('div', { class: 'section-actions' }, [
        el('button', { class: 'btn btn-tiny', type: 'button', onclick: () => makeItemRow(itemsWrap, '', isIng ? 'e.g. 2 cups flour' : 'e.g. Preheat oven to 350°F') }, [isIng ? '+ Add ingredient' : '+ Add step']),
        el('button', { class: 'remove-section-btn', type: 'button', onclick: () => block.remove() }, ['Remove section']),
      ]),
    ]);
    return block;
  }

  function buildSectionsEditor(containerId, sections, kind) {
    const container = $(containerId);
    container.innerHTML = '';
    const secs = (sections && sections.length) ? sections : [{ header: null, items: [''] }];
    secs.forEach(s => container.appendChild(makeSectionBlock(s.header, s.items, kind)));
  }

  function collectSections(containerId) {
    const container = $(containerId);
    const blocks = $all('.section-block', container);
    const out = [];
    blocks.forEach(b => {
      const header = $('.section-header-input', b).value.trim() || null;
      const items = $all('.items-wrap input', b).map(i => i.value.trim()).filter(v => v);
      if (items.length) out.push({ header, items });
    });
    return out;
  }

  // ---------------------------------------------------------- editor modal
  function openEditor(recipe) {
    const form = $('#recipeForm');
    form.reset();
    state.editingPhotoRemoved = false;
    $('#f_remove_photo').value = '0';
    $('#photoPreviewWrap').hidden = true;

    if (recipe) {
      state.editingId = recipe.id;
      $('#editTitle').textContent = 'Edit Recipe';
      $('#deleteBtn').hidden = false;
      $('#f_id').value = recipe.id;
      $('#f_title').value = recipe.title;
      $('#f_category').value = recipe.category_id;
      $('#f_format').value = recipe.format;
      $('#f_lang').value = recipe.lang;
      $('#f_serves').value = recipe.serves || '';
      $('#f_prep').value = recipe.prep_time || '';
      $('#f_cook').value = recipe.cook_time || '';
      $('#f_note').value = recipe.note || '';
      $('#f_source').value = recipe.source || '';
      buildSectionsEditor('#ingredientsEditor', recipe.ingredients, 'ingredient');
      buildSectionsEditor('#directionsEditor', recipe.directions, 'direction');
      if (recipe.photo_url) {
        $('#photoPreview').src = recipe.photo_url;
        $('#photoPreviewWrap').hidden = false;
      }
    } else {
      state.editingId = null;
      $('#editTitle').textContent = 'New Recipe';
      $('#deleteBtn').hidden = true;
      $('#f_id').value = '';
      if (state.catFilter !== 'all') $('#f_category').value = state.catFilter;
      buildSectionsEditor('#ingredientsEditor', null, 'ingredient');
      buildSectionsEditor('#directionsEditor', null, 'direction');
    }

    $('#editModal').hidden = false;
  }

  function closeModal(modal) { modal.hidden = true; }

  async function handleFormSubmit(e) {
    e.preventDefault();
    const ingredients = collectSections('#ingredientsEditor');
    const directions = collectSections('#directionsEditor');
    if (!ingredients.length) { showToast('Add at least one ingredient.', true); return; }
    if (!directions.length) { showToast('Add at least one direction step.', true); return; }

    const fd = new FormData();
    fd.append('title', $('#f_title').value.trim());
    fd.append('category_id', $('#f_category').value);
    fd.append('format', $('#f_format').value);
    fd.append('lang', $('#f_lang').value);
    fd.append('serves', $('#f_serves').value.trim());
    fd.append('prep_time', $('#f_prep').value.trim());
    fd.append('cook_time', $('#f_cook').value.trim());
    fd.append('note', $('#f_note').value.trim());
    fd.append('source', $('#f_source').value.trim());
    fd.append('ingredients_json', JSON.stringify(ingredients));
    fd.append('directions_json', JSON.stringify(directions));
    fd.append('remove_photo', $('#f_remove_photo').value);
    const photoFile = $('#f_photo').files[0];
    if (photoFile) fd.append('photo', photoFile);

    let action = 'create';
    if (state.editingId) { action = 'update'; fd.append('id', state.editingId); }

    const res = await api(action, { method: 'POST', body: fd });
    if (res.success) {
      showToast(res.message || 'Saved');
      closeModal($('#editModal'));
      loadCategories();
      loadRecipes();
    } else {
      showToast(res.message || 'Could not save recipe', true);
    }
  }

  async function handleDelete() {
    if (!state.editingId) return;
    if (!confirm('Delete this recipe? This cannot be undone.')) return;
    const res = await api('delete', { method: 'POST', body: new URLSearchParams({ id: state.editingId }) });
    if (res.success) {
      showToast('Recipe deleted');
      closeModal($('#editModal'));
      loadCategories();
      loadRecipes();
    } else {
      showToast(res.message || 'Could not delete recipe', true);
    }
  }

  // ---------------------------------------------------------- wiring
  function wireEvents() {
    $('#searchInput').addEventListener('input', debounce((e) => { state.q = e.target.value; loadRecipes(); }, 250));
    $('#langFilter').addEventListener('change', (e) => { state.langFilter = e.target.value; loadRecipes(); });
    $('#formatFilter').addEventListener('change', (e) => { state.formatFilter = e.target.value; loadRecipes(); });
    $('#favFilterBtn').addEventListener('click', () => {
      state.favOnly = !state.favOnly;
      $('#favFilterBtn').classList.toggle('active', state.favOnly);
      loadRecipes();
    });
    $('#addBtn').addEventListener('click', () => openEditor(null));
    $('#exportBtn').addEventListener('click', () => window.open('api/export_pdf.php', '_blank'));

    $all('.modal-close').forEach(btn => btn.addEventListener('click', (e) => closeModal(e.target.closest('.modal-overlay'))));
    $all('.modal-overlay').forEach(overlay => overlay.addEventListener('click', (e) => { if (e.target === overlay) closeModal(overlay); }));
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') $all('.modal-overlay').forEach(m => { if (!m.hidden) closeModal(m); });
    });

    $('#recipeForm').addEventListener('submit', handleFormSubmit);
    $('#cancelEditBtn').addEventListener('click', () => closeModal($('#editModal')));
    $('#deleteBtn').addEventListener('click', handleDelete);
    $('#addIngredientSection').addEventListener('click', () => $('#ingredientsEditor').appendChild(makeSectionBlock('', [''], 'ingredient')));
    $('#addDirectionSection').addEventListener('click', () => $('#directionsEditor').appendChild(makeSectionBlock('', [''], 'direction')));

    $('#f_photo').addEventListener('change', () => {
      const file = $('#f_photo').files[0];
      if (!file) return;
      $('#f_remove_photo').value = '0';
      const reader = new FileReader();
      reader.onload = (e) => { $('#photoPreview').src = e.target.result; $('#photoPreviewWrap').hidden = false; };
      reader.readAsDataURL(file);
    });
    $('#removePhotoBtn').addEventListener('click', () => {
      $('#f_photo').value = '';
      $('#photoPreviewWrap').hidden = true;
      $('#f_remove_photo').value = '1';
    });
  }

  async function init() {
    wireEvents();
    await loadCategories();
    await loadRecipes();
  }

  document.addEventListener('DOMContentLoaded', init);
})();
