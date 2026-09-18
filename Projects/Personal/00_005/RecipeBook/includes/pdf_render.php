<?php
/**
 * Renders the recipe catalog (fetched live from the database) into the exact
 * printable "Recipe Page" / "Recipe Card" template, as one HTML document
 * ready to hand to WeasyPrint. This is the single source of truth for the
 * printed book — it is regenerated on demand from whatever is in the
 * database right now, so the web app and the PDF can never drift apart.
 */

function pr_esc(?string $s): string {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

function pr_metaRow(array $r): string {
    $cells = [];
    if (!empty($r['serves']))    $cells[] = ['SERVES', $r['serves']];
    if (!empty($r['prep_time'])) $cells[] = ['PREP TIME', $r['prep_time']];
    if (!empty($r['cook_time'])) $cells[] = ['COOK TIME', $r['cook_time']];
    if (empty($cells)) return '';
    $tds = '';
    foreach ($cells as [$k, $v]) {
        $tds .= '<div class="meta-cell"><span class="meta-label">' . pr_esc($k) . ':</span> ' . pr_esc($v) . '</div>';
    }
    return '<div class="meta-row">' . $tds . '</div>';
}

function pr_sections(array $sections, string $tag): string {
    $out = '';
    foreach ($sections as $section) {
        if (!empty($section['header'])) {
            $out .= '<div class="sub-header">' . pr_esc($section['header']) . '</div>';
        }
        $out .= "<{$tag} class=\"" . ($tag === 'ul' ? 'ing-list' : 'dir-list') . '">';
        foreach ($section['items'] as $item) {
            $out .= '<li>' . pr_esc($item) . '</li>';
        }
        $out .= "</{$tag}>";
    }
    return $out;
}

function pr_noteHtml(array $r): string {
    $parts = [];
    if (!empty($r['note']))   $parts[] = pr_esc($r['note']);
    if (!empty($r['source'])) $parts[] = '<span class="source">Source: ' . pr_esc($r['source']) . '</span>';
    if (empty($parts)) return '';
    return '<div class="note-box"><div class="note-label">NOTE</div><div class="note-body">' . implode('<br>', $parts) . '</div></div>';
}

function pr_photoBox(array $r): string {
    if (!empty($r['photo_path'])) {
        $src = 'file://' . $r['photo_path'];
        return '<div class="photo-box photo-filled"><img src="' . pr_esc($src) . '" alt=""></div>';
    }
    return '<div class="photo-box"><span>PHOTO</span></div>';
}

function pr_langTag(array $r): string {
    return '<span class="lang-tag">' . ($r['lang'] === 'es' ? 'ES' : 'EN') . '</span>';
}

function pr_renderPage(array $r): string {
    $ing = normalizeSections($r['ingredients_json']);
    $dir = normalizeSections($r['directions_json']);
    return '
<section class="recipe-page">
  <div class="page-kicker">Recipe Page ' . pr_langTag($r) . '</div>
  <div class="header-box">
    <div class="name-row"><span class="name-label">NAME OF DISH:</span> <span class="name-value">' . pr_esc($r['title']) . '</span></div>
    ' . pr_metaRow($r) . '
  </div>
  <div class="body-columns">
    <div class="photo-col">' . pr_photoBox($r) . '</div>
    <div class="ingredients-col">
      <div class="section-label">INGREDIENTS</div>
      ' . pr_sections($ing, 'ul') . '
    </div>
  </div>
  <div class="directions-block">
    <div class="section-label">DIRECTIONS</div>
    ' . pr_sections($dir, 'ol') . '
  </div>
  ' . pr_noteHtml($r) . '
</section>';
}

function pr_renderCard(array $r): string {
    $ing = normalizeSections($r['ingredients_json']);
    $dir = normalizeSections($r['directions_json']);
    $noteParts = [];
    if (!empty($r['note']))   $noteParts[] = pr_esc($r['note']);
    if (!empty($r['source'])) $noteParts[] = '<span class="source">Source: ' . pr_esc($r['source']) . '</span>';
    $noteHtml = $noteParts ? '<div class="card-note">' . implode('<br>', $noteParts) . '</div>' : '';
    return '
<div class="recipe-card">
  <div class="page-kicker">Recipe Card ' . pr_langTag($r) . '</div>
  <div class="header-box">
    <div class="name-row"><span class="name-label">NAME OF DISH:</span> <span class="name-value">' . pr_esc($r['title']) . '</span></div>
    ' . pr_metaRow($r) . '
  </div>
  <div class="card-columns">
    <div class="ingredients-col">
      <div class="section-label">INGREDIENTS</div>
      ' . pr_sections($ing, 'ul') . '
    </div>
    <div class="directions-col">
      <div class="section-label">DIRECTIONS</div>
      ' . pr_sections($dir, 'ol') . '
    </div>
  </div>
  ' . $noteHtml . '
</div>';
}

function pr_renderDivider(int $idx, string $en, string $es): string {
    $sub = $es !== '' ? '<h2 class="divider-subtitle">' . pr_esc($es) . '</h2>' : '';
    return '
<section class="divider-page">
  <div class="divider-number">' . sprintf('%02d', $idx) . '</div>
  <h1 class="divider-title">' . pr_esc($en) . '</h1>
  ' . $sub . '
  <div class="divider-rule"></div>
</section>';
}

function pr_css(): string {
    return <<<CSS
@page { size: Letter; margin: 0.45in 0.55in; }
* { box-sizing: border-box; }
body { font-family: "Liberation Sans", sans-serif; color: #2b2622; font-size: 10pt; line-height: 1.28; }
.divider-page { height: 9.6in; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; page-break-after: always; break-after: page; }
.divider-number { font-family: "Liberation Serif", serif; font-size: 15pt; color: #b7ab98; letter-spacing: 6px; margin-bottom: 0.15in; }
.divider-title { font-family: "Liberation Serif", serif; font-weight: normal; font-size: 34pt; margin: 0; color: #2b2622; }
.divider-subtitle { font-family: "Liberation Serif", serif; font-style: italic; font-weight: normal; font-size: 17pt; margin: 0.12in 0 0 0; color: #6b6459; }
.divider-rule { width: 1.4in; height: 1.5pt; background: #b7ab98; margin-top: 0.35in; }
.page-kicker { font-family: "Liberation Serif", serif; font-size: 18pt; color: #2b2622; margin-bottom: 0.09in; }
.page-kicker .lang-tag { font-family: "Liberation Sans", sans-serif; font-size: 8pt; vertical-align: middle; color: #fff; background: #a9a196; border-radius: 3px; padding: 1px 6px; margin-left: 8px; }
.header-box { border: 1.1pt solid #2b2622; }
.name-row { border-bottom: 1.1pt solid #2b2622; padding: 4px 8px; font-size: 10.5pt; }
.name-label { font-weight: bold; letter-spacing: 0.4px; }
.name-value { font-weight: bold; }
.meta-row { display: flex; }
.meta-cell { flex: 1; padding: 4px 8px; border-right: 1.1pt solid #2b2622; font-size: 9.3pt; }
.meta-cell:last-child { border-right: none; }
.meta-label { font-weight: bold; }
.section-label { font-family: "Liberation Sans", sans-serif; font-weight: bold; font-size: 9.6pt; letter-spacing: 1.4px; color: #6b6459; margin: 0.09in 0 0.04in 0; border-bottom: 0.8pt solid #cfc7ba; padding-bottom: 2px; }
.sub-header { font-weight: bold; font-style: italic; font-size: 9.6pt; margin: 0.05in 0 0.01in 0; color: #4a4137; }
ul.ing-list, ol.dir-list { margin: 0 0 0.04in 0; padding-left: 1.05em; }
ul.ing-list li, ol.dir-list li { margin-bottom: 1.5px; font-size: 9.4pt; }
ul.ing-list { list-style-type: square; }
ul.ing-list li::marker { color: #a9a196; }
.recipe-page { page-break-after: always; break-after: page; }
.recipe-page .body-columns { display: flex; gap: 0.28in; margin-top: 0.1in; }
.recipe-page .photo-col { width: 2.15in; flex-shrink: 0; }
.photo-box { width: 2.15in; height: 2.15in; border: 1.1pt solid #2b2622; display: flex; align-items: center; justify-content: center; color: #b7ab98; font-family: "Liberation Sans", sans-serif; letter-spacing: 2px; font-size: 9pt; overflow: hidden; }
.photo-box.photo-filled { padding: 0; }
.photo-box img { width: 100%; height: 100%; object-fit: cover; }
.recipe-page .ingredients-col { flex: 1; }
.recipe-page .directions-block { margin-top: 0.02in; }
.note-box { margin-top: 0.1in; border: 1.1pt solid #2b2622; padding: 5px 8px; min-height: 0.4in; }
.note-label { font-weight: bold; font-size: 9pt; letter-spacing: 1.4px; color: #6b6459; margin-bottom: 3px; }
.note-body { font-size: 9pt; color: #4a4137; }
.note-body .source { font-style: italic; color: #8a8175; }
.card-sheet { height: 9.6in; display: flex; flex-direction: column; page-break-after: always; break-after: page; }
.recipe-card { flex: 1; padding: 0.16in 0.05in; position: relative; }
.recipe-card + .recipe-card { border-top: 1pt dashed #b7ab98; }
.recipe-card .page-kicker { font-size: 15pt; margin-bottom: 0.08in; }
.card-columns { display: flex; gap: 0.3in; margin-top: 0.1in; }
.card-columns .ingredients-col, .card-columns .directions-col { flex: 1; }
.card-note { margin-top: 0.08in; font-size: 8.6pt; color: #6b6459; border-top: 0.8pt solid #cfc7ba; padding-top: 4px; }
.card-note .source { font-style: italic; color: #8a8175; }
CSS;
}

/**
 * $categories: list of ['id'=>, 'name_en'=>, 'name_es'=>] in sort order
 * $recipesByCategory: [category_id => [recipe row, ...]]
 */
function pr_buildBookHtml(array $categories, array $recipesByCategory): string {
    $body = '';
    $idx = 0;
    foreach ($categories as $cat) {
        $idx++;
        $recipes = $recipesByCategory[$cat['id']] ?? [];
        if (empty($recipes)) continue;
        $body .= pr_renderDivider($idx, $cat['name_en'], $cat['name_es']);
        $pages = array_values(array_filter($recipes, fn($r) => $r['format'] === 'page'));
        $cards = array_values(array_filter($recipes, fn($r) => $r['format'] === 'card'));
        usort($pages, fn($a, $b) => strcmp($a['title'], $b['title']));
        usort($cards, fn($a, $b) => strcmp($a['title'], $b['title']));
        foreach ($pages as $r) $body .= pr_renderPage($r);
        for ($i = 0; $i < count($cards); $i += 2) {
            $pair = array_slice($cards, $i, 2);
            $inner = implode('', array_map('pr_renderCard', $pair));
            $body .= '<div class="card-sheet">' . $inner . '</div>';
        }
    }
    return '<!DOCTYPE html><html><head><meta charset="utf-8"><style>' . pr_css() . '</style></head><body>' . $body . '</body></html>';
}
