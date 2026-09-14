# Design system

**Status: not started.** The theme uses a placeholder three-color palette and
default typography.

## Principles

- The theme `wordpress/themes/cacdemo/` owns the Christlikeness design:
  typography, color, spacing, visual language, templates, header/footer
  appearance, responsive presentation.
- Design tokens live in `theme.json`, not hardcoded CSS.
- Style core blocks through theme.json `styles.blocks` and block style variations
  so ordinary Gutenberg content matches the design.
- Reusable sections are block patterns (synced patterns where content should stay in step).
- Header/footer are template parts that keep the native Site Logo, Site Title and
  Navigation blocks editable by administrators.

## Richer components

Use core blocks and patterns first. For components core lacks (accordions, tabs,
advanced galleries, sliders/carousels, timelines, icon+text, advanced layout
controls), adopt **one** reputable free block library rather than building blocks
ourselves or stacking overlapping suites. Evaluate it with the plugin checklist in
[ARCHITECTURE.md](ARCHITECTURE.md), then style its output from the theme — never
patch the plugin.

A plugin provides capability; the theme provides Christlikeness design.

Deferred: final visual identity, homepage, header/footer styling, block library choice.
