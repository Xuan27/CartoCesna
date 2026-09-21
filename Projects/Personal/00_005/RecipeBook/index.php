<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Vanessa's Recipe Book</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header class="app-header">
  <div class="brand">
    <span class="brand-kicker">EST. FAMILY KITCHEN</span>
    <h1>Vanessa's Recipe Book</h1>
  </div>
  <div class="header-actions">
    <button id="exportBtn" class="btn btn-outline" type="button">
      <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v12m0 0l-4-4m4 4l4-4M5 21h14"/></svg>
      Download Full Book
    </button>
    <button id="addBtn" class="btn btn-primary" type="button">+ New Recipe</button>
  </div>
</header>

<div class="toolbar">
  <div class="search-wrap">
    <svg class="search-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
    <input type="search" id="searchInput" placeholder="Search recipes, ingredients, or steps…" autocomplete="off">
  </div>
  <div class="filter-row" id="categoryPills"></div>
  <div class="filter-row secondary">
    <select id="langFilter" class="select-chip">
      <option value="all">All languages</option>
      <option value="en">English</option>
      <option value="es">Español</option>
    </select>
    <select id="formatFilter" class="select-chip">
      <option value="all">All formats</option>
      <option value="page">Recipe Pages</option>
      <option value="card">Recipe Cards</option>
    </select>
    <button id="favFilterBtn" class="pill-toggle" type="button">★ Favorites</button>
  </div>
</div>

<main>
  <div id="resultsSummary" class="results-summary"></div>
  <div id="recipeGrid" class="recipe-grid"></div>
  <div id="emptyState" class="empty-state" hidden>
    <p>No recipes match your search.</p>
  </div>
</main>

<!-- Detail (read-only) modal -->
<div id="detailModal" class="modal-overlay" hidden>
  <div class="modal detail-modal">
    <button class="modal-close" type="button" aria-label="Close">&times;</button>
    <div id="detailContent"></div>
  </div>
</div>

<!-- Add / Edit modal -->
<div id="editModal" class="modal-overlay" hidden>
  <div class="modal edit-modal">
    <button class="modal-close" type="button" aria-label="Close">&times;</button>
    <h2 id="editTitle">New Recipe</h2>
    <form id="recipeForm">
      <input type="hidden" id="f_id" name="id">

      <div class="form-grid">
        <label class="field field-wide">
          <span>Dish name</span>
          <input type="text" id="f_title" name="title" required maxlength="255" placeholder="e.g. Caldo Tlalpeño">
        </label>

        <label class="field">
          <span>Category</span>
          <select id="f_category" name="category_id" required></select>
        </label>

        <label class="field">
          <span>Template</span>
          <select id="f_format" name="format">
            <option value="page">Recipe Page (entrée / large dish)</option>
            <option value="card">Recipe Card (salsa, side, small bite)</option>
          </select>
        </label>

        <label class="field">
          <span>Language</span>
          <select id="f_lang" name="lang">
            <option value="en">English</option>
            <option value="es">Español</option>
          </select>
        </label>

        <label class="field">
          <span>Serves</span>
          <input type="text" id="f_serves" name="serves" placeholder="e.g. 6">
        </label>

        <label class="field">
          <span>Prep time</span>
          <input type="text" id="f_prep" name="prep_time" placeholder="e.g. 15 minutes">
        </label>

        <label class="field">
          <span>Cook time</span>
          <input type="text" id="f_cook" name="cook_time" placeholder="e.g. 30 minutes">
        </label>
      </div>

      <div class="field-block">
        <div class="field-block-header">
          <span>Ingredients</span>
          <button type="button" class="btn btn-tiny" id="addIngredientSection">+ Add section</button>
        </div>
        <p class="field-hint">Leave a section's header blank for a plain list, or name it (e.g. "For the sauce") for multi-part recipes.</p>
        <div id="ingredientsEditor" class="sections-editor"></div>
      </div>

      <div class="field-block">
        <div class="field-block-header">
          <span>Directions</span>
          <button type="button" class="btn btn-tiny" id="addDirectionSection">+ Add section</button>
        </div>
        <div id="directionsEditor" class="sections-editor"></div>
      </div>

      <div class="form-grid">
        <label class="field field-wide">
          <span>Note <em>(optional — tips, storage, variations)</em></span>
          <textarea id="f_note" name="note" rows="2"></textarea>
        </label>
        <label class="field field-wide">
          <span>Source <em>(optional — website or "Family recipe")</em></span>
          <input type="text" id="f_source" name="source" placeholder="e.g. cookieandkate.com">
        </label>
      </div>

      <div class="field-block">
        <div class="field-block-header"><span>Photo <em>(optional)</em></span></div>
        <div class="photo-editor">
          <div id="photoPreviewWrap" class="photo-preview" hidden>
            <img id="photoPreview" alt="Recipe photo preview">
            <button type="button" id="removePhotoBtn" class="btn btn-tiny btn-danger-outline">Remove photo</button>
          </div>
          <input type="file" id="f_photo" name="photo" accept="image/png,image/jpeg,image/webp">
          <input type="hidden" id="f_remove_photo" name="remove_photo" value="0">
        </div>
      </div>

      <div class="form-actions">
        <button type="button" id="deleteBtn" class="btn btn-danger-outline" hidden>Delete recipe</button>
        <div class="form-actions-right">
          <button type="button" id="cancelEditBtn" class="btn btn-ghost">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Recipe</button>
        </div>
      </div>
    </form>
  </div>
</div>

<div id="toast" class="toast" hidden></div>

<script src="assets/js/app.js"></script>
</body>
</html>
