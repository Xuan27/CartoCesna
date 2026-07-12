<?php
require_once '../../../classes/Security.php';
Security::secureSession();
if (empty($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: ../../../login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grocery &amp; Meal Planner</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <link rel="stylesheet" href="../../../Models/css/survey_projects_notes.css">
    <link rel="stylesheet" href="../../../Models/css/grocery_meal.css">
</head>
<body>

    <div class="container">
        <button class="mobile-toggle" id="mobileToggle"><i class="fas fa-bars"></i></button>
        <div class="sidebar" id="sidebar">
            <button class="toggle-btn" id="toggleBtn"><i class="fas fa-chevron-left"></i></button>
            <div class="sidebar-header">
                <h1><i class="fas fa-basket-shopping"></i> Grocery &amp; Meals</h1>
                <p>Weekly meal &amp; shopping planner</p>
            </div>

            <nav class="sidebar-nav">
                <a href="#" class="nav-item active" onclick="switchTab('overview', this); return false;" data-tab="overview">
                    <i class="fas fa-gauge-high"></i> Overview
                </a>
                <a href="#" class="nav-item" onclick="switchTab('meals', this); return false;" data-tab="meals">
                    <i class="fas fa-utensils"></i> Meals
                </a>
                <a href="#" class="nav-item" onclick="switchTab('products', this); return false;" data-tab="products">
                    <i class="fas fa-carrot"></i> Products &amp; Prices
                </a>
                <a href="#" class="nav-item" onclick="switchTab('planner', this); return false;" data-tab="planner">
                    <i class="fas fa-calendar-week"></i> Meal Plan
                </a>
                <a href="#" class="nav-item" onclick="switchTab('shopping', this); return false;" data-tab="shopping">
                    <i class="fas fa-cart-shopping"></i> Shopping List
                </a>
                <a href="#" class="nav-item" onclick="switchTab('health', this); return false;" data-tab="health">
                    <i class="fas fa-heart-pulse"></i> Health Profile
                </a>
            </nav>
        </div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <div class="top-bar-left">
                <button class="mobile-menu-button" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
                <div>
                    <h2 id="tabTitle">Overview</h2>
                    <p id="tabSubtitle">This week at a glance</p>
                </div>
            </div>
            <div class="top-bar-right" id="topBarActions"></div>
        </div>

        <div class="content-area">

            <!-- ============ OVERVIEW ============ -->
            <div class="tab-panel active" id="tab-overview">
                <div class="gm-stat-grid" id="overviewStats"></div>
                <div class="gm-panel">
                    <h3><i class="fas fa-calendar-week"></i> This week's plan</h3>
                    <div id="overviewPlan" class="gm-empty-inline">Loading...</div>
                </div>
            </div>

            <!-- ============ MEALS ============ -->
            <div class="tab-panel" id="tab-meals">
                <div class="gm-filter-bar">
                    <select id="mealTypeFilter" onchange="loadMeals()">
                        <option value="">All meal types</option>
                        <option value="breakfast">Breakfast</option>
                        <option value="lunch">Lunch</option>
                        <option value="dinner">Dinner</option>
                        <option value="snack">Snack</option>
                    </select>
                </div>
                <div id="mealsContainer" class="gm-card-grid"></div>
            </div>

            <!-- ============ PRODUCTS & PRICES ============ -->
            <div class="tab-panel" id="tab-products">
                <div id="productsContainer"></div>
            </div>

            <!-- ============ MEAL PLAN ============ -->
            <div class="tab-panel" id="tab-planner">
                <div class="gm-filter-bar">
                    <button class="btn btn-secondary btn-sm" onclick="changeWeek(-7)"><i class="fas fa-chevron-left"></i></button>
                    <span id="plannerWeekLabel" class="gm-week-label"></span>
                    <button class="btn btn-secondary btn-sm" onclick="changeWeek(7)"><i class="fas fa-chevron-right"></i></button>
                </div>
                <div id="plannerGrid" class="gm-planner-grid"></div>
            </div>

            <!-- ============ SHOPPING LIST ============ -->
            <div class="tab-panel" id="tab-shopping">
                <div class="gm-filter-bar">
                    <span id="shoppingWeekLabel" class="gm-week-label"></span>
                </div>
                <div id="shoppingContainer"></div>
            </div>

            <!-- ============ HEALTH PROFILE ============ -->
            <div class="tab-panel" id="tab-health">
                <div class="gm-panel" style="max-width: 640px;">
                    <h3><i class="fas fa-heart-pulse"></i> Targets &amp; restrictions</h3>
                    <p class="gm-hint">Entered manually. Used to filter meal suggestions (allergies/restrictions) and to size portions during auto-generate (calorie target).</p>
                    <form id="healthForm" onsubmit="return false;">
                        <div class="form-grid" style="grid-template-columns: 1fr 1fr 1fr; margin-bottom: 1rem;">
                            <div class="form-group">
                                <label class="form-label">Calorie target / day</label>
                                <input type="number" class="form-input" id="hpCalories" min="0" placeholder="2200">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Protein target (g)</label>
                                <input type="number" class="form-input" id="hpProtein" min="0" placeholder="150">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Carbs target (g)</label>
                                <input type="number" class="form-input" id="hpCarbs" min="0" placeholder="220">
                            </div>
                        </div>
                        <div class="form-grid" style="grid-template-columns: 1fr; margin-bottom: 1rem;">
                            <div class="form-group">
                                <label class="form-label">Fat target (g)</label>
                                <input type="number" class="form-input" id="hpFat" min="0" placeholder="70">
                            </div>
                        </div>
                        <div class="form-group" style="margin-bottom: 1rem;">
                            <label class="form-label">Allergies <span class="gm-hint-inline">(comma-separated tags, matched against meal tags)</span></label>
                            <input type="text" class="form-input" id="hpAllergies" placeholder="peanuts, shellfish">
                        </div>
                        <div class="form-group" style="margin-bottom: 1rem;">
                            <label class="form-label">Dietary restrictions</label>
                            <input type="text" class="form-input" id="hpRestrictions" placeholder="vegetarian, low-sodium">
                        </div>
                        <div class="form-group" style="margin-bottom: 1rem;">
                            <label class="form-label">Notes <span class="gm-hint-inline">(e.g. biomarker flags from Function Health — no automated import yet, paste relevant notes here)</span></label>
                            <textarea class="form-input" id="hpNotes" rows="3" placeholder="e.g. elevated LDL - prefer lower saturated fat meals"></textarea>
                        </div>
                        <button type="button" class="btn btn-primary" onclick="saveHealthProfile()"><i class="fas fa-save"></i> Save profile</button>
                    </form>
                </div>
            </div>

        </div>
    </div>

    <!-- ============ MEAL MODAL ============ -->
    <div class="checklist-modal" id="mealModal">
        <div class="checklist-modal-content" style="max-width: 640px; max-height: 90vh;">
            <div class="checklist-modal-header">
                <div class="checklist-modal-header-top">
                    <h3><i class="fas fa-utensils"></i> <span id="mealModalTitle">Add Meal</span></h3>
                    <button class="checklist-modal-close" onclick="closeModal('mealModal')"><i class="fas fa-times"></i></button>
                </div>
            </div>
            <div class="checklist-modal-body" style="padding: 1.5rem;">
                <form id="mealForm" onsubmit="return false;">
                    <input type="hidden" id="editMealId" value="">
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label class="form-label">Meal name <span style="color: var(--danger-color);">*</span></label>
                        <input type="text" class="form-input" id="mealName" placeholder="Sheet-pan chicken fajitas" required>
                    </div>
                    <div class="form-grid" style="grid-template-columns: 1fr 1fr; margin-bottom: 1rem;">
                        <div class="form-group">
                            <label class="form-label">Type</label>
                            <select class="form-input" id="mealType">
                                <option value="breakfast">Breakfast</option>
                                <option value="lunch">Lunch</option>
                                <option value="dinner" selected>Dinner</option>
                                <option value="snack">Snack</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Servings</label>
                            <input type="number" class="form-input" id="mealServings" min="1" value="4">
                        </div>
                    </div>
                    <div class="form-grid" style="grid-template-columns: 1fr 1fr 1fr; margin-bottom: 1rem;">
                        <div class="form-group">
                            <label class="form-label">Calories</label>
                            <input type="number" class="form-input" id="mealCalories" min="0">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Protein (g)</label>
                            <input type="number" class="form-input" id="mealProtein" min="0">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Carbs (g)</label>
                            <input type="number" class="form-input" id="mealCarbs" min="0">
                        </div>
                    </div>
                    <div class="form-grid" style="grid-template-columns: 1fr; margin-bottom: 1rem;">
                        <div class="form-group">
                            <label class="form-label">Fat (g)</label>
                            <input type="number" class="form-input" id="mealFat" min="0">
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label class="form-label">Tags <span class="gm-hint-inline">(comma-separated, e.g. vegetarian, gluten-free)</span></label>
                        <input type="text" class="form-input" id="mealTags" placeholder="vegetarian, quick">
                    </div>
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label class="form-label">Instructions</label>
                        <textarea class="form-input" id="mealInstructions" rows="3"></textarea>
                    </div>

                    <div class="form-group" style="margin-bottom: 0.5rem;">
                        <label class="form-label">Ingredients</label>
                    </div>
                    <div id="ingredientRows"></div>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="addIngredientRow()" style="margin-bottom: 1rem;">
                        <i class="fas fa-plus"></i> Add ingredient
                    </button>

                    <div style="display:flex; justify-content:flex-end; gap:0.5rem; margin-top: 1rem;">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('mealModal')">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="saveMeal()"><i class="fas fa-save"></i> Save meal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ============ PRODUCT MODAL ============ -->
    <div class="checklist-modal" id="productModal">
        <div class="checklist-modal-content" style="max-width: 480px;">
            <div class="checklist-modal-header">
                <div class="checklist-modal-header-top">
                    <h3><i class="fas fa-carrot"></i> <span id="productModalTitle">Add Product</span></h3>
                    <button class="checklist-modal-close" onclick="closeModal('productModal')"><i class="fas fa-times"></i></button>
                </div>
            </div>
            <div class="checklist-modal-body" style="padding: 1.5rem;">
                <form id="productForm" onsubmit="return false;">
                    <input type="hidden" id="editProductId" value="">
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label class="form-label">Product name <span style="color: var(--danger-color);">*</span></label>
                        <input type="text" class="form-input" id="productName" placeholder="Boneless chicken thighs" required>
                    </div>
                    <div class="form-grid" style="grid-template-columns: 1fr 1fr; margin-bottom: 1rem;">
                        <div class="form-group">
                            <label class="form-label">Category</label>
                            <input type="text" class="form-input" id="productCategory" placeholder="Meat">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Unit</label>
                            <input type="text" class="form-input" id="productUnit" placeholder="lb" value="each">
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label class="form-label">Notes</label>
                        <textarea class="form-input" id="productNotes" rows="2"></textarea>
                    </div>
                    <div style="display:flex; justify-content:flex-end; gap:0.5rem;">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('productModal')">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="saveProduct()"><i class="fas fa-save"></i> Save product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ============ PRODUCT PRICE DETAIL MODAL ============ -->
    <div class="checklist-modal" id="priceModal">
        <div class="checklist-modal-content" style="max-width: 640px; max-height: 85vh;">
            <div class="checklist-modal-header">
                <div class="checklist-modal-header-top">
                    <h3><i class="fas fa-tags"></i> <span id="priceModalTitle">Prices</span></h3>
                    <button class="checklist-modal-close" onclick="closeModal('priceModal')"><i class="fas fa-times"></i></button>
                </div>
            </div>
            <div class="checklist-modal-body" style="padding: 1.5rem;">
                <div id="priceLatest" class="gm-price-latest"></div>

                <h4 class="gm-subhead">Add a manual price</h4>
                <div class="gm-inline-form">
                    <select id="priceStoreSelect"></select>
                    <input type="number" id="priceValue" step="0.01" min="0" placeholder="Price ($)">
                    <input type="date" id="priceDate">
                    <button class="btn btn-primary btn-sm" onclick="saveManualPrice()"><i class="fas fa-plus"></i> Add</button>
                </div>

                <h4 class="gm-subhead">Best-effort store lookup <span class="gm-hint-inline">(unverified — always confirm before saving)</span></h4>
                <div class="gm-inline-form" id="scrapeControls"></div>
                <div id="scrapeResult"></div>

                <h4 class="gm-subhead">Price history</h4>
                <div id="priceHistory"></div>
            </div>
        </div>
    </div>

    <!-- ============ PLAN ENTRY MODAL ============ -->
    <div class="checklist-modal" id="planEntryModal">
        <div class="checklist-modal-content" style="max-width: 420px;">
            <div class="checklist-modal-header">
                <div class="checklist-modal-header-top">
                    <h3><i class="fas fa-calendar-plus"></i> <span id="planEntryTitle">Add meal</span></h3>
                    <button class="checklist-modal-close" onclick="closeModal('planEntryModal')"><i class="fas fa-times"></i></button>
                </div>
            </div>
            <div class="checklist-modal-body" style="padding: 1.5rem;">
                <input type="hidden" id="planEntryDate">
                <input type="hidden" id="planEntryType">
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label class="form-label">Meal</label>
                    <select class="form-input" id="planEntryMealSelect"></select>
                </div>
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label class="form-label">Servings planned</label>
                    <input type="number" class="form-input" id="planEntryServings" min="1" value="1">
                </div>
                <div style="display:flex; justify-content:flex-end; gap:0.5rem;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('planEntryModal')">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="savePlanEntry()"><i class="fas fa-save"></i> Add to plan</button>
                </div>
            </div>
        </div>
    </div>

    <div id="toast" class="toast">
        <div class="toast-content">
            <i class="fas fa-check-circle toast-icon"></i>
            <span id="toastMessage">Saved</span>
        </div>
    </div>

    <script>
        const API_URL = '../../../Models/php/grocery_meal_api.php';

        let allProducts = [];
        let allMeals = [];
        let allStores = [];
        let currentProductId = null;
        let weekStart = mondayOf(new Date());

        document.addEventListener('DOMContentLoaded', function () {
            setupSidebar();
            switchTab('overview', document.querySelector('[data-tab="overview"]'));
            loadStores().then(() => {
                loadProducts();
                loadMeals().then(loadOverview);
                loadPlanner();
                loadShoppingList();
            });
            loadHealthProfile();
        });

        function setupSidebar() {
            const sidebar = document.getElementById('sidebar');
            const toggleButton = document.getElementById('toggleBtn');
            const mainContent = document.querySelector('.main-content');
            const mobileToggle = document.getElementById('mobileToggle');
            toggleButton.addEventListener('click', () => {
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('collapsed');
            });
            mobileToggle.addEventListener('click', () => sidebar.classList.toggle('collapsed'));
        }
        function toggleSidebar() { document.getElementById('sidebar').classList.toggle('open'); }

        // ============================================================
        // TABS
        // ============================================================
        const tabMeta = {
            overview: { title: 'Overview', subtitle: 'This week at a glance', actions: '' },
            meals: {
                title: 'Meals', subtitle: 'Your meal library',
                actions: `<button class="btn btn-primary" onclick="openMealModal()"><i class="fas fa-plus"></i> Add Meal</button>`
            },
            products: {
                title: 'Products & Prices', subtitle: 'Track prices across HEB, Sprouts, and Costco',
                actions: `<button class="btn btn-primary" onclick="openProductModal()"><i class="fas fa-plus"></i> Add Product</button>`
            },
            planner: {
                title: 'Meal Plan', subtitle: 'Assign meals to each day of the week',
                actions: `<button class="btn btn-primary" onclick="generateWeek()"><i class="fas fa-wand-magic-sparkles"></i> Auto-Generate Week</button>`
            },
            shopping: { title: 'Shopping List', subtitle: 'Aggregated ingredients with cheapest store per item', actions: '' },
            health: { title: 'Health Profile', subtitle: 'Targets and restrictions that tailor your meal plan', actions: '' }
        };

        function switchTab(tab, el) {
            document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
            document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
            const panel = document.getElementById('tab-' + tab);
            if (panel) panel.classList.add('active');
            if (el) el.classList.add('active');
            const meta = tabMeta[tab] || {};
            document.getElementById('tabTitle').textContent = meta.title || '';
            document.getElementById('tabSubtitle').textContent = meta.subtitle || '';
            document.getElementById('topBarActions').innerHTML = meta.actions || '';
        }

        // ============================================================
        // STORES
        // ============================================================
        async function loadStores() {
            try {
                const res = await fetch(`${API_URL}?action=get_stores`);
                const data = await res.json();
                if (data.success) allStores = data.stores || [];
            } catch (e) { console.error(e); }
        }

        // ============================================================
        // OVERVIEW
        // ============================================================
        async function loadOverview() {
            document.getElementById('overviewStats').innerHTML = `
                <div class="gm-stat-card"><i class="fas fa-utensils"></i><div><strong>${allMeals.length}</strong><span>Meals in library</span></div></div>
                <div class="gm-stat-card"><i class="fas fa-carrot"></i><div><strong>${allProducts.length}</strong><span>Products tracked</span></div></div>
            `;
            try {
                const res = await fetch(`${API_URL}?action=get_meal_plan&start_date=${fmtDate(weekStart)}&end_date=${fmtDate(addDays(weekStart, 6))}`);
                const data = await res.json();
                const container = document.getElementById('overviewPlan');
                if (data.success && data.plan.length) {
                    container.innerHTML = renderPlanSummary(data.plan);
                } else {
                    container.innerHTML = `<p class="gm-hint">No meals planned this week yet. Head to <strong>Meal Plan</strong> to add some.</p>`;
                }
            } catch (e) { console.error(e); }
        }

        function renderPlanSummary(plan) {
            const byDate = {};
            plan.forEach(p => { (byDate[p.plan_date] = byDate[p.plan_date] || []).push(p); });
            return Object.keys(byDate).sort().map(date => `
                <div class="gm-plan-summary-row">
                    <strong>${formatDayLabel(date)}</strong>
                    <span>${byDate[date].map(p => `${capitalize(p.meal_type)}: ${escapeHtml(p.meal_name)}`).join(' &middot; ')}</span>
                </div>
            `).join('');
        }

        // ============================================================
        // MEALS
        // ============================================================
        async function loadMeals() {
            const type = document.getElementById('mealTypeFilter').value;
            try {
                const res = await fetch(`${API_URL}?action=get_meals${type ? '&meal_type=' + type : ''}`);
                const data = await res.json();
                if (data.success) {
                    allMeals = data.meals || [];
                    renderMeals();
                } else {
                    showToast(data.message || 'Error loading meals', 'error');
                }
            } catch (e) { console.error(e); showToast('Network error loading meals', 'error'); }
        }

        function renderMeals() {
            const container = document.getElementById('mealsContainer');
            if (!allMeals.length) {
                container.innerHTML = `<div class="gm-empty-state"><i class="fas fa-utensils"></i><h3>No meals yet</h3><p>Add your first meal to start building the library.</p></div>`;
                return;
            }
            container.innerHTML = allMeals.map(m => `
                <div class="gm-card">
                    <div class="gm-card-header">
                        <span class="gm-type-badge">${capitalize(m.meal_type)}</span>
                        <div class="gm-card-actions">
                            <button class="btn btn-xs btn-secondary" onclick="openMealModal(${m.meal_id})"><i class="fas fa-pen"></i></button>
                            <button class="btn btn-xs btn-secondary" onclick="deleteMeal(${m.meal_id})"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                    <div class="gm-card-name">${escapeHtml(m.name)}</div>
                    <div class="gm-card-macros">
                        ${m.calories ? `<span><i class="fas fa-fire"></i> ${m.calories} cal</span>` : ''}
                        ${m.protein_g ? `<span>P ${m.protein_g}g</span>` : ''}
                        ${m.carbs_g ? `<span>C ${m.carbs_g}g</span>` : ''}
                        ${m.fat_g ? `<span>F ${m.fat_g}g</span>` : ''}
                    </div>
                    ${m.tags ? `<div class="gm-tags">${m.tags.split(',').map(t => `<span class="gm-tag">${escapeHtml(t.trim())}</span>`).join('')}</div>` : ''}
                </div>
            `).join('');
        }

        async function openMealModal(mealId) {
            document.getElementById('mealForm').reset();
            document.getElementById('editMealId').value = '';
            document.getElementById('ingredientRows').innerHTML = '';
            document.getElementById('mealModalTitle').textContent = mealId ? 'Edit Meal' : 'Add Meal';

            if (mealId) {
                const res = await fetch(`${API_URL}?action=get_meal&meal_id=${mealId}`);
                const data = await res.json();
                if (!data.success) { showToast(data.message || 'Meal not found', 'error'); return; }
                const meal = data.meal;
                document.getElementById('editMealId').value = meal.meal_id;
                document.getElementById('mealName').value = meal.name;
                document.getElementById('mealType').value = meal.meal_type;
                document.getElementById('mealServings').value = meal.servings;
                document.getElementById('mealCalories').value = meal.calories || '';
                document.getElementById('mealProtein').value = meal.protein_g || '';
                document.getElementById('mealCarbs').value = meal.carbs_g || '';
                document.getElementById('mealFat').value = meal.fat_g || '';
                document.getElementById('mealTags').value = meal.tags || '';
                document.getElementById('mealInstructions').value = meal.instructions || '';
                (meal.ingredients || []).forEach(ing => addIngredientRow(ing.product_id, ing.quantity, ing.unit));
            } else {
                addIngredientRow();
            }
            openModal('mealModal');
        }

        function addIngredientRow(productId, quantity, unit) {
            const row = document.createElement('div');
            row.className = 'gm-ingredient-row';
            const options = allProducts.map(p => `<option value="${p.product_id}" ${productId == p.product_id ? 'selected' : ''}>${escapeHtml(p.name)}</option>`).join('');
            row.innerHTML = `
                <select class="gm-ingredient-product">${options || '<option value="">No products yet — add some first</option>'}</select>
                <input type="number" class="gm-ingredient-qty" step="0.01" min="0" value="${quantity || 1}" placeholder="Qty">
                <input type="text" class="gm-ingredient-unit" value="${escapeHtml(unit || '')}" placeholder="unit">
                <button type="button" class="btn btn-xs btn-secondary" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
            `;
            document.getElementById('ingredientRows').appendChild(row);
        }

        async function saveMeal() {
            const name = document.getElementById('mealName').value.trim();
            if (!name) { showToast('Meal name is required', 'error'); return; }

            const ingredients = Array.from(document.querySelectorAll('#ingredientRows .gm-ingredient-row')).map(row => ({
                product_id: parseInt(row.querySelector('.gm-ingredient-product').value) || 0,
                quantity: parseFloat(row.querySelector('.gm-ingredient-qty').value) || 1,
                unit: row.querySelector('.gm-ingredient-unit').value.trim()
            })).filter(i => i.product_id);

            const payload = {
                action: 'save_meal',
                meal_id: document.getElementById('editMealId').value || undefined,
                name,
                meal_type: document.getElementById('mealType').value,
                servings: document.getElementById('mealServings').value,
                calories: document.getElementById('mealCalories').value,
                protein_g: document.getElementById('mealProtein').value,
                carbs_g: document.getElementById('mealCarbs').value,
                fat_g: document.getElementById('mealFat').value,
                tags: document.getElementById('mealTags').value.trim(),
                instructions: document.getElementById('mealInstructions').value.trim(),
                ingredients
            };

            try {
                const res = await fetch(API_URL, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
                const data = await res.json();
                if (data.success) {
                    showToast('Meal saved');
                    closeModal('mealModal');
                    loadMeals();
                } else {
                    showToast(data.message || 'Error saving meal', 'error');
                }
            } catch (e) { console.error(e); showToast('Network error saving meal', 'error'); }
        }

        async function deleteMeal(id) {
            if (!confirm('Delete this meal? This also removes it from any meal plan entries.')) return;
            try {
                const res = await fetch(API_URL, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'delete_meal', meal_id: id }) });
                const data = await res.json();
                if (data.success) { showToast('Meal deleted'); loadMeals(); } else { showToast(data.message || 'Error deleting meal', 'error'); }
            } catch (e) { console.error(e); showToast('Network error deleting meal', 'error'); }
        }

        // ============================================================
        // PRODUCTS & PRICES
        // ============================================================
        async function loadProducts() {
            try {
                const [prodRes, priceRes] = await Promise.all([
                    fetch(`${API_URL}?action=get_products`),
                    fetch(`${API_URL}?action=get_prices`)
                ]);
                const prodData = await prodRes.json();
                const priceData = await priceRes.json();
                if (prodData.success) allProducts = prodData.products || [];

                const cheapest = {};
                if (priceData.success) {
                    (priceData.prices || []).forEach(row => {
                        if (!cheapest[row.product_id] || parseFloat(row.price) < parseFloat(cheapest[row.product_id].price)) cheapest[row.product_id] = row;
                    });
                }
                renderProducts(cheapest);
            } catch (e) { console.error(e); showToast('Network error loading products', 'error'); }
        }

        function renderProducts(cheapest) {
            const container = document.getElementById('productsContainer');
            if (!allProducts.length) {
                container.innerHTML = `<div class="gm-empty-state"><i class="fas fa-carrot"></i><h3>No products yet</h3><p>Add products to start tracking prices and building meals.</p></div>`;
                return;
            }
            container.innerHTML = `
                <table class="gm-table">
                    <thead><tr><th>Product</th><th>Category</th><th>Unit</th><th>Best known price</th><th></th></tr></thead>
                    <tbody>
                        ${allProducts.map(p => {
                            const c = cheapest[p.product_id];
                            return `
                            <tr>
                                <td>${escapeHtml(p.name)}</td>
                                <td>${escapeHtml(p.category || '—')}</td>
                                <td>${escapeHtml(p.unit)}</td>
                                <td>${c ? `$${Number(c.price).toFixed(2)} <span class="gm-hint-inline">at ${escapeHtml(c.store_name)}${c.needs_review ? ' (unverified)' : ''}</span>` : '<span class="gm-hint-inline">No prices yet</span>'}</td>
                                <td class="gm-table-actions">
                                    <button class="btn btn-xs btn-secondary" onclick="openPriceModal(${p.product_id})"><i class="fas fa-tags"></i> Prices</button>
                                    <button class="btn btn-xs btn-secondary" onclick="openProductModal(${p.product_id})"><i class="fas fa-pen"></i></button>
                                    <button class="btn btn-xs btn-secondary" onclick="deleteProduct(${p.product_id})"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>`;
                        }).join('')}
                    </tbody>
                </table>
            `;
        }

        function openProductModal(productId) {
            document.getElementById('productForm').reset();
            document.getElementById('editProductId').value = '';
            document.getElementById('productModalTitle').textContent = productId ? 'Edit Product' : 'Add Product';
            if (productId) {
                const p = allProducts.find(x => x.product_id == productId);
                if (p) {
                    document.getElementById('editProductId').value = p.product_id;
                    document.getElementById('productName').value = p.name;
                    document.getElementById('productCategory').value = p.category || '';
                    document.getElementById('productUnit').value = p.unit;
                    document.getElementById('productNotes').value = p.notes || '';
                }
            } else {
                document.getElementById('productUnit').value = 'each';
            }
            openModal('productModal');
        }

        async function saveProduct() {
            const name = document.getElementById('productName').value.trim();
            if (!name) { showToast('Product name is required', 'error'); return; }
            const payload = {
                action: 'save_product',
                product_id: document.getElementById('editProductId').value || undefined,
                name,
                category: document.getElementById('productCategory').value.trim(),
                unit: document.getElementById('productUnit').value.trim() || 'each',
                notes: document.getElementById('productNotes').value.trim()
            };
            try {
                const res = await fetch(API_URL, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
                const data = await res.json();
                if (data.success) { showToast('Product saved'); closeModal('productModal'); loadProducts(); }
                else showToast(data.message || 'Error saving product', 'error');
            } catch (e) { console.error(e); showToast('Network error saving product', 'error'); }
        }

        async function deleteProduct(id) {
            if (!confirm('Delete this product? This also removes its prices and any meal ingredient links.')) return;
            try {
                const res = await fetch(API_URL, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'delete_product', product_id: id }) });
                const data = await res.json();
                if (data.success) { showToast('Product deleted'); loadProducts(); } else showToast(data.message || 'Error deleting product', 'error');
            } catch (e) { console.error(e); showToast('Network error deleting product', 'error'); }
        }

        async function openPriceModal(productId) {
            currentProductId = productId;
            const p = allProducts.find(x => x.product_id == productId);
            document.getElementById('priceModalTitle').textContent = p ? `Prices — ${p.name}` : 'Prices';
            document.getElementById('priceDate').value = fmtDate(new Date());

            document.getElementById('priceStoreSelect').innerHTML = allStores.map(s => `<option value="${s.store_id}">${escapeHtml(s.name)}</option>`).join('');
            document.getElementById('scrapeControls').innerHTML = allStores.map(s =>
                `<button class="btn btn-secondary btn-sm" onclick="scrapePriceFor(${s.store_id}, '${escapeHtml(s.name)}')"><i class="fas fa-magnifying-glass"></i> Check ${escapeHtml(s.name)}</button>`
            ).join('');
            document.getElementById('scrapeResult').innerHTML = '';

            await refreshPriceModal();
            openModal('priceModal');
        }

        async function refreshPriceModal() {
            try {
                const res = await fetch(`${API_URL}?action=get_prices&product_id=${currentProductId}`);
                const data = await res.json();
                if (!data.success) return;

                document.getElementById('priceLatest').innerHTML = data.latest.length
                    ? `<div class="gm-latest-grid">${data.latest.map(r => `
                        <div class="gm-latest-card">
                            <strong>${escapeHtml(r.store_name)}</strong>
                            <span>$${Number(r.price).toFixed(2)}</span>
                            <small>${r.observed_date}${r.needs_review ? ' &middot; unverified' : ''}</small>
                        </div>`).join('')}</div>`
                    : '<p class="gm-hint">No prices recorded yet.</p>';

                document.getElementById('priceHistory').innerHTML = data.history.length
                    ? `<table class="gm-table"><thead><tr><th>Store</th><th>Price</th><th>Date</th><th>Source</th><th></th></tr></thead><tbody>
                        ${data.history.map(r => `
                        <tr>
                            <td>${escapeHtml(r.store_name)}</td>
                            <td>$${Number(r.price).toFixed(2)}</td>
                            <td>${r.observed_date}</td>
                            <td>${r.source}${r.needs_review ? ' (unverified)' : ''}</td>
                            <td><button class="btn btn-xs btn-secondary" onclick="deletePriceEntry(${r.price_id})"><i class="fas fa-trash"></i></button></td>
                        </tr>`).join('')}
                    </tbody></table>`
                    : '<p class="gm-hint">No history yet.</p>';
            } catch (e) { console.error(e); }
        }

        async function saveManualPrice() {
            const storeId = document.getElementById('priceStoreSelect').value;
            const price = document.getElementById('priceValue').value;
            const date = document.getElementById('priceDate').value;
            if (!price || isNaN(price)) { showToast('Enter a valid price', 'error'); return; }
            try {
                const res = await fetch(API_URL, {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'save_price', product_id: currentProductId, store_id: storeId, price, observed_date: date })
                });
                const data = await res.json();
                if (data.success) {
                    showToast('Price added');
                    document.getElementById('priceValue').value = '';
                    refreshPriceModal();
                    loadProducts();
                } else showToast(data.message || 'Error adding price', 'error');
            } catch (e) { console.error(e); showToast('Network error adding price', 'error'); }
        }

        async function deletePriceEntry(priceId) {
            if (!confirm('Delete this price entry?')) return;
            try {
                const res = await fetch(API_URL, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'delete_price', price_id: priceId }) });
                const data = await res.json();
                if (data.success) { refreshPriceModal(); loadProducts(); } else showToast(data.message || 'Error deleting price', 'error');
            } catch (e) { console.error(e); }
        }

        async function scrapePriceFor(storeId, storeName) {
            const p = allProducts.find(x => x.product_id == currentProductId);
            const resultBox = document.getElementById('scrapeResult');
            resultBox.innerHTML = `<p class="gm-hint">Checking ${escapeHtml(storeName)}...</p>`;
            try {
                const res = await fetch(API_URL, {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'scrape_price', store_id: storeId, product_name: p ? p.name : '' })
                });
                const data = await res.json();
                if (data.success) {
                    resultBox.innerHTML = `
                        <div class="gm-scrape-result">
                            <p><strong>${escapeHtml(data.store_name)}:</strong> $${Number(data.price).toFixed(2)} — matched "${escapeHtml(data.matched_name)}"</p>
                            <p class="gm-hint-inline">Unverified — double check against the store before trusting it.</p>
                            <button class="btn btn-sm btn-primary" onclick="confirmScrapedPrice(${data.store_id}, ${data.price})">
                                <i class="fas fa-check"></i> Looks right, save it
                            </button>
                        </div>`;
                } else {
                    resultBox.innerHTML = `<p class="gm-hint">${escapeHtml(data.message || 'No match found')}</p>`;
                }
            } catch (e) { console.error(e); resultBox.innerHTML = `<p class="gm-hint">Network error checking price.</p>`; }
        }

        async function confirmScrapedPrice(storeId, price) {
            try {
                const res = await fetch(API_URL, {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'save_price', product_id: currentProductId, store_id: storeId, price, observed_date: fmtDate(new Date()) })
                });
                const data = await res.json();
                if (data.success) { showToast('Price saved'); document.getElementById('scrapeResult').innerHTML = ''; refreshPriceModal(); loadProducts(); }
                else showToast(data.message || 'Error saving price', 'error');
            } catch (e) { console.error(e); showToast('Network error saving price', 'error'); }
        }

        // ============================================================
        // MEAL PLAN (PLANNER)
        // ============================================================
        function changeWeek(deltaDays) {
            weekStart = addDays(weekStart, deltaDays);
            loadPlanner();
            loadShoppingList();
            loadOverview();
        }

        async function loadPlanner() {
            const start = fmtDate(weekStart), end = fmtDate(addDays(weekStart, 6));
            document.getElementById('plannerWeekLabel').textContent = `${start} to ${end}`;
            try {
                const res = await fetch(`${API_URL}?action=get_meal_plan&start_date=${start}&end_date=${end}`);
                const data = await res.json();
                if (data.success) renderPlanner(data.plan);
                else showToast(data.message || 'Error loading plan', 'error');
            } catch (e) { console.error(e); showToast('Network error loading plan', 'error'); }
        }

        function renderPlanner(plan) {
            const slots = ['breakfast', 'lunch', 'dinner'];
            const byKey = {};
            plan.forEach(p => { byKey[p.plan_date + '|' + p.meal_type] = p; });

            let html = '<div class="gm-planner-header"><div></div>' + slots.map(s => `<div>${capitalize(s)}</div>`).join('') + '</div>';
            for (let i = 0; i < 7; i++) {
                const date = addDays(weekStart, i);
                const dateStr = fmtDate(date);
                html += `<div class="gm-planner-row"><div class="gm-planner-day">${formatDayLabel(dateStr)}</div>`;
                slots.forEach(type => {
                    const entry = byKey[dateStr + '|' + type];
                    html += `<div class="gm-planner-cell">`;
                    if (entry) {
                        html += `<div class="gm-planner-entry">
                            <span>${escapeHtml(entry.meal_name)}</span>
                            <button class="btn btn-xs btn-secondary" onclick="deletePlanEntry(${entry.plan_id})"><i class="fas fa-times"></i></button>
                        </div>`;
                    } else {
                        html += `<button class="gm-planner-add" onclick="openPlanEntryModal('${dateStr}', '${type}')"><i class="fas fa-plus"></i></button>`;
                    }
                    html += `</div>`;
                });
                html += `</div>`;
            }
            document.getElementById('plannerGrid').innerHTML = html;
        }

        function openPlanEntryModal(dateStr, type) {
            document.getElementById('planEntryDate').value = dateStr;
            document.getElementById('planEntryType').value = type;
            document.getElementById('planEntryTitle').textContent = `Add ${capitalize(type)} — ${formatDayLabel(dateStr)}`;
            document.getElementById('planEntryServings').value = 1;
            const options = allMeals.filter(m => m.meal_type === type);
            document.getElementById('planEntryMealSelect').innerHTML = options.length
                ? options.map(m => `<option value="${m.meal_id}">${escapeHtml(m.name)}</option>`).join('')
                : `<option value="">No ${type} meals in your library yet</option>`;
            openModal('planEntryModal');
        }

        async function savePlanEntry() {
            const mealId = document.getElementById('planEntryMealSelect').value;
            if (!mealId) { showToast('Add a meal of this type first', 'error'); return; }
            const payload = {
                action: 'save_meal_plan_entry',
                plan_date: document.getElementById('planEntryDate').value,
                meal_type: document.getElementById('planEntryType').value,
                meal_id: mealId,
                servings_planned: document.getElementById('planEntryServings').value
            };
            try {
                const res = await fetch(API_URL, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
                const data = await res.json();
                if (data.success) { showToast('Added to plan'); closeModal('planEntryModal'); loadPlanner(); loadShoppingList(); loadOverview(); }
                else showToast(data.message || 'Error adding to plan', 'error');
            } catch (e) { console.error(e); showToast('Network error updating plan', 'error'); }
        }

        async function deletePlanEntry(planId) {
            if (!confirm('Remove this meal from the plan?')) return;
            try {
                const res = await fetch(API_URL, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'delete_meal_plan_entry', plan_id: planId }) });
                const data = await res.json();
                if (data.success) { loadPlanner(); loadShoppingList(); loadOverview(); } else showToast(data.message || 'Error removing entry', 'error');
            } catch (e) { console.error(e); }
        }

        async function generateWeek() {
            const start = fmtDate(weekStart), end = fmtDate(addDays(weekStart, 6));
            try {
                const res = await fetch(API_URL, {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'generate_meal_plan', start_date: start, end_date: end })
                });
                const data = await res.json();
                if (data.success) {
                    showToast(`Filled ${data.entries_created} empty slot(s)`);
                    loadPlanner(); loadShoppingList(); loadOverview();
                } else showToast(data.message || 'Error generating plan', 'error');
            } catch (e) { console.error(e); showToast('Network error generating plan', 'error'); }
        }

        // ============================================================
        // SHOPPING LIST
        // ============================================================
        async function loadShoppingList() {
            const start = fmtDate(weekStart), end = fmtDate(addDays(weekStart, 6));
            document.getElementById('shoppingWeekLabel').textContent = `${start} to ${end}`;
            try {
                const res = await fetch(`${API_URL}?action=get_shopping_list&start_date=${start}&end_date=${end}`);
                const data = await res.json();
                if (data.success) renderShoppingList(data);
                else showToast(data.message || 'Error loading shopping list', 'error');
            } catch (e) { console.error(e); showToast('Network error loading shopping list', 'error'); }
        }

        function renderShoppingList(data) {
            const container = document.getElementById('shoppingContainer');
            if (!data.items.length) {
                container.innerHTML = `<div class="gm-empty-state"><i class="fas fa-cart-shopping"></i><h3>Nothing planned</h3><p>Add meals to this week's plan to generate a shopping list.</p></div>`;
                return;
            }
            container.innerHTML = `
                <table class="gm-table">
                    <thead><tr><th>Item</th><th>Qty</th><th>Best store</th><th>Est. cost</th></tr></thead>
                    <tbody>
                        ${data.items.map(i => `
                        <tr>
                            <td>${escapeHtml(i.product_name)}</td>
                            <td>${Number(i.total_quantity).toFixed(2)} ${escapeHtml(i.unit)}</td>
                            <td>${i.best_store ? escapeHtml(i.best_store) : '<span class="gm-hint-inline">No price yet</span>'}</td>
                            <td>${i.estimated_cost !== null ? '$' + Number(i.estimated_cost).toFixed(2) : '—'}</td>
                        </tr>`).join('')}
                    </tbody>
                    <tfoot><tr><td colspan="3"><strong>Estimated total</strong></td><td><strong>$${Number(data.estimated_total).toFixed(2)}</strong></td></tr></tfoot>
                </table>
            `;
        }

        // ============================================================
        // HEALTH PROFILE
        // ============================================================
        async function loadHealthProfile() {
            try {
                const res = await fetch(`${API_URL}?action=get_health_profile`);
                const data = await res.json();
                if (data.success && data.profile) {
                    document.getElementById('hpCalories').value = data.profile.calorie_target || '';
                    document.getElementById('hpProtein').value = data.profile.protein_target_g || '';
                    document.getElementById('hpCarbs').value = data.profile.carbs_target_g || '';
                    document.getElementById('hpFat').value = data.profile.fat_target_g || '';
                    document.getElementById('hpAllergies').value = data.profile.allergies || '';
                    document.getElementById('hpRestrictions').value = data.profile.restrictions || '';
                    document.getElementById('hpNotes').value = data.profile.notes || '';
                }
            } catch (e) { console.error(e); }
        }

        async function saveHealthProfile() {
            const payload = {
                action: 'save_health_profile',
                calorie_target: document.getElementById('hpCalories').value,
                protein_target_g: document.getElementById('hpProtein').value,
                carbs_target_g: document.getElementById('hpCarbs').value,
                fat_target_g: document.getElementById('hpFat').value,
                allergies: document.getElementById('hpAllergies').value.trim(),
                restrictions: document.getElementById('hpRestrictions').value.trim(),
                notes: document.getElementById('hpNotes').value.trim()
            };
            try {
                const res = await fetch(API_URL, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
                const data = await res.json();
                if (data.success) showToast('Health profile saved'); else showToast(data.message || 'Error saving profile', 'error');
            } catch (e) { console.error(e); showToast('Network error saving profile', 'error'); }
        }

        // ============================================================
        // HELPERS
        // ============================================================
        function openModal(id) { document.getElementById(id).classList.add('active'); }
        function closeModal(id) { document.getElementById(id).classList.remove('active'); }

        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const toastMessage = document.getElementById('toastMessage');
            const toastIcon = document.querySelector('.toast-icon');
            toastMessage.textContent = message;
            if (type === 'error') {
                toastIcon.className = 'fas fa-exclamation-circle toast-icon';
                toast.style.borderLeftColor = 'var(--danger-color)';
                toastIcon.style.color = 'var(--danger-color)';
            } else {
                toastIcon.className = 'fas fa-check-circle toast-icon';
                toast.style.borderLeftColor = 'var(--success-color)';
                toastIcon.style.color = 'var(--success-color)';
            }
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 3500);
        }

        function escapeHtml(text) {
            if (text === null || text === undefined) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function capitalize(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : ''; }

        function mondayOf(date) {
            const d = new Date(date);
            const day = d.getDay();
            const diff = (day === 0 ? -6 : 1) - day;
            d.setDate(d.getDate() + diff);
            d.setHours(0, 0, 0, 0);
            return d;
        }

        function addDays(date, days) {
            const d = new Date(date);
            d.setDate(d.getDate() + days);
            return d;
        }

        function fmtDate(date) {
            const y = date.getFullYear(), m = String(date.getMonth() + 1).padStart(2, '0'), d = String(date.getDate()).padStart(2, '0');
            return `${y}-${m}-${d}`;
        }

        function formatDayLabel(dateStr) {
            const d = new Date(dateStr + 'T00:00:00');
            return d.toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' });
        }

        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('checklist-modal')) e.target.classList.remove('active');
        });
    </script>
</body>
</html>
