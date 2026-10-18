# Advanced Markdown Standards

This page tests the most common Markdown structures used in ThemisDB docs.

## Emphasis and inline formatting

**strong**, *emphasis*, ***both***, ~~strike~~, and `inline-code`.

A link to [ThemisDB](https://themisdb.com) and a relative link to [about](about.md).

## Lists

### Unordered list

- first item
- second item
  - nested item
  - [x] finished subtask
- [ ] open task

### Ordered list

1. First step
2. Second step
   1. Nested numbered step
   2. Another nested step

## Block quotes and callouts

> Important note: Markdown should be normalized before rendering.

> [!TIP]
> Use semantic blocks instead of raw HTML whenever possible.

## Table

| Area | Status | Notes |
| --- | --- | --- |
| Headings | OK | Standard markdown |
| Lists | OK | Nested supported |
| Tasks | OK | Checkbox syntax works |
| Tables | OK | Real HTML output |

## Code and HTML embeds

```bash
echo "rendered correctly"
```

<div class="note">
  <strong>Inline HTML</strong> is preserved when safe.
</div>

## Footnote-style reference

This sentence references a note.[^1]

[^1]: Footnotes should remain semantically readable after conversion.

## Final paragraph

The content stays readable after conversion, even with mixed formatting, nested list items, and task markers.
