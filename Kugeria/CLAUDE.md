# Kūgeria Code Guidelines

## Before editing

- Read the current file first. Do not assume a previous cleanup is still present; these static pages may be reformatted or regenerated.
- Keep changes scoped to the page and component being changed.

## HTML and CSS rules

- Do not add `style="..."` attributes, including inside JavaScript-generated HTML strings. Add a descriptive class and define the rule in the page stylesheet instead.
- Do not add empty CSS rules such as `.bio-panel{}`. Remove unused rules or give them a real declaration.
- For `backdrop-filter`, always include the Safari fallback immediately before it: `-webkit-backdrop-filter: ...; backdrop-filter: ...;`.
- Avoid adding `meta[name="theme-color"]`; the project diagnostics flag it as unsupported in Firefox and Opera. Keep the browser-independent visual color in CSS instead.
- Preserve existing visual values when moving inline styles into classes. Prefer component-specific names such as `.consent-copy`, `.project-image`, or `.mt-sub` over vague utility names.

## Accessibility

- Every `<button>` needs visible text or an accessible name (`aria-label` and, where useful, `title`). This includes icon-only buttons containing SVGs.
- Use `type="button"` for buttons that are not form submissions.
- Keep form labels associated with their controls using `for`/`id`.

## Validation before finishing

- Run the editor diagnostics on every changed HTML file.
- Search changed files for `style=` and inspect every match, including JavaScript strings.
- Search changed files for `theme-color`, `backdrop-filter`, and empty CSS rules.
- Confirm that generated HTML strings use classes rather than inline styles.
- Report any unrelated existing diagnostics separately; do not hide them with broad refactors.
