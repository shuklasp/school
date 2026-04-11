# 05. Routing and Views

Routing in SPP is simple and flexible, allowing you to define static and dynamic pages with AJAX fragment loading.

---

## Defining Routes in `pages.yml`

The `etc/pages.yml` file is the central source of truth for routing. A typical page definition looks like this:

```yaml
pages:
  - name: home
    url: /home.php
  - name: about
    url: /about.php
  - name: profile
    url: /user_profile.php
```

### Static vs. Dynamic Paths

If a request URL (e.g., `?q=profile/123/edit`) matches a registered page name (`profile`), the remaining parts of the URL are automatically passed as positional parameters.

```php
$pageData = \SPPMod\SPPView\Pages::getPage();
$userId = $pageData['params'][0]; // This retrieves "123"
```

---

## The `ViewPage` Rendering Engine

The `ViewPage` class manages the HTML output of your application. You can add global CSS/JS assets, set page titles, and more.

### 1. Show the Page
```php
\SPPMod\SPPView\ViewPage::showPage();
```
This is the core rendering method. It handles routing and includes the appropriate PHP file from your `src/` directory.

### 2. Include Assets Dynamically
```php
\SPPMod\SPPView\ViewPage::addCssIncludeFile('res/custom.css');
\SPPMod\SPPView\ViewPage::addJsIncludeFile('res/custom.js');
```

---

## SPA "Drop and Play"

SPP can automatically "augment" your static PHP/HTML pages to behave like a Single Page Application (SPA). When enabled, the framework:
1.  **Intercepts** link clicks (`<a>` tags) and form submissions.
2.  **Fetches** only the necessary HTML content (fragments) using AJAX.
3.  **Updates** the page content without a full reload.

To enable this globally, set the following in your `sppview` configuration:
```yaml
auto_page_augmentation: true
auto_js_injection: true
```

---

## Passing Data to Views

Use the `SPPGlobal` class to store and retrieve data across your application's lifecycle:

```php
\SPP\SPPGlobal::set('user_name', 'John');
//... in your view file:
echo \SPP\SPPGlobal::get('user_name');
```

---

[**Next: Forms & Validation**](06_forms_and_validation.md)
