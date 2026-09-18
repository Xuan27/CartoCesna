# Vanessa's Recipe Book — web app

A small PHP/MySQL/vanilla-JS app to search, edit, and add recipes for
Vanessa's family recipe catalog, with a "Download Full Book" button that
regenerates the entire printable cookbook (the same page/card template as
`../Recipes/Vanessa's Recipe Book.pdf`) live from whatever is in the
database — so the printed book is never the only editable copy.

**URL:** `http://localhost/CartoCesna/Projects/Personal/00_005/RecipeBook/index.php`

## Setup (fresh checkout)

1. Load the schema into the existing `cartocesna` database (this app's
   `categories` and `recipes` tables live alongside the rest of the app's
   tables, using the same DB connection as everything else — see
   `config/db.php`):
   ```
   mysql -u root -p cartocesna < schema.sql
   ```

2. Make sure `uploads/` is writable by the web server user (it has no group
   in common with the file owner on this host, so it's `chmod 777`):
   ```
   chmod 777 uploads
   ```

3. PDF export shells out to `python3 -m weasyprint`. WeasyPrint here is only
   installed in one user's local Python site-packages, not system-wide, so
   `api/export_pdf.php` sets `PYTHONPATH` explicitly when invoking it. If
   exports start failing with `ModuleNotFoundError: No module named
   'weasyprint'`, the web server user can no longer traverse into that
   user's `~/.local/lib/pythonX.Y/site-packages` directory — check that
   `~/.local` and `~/.local/lib` still have the execute (`x`) bit for
   "other" (`chmod o+x`), and that the `PYTHONPATH` in `export_pdf.php`
   still points at the right Python version's site-packages.

## Structure

- `index.php` / `assets/` — the single-page front end.
- `api/recipes_api.php` — action-based JSON API (categories, list, get,
  create, update, delete, toggle_favorite).
- `api/export_pdf.php` — rebuilds the book (or one category, or one recipe
  via `?id=`) from the database and streams a PDF.
- `includes/pdf_render.php` — PHP port of the print template shared by the
  full-book export and the single-recipe export.
- `config/db.php` — connection to `cartocesna` (same database as the rest of the app).
