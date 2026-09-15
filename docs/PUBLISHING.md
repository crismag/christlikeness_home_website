# Publishing

**Status: native WordPress only.** Editors use the standard block editor with
WordPress's own draft, pending, scheduled and published statuses, revisions,
featured images and the Media Library. `scripts/test.sh --interop` verifies that
drafts stay private, publishing renders through the theme, revisions are recorded
and scheduling works.

**Contextual publishing (2026-09-16):** section-scoped Contributors and Publishers, contextual actions on the website that open the
core editor, and Users → Page contributors. Design, decisions and behaviour: [CONTRIBUTOR-PUBLISHING.md](CONTRIBUTOR-PUBLISHING.md).

Still deferred: review queues and other workflow extensions. Evaluate
core first (roles, statuses, revisions, block locking, patterns), then established
free workflow plugins, and custom code last. Whatever is chosen must extend native
statuses, roles/capabilities and revisions rather than replace them.
