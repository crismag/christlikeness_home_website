# Content model

**Status: not started.** The local baseline has no pages, posts or menus. The
information architecture will be defined after the christlikeness.ca content
harvest and design review.

Each requirement below goes through the capability decision order in
[ARCHITECTURE.md](ARCHITECTURE.md) — core first, then an established free plugin,
custom code in `cacdemo-content` only as a last resort — with a proposal
(native option, free plugin options, recommendation, reason, tradeoffs) before
implementation. Content models never live in the theme.

## Deferred requirements

| Requirement | Evaluate first |
|---|---|
| Site pages / information architecture | Core Pages, Navigation, patterns |
| Sermons | Core (posts + categories/tags, Query Loop, embeds) vs sermon plugins |
| Events | Event/calendar plugins vs core posts + meta |
| Ministries | Core Pages hierarchy or taxonomy |
| People / leaders | Core patterns/pages vs a content-modeling plugin |
| Music | Core audio/embeds, Query Loop |
| Contact form | Free form plugins (replaces Hostinger `[hostinger_contact_form]` / `contact-form-block`) |
| Newsletter subscription | Provider's free plugin/embed (replaces Hostinger Reach block) |
| Simplified publishing UX | Core roles, statuses, revisions, block locking/patterns first |
| YouTube / Google integrations | Core embeds, then established plugins |
