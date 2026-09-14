# Deployment

**Status: not implemented.** `scripts/deploy-staging.sh` and
`scripts/verify-staging.sh` are placeholders that exit with an error. Staging
(`cacdemo.crishub.com`) has not been changed.

## Intended flow

```
local ──► Git ──► staging (cacdemo.crishub.com) ──► verification ──► production (christlikeness.ca)
```

## What a deployment ships

- `wordpress/themes/cacdemo/`
- `wordpress/plugins/cacdemo-content/`
- Controlled, repeatable migrations (e.g. registering options or roles through the plugin)
- Non-secret project configuration

## What a deployment never replaces

- WordPress core (updated separately, through WordPress)
- The staging/production database
- Uploads
- Content created by editors

## Open questions for the deployment phase

- Transport to Hostinger (rsync over SSH via `~/bin/hostinger` is available).
- Removing `hostinger-ai-theme` and Hostinger plugins from staging, and whether
  LiteSpeed Cache stays there.
- Replacing the staging pages' Hostinger-specific blocks.
- Staging/production secrets management.
