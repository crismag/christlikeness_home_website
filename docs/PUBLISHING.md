# Publishing

**Status: native WordPress only.** Editors use the standard block editor with
WordPress's own draft, pending, scheduled and published statuses, revisions,
featured images and the Media Library. `scripts/test.sh --interop` verifies that
drafts stay private, publishing renders through the theme, revisions are recorded
and scheduling works.

Deferred: a simplified publisher interface and any workflow extensions. Evaluate
core first (roles, statuses, revisions, block locking, patterns), then established
free workflow plugins, and custom code last. Whatever is chosen must extend native
statuses, roles/capabilities and revisions rather than replace them.
