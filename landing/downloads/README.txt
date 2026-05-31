PUT YOUR TWO PLUGIN ZIP FILES IN THIS FOLDER
============================================

1. social-proof-live-trial.zip   <- the 24-hour trial build
2. social-proof-live-pro.zip      <- the full Pro build

Exact filenames matter (they are referenced in ../api/config.php).

These files are NOT directly downloadable by URL (see .htaccess).
They are streamed securely by ../api/download.php:
  - Trial:  download.php?type=trial          (free)
  - Pro:    download.php?type=pro&token=...   (only after a verified payment)

----------------------------------------------------------------
HOW TO BUILD THE TWO ZIPS
----------------------------------------------------------------

Both plugin folders are ready in the repository root — just zip them:

PRO build (folder: /social-proof-live):
  zip -r social-proof-live-pro.zip social-proof-live

TRIAL build (folder: /social-proof-live-trial) — already configured:
  zip -r social-proof-live-trial.zip social-proof-live-trial

The trial folder already has `define( 'SPLIVE_TRIAL', true );` set, so it:
  - runs every feature for 24 hours,
  - shows a LIVE countdown timer at the top of the WordPress admin,
  - links "Upgrade to Pro" to https://devsarun.io/plugin/chat/,
  - and deactivates + deletes itself automatically after 24 hours.

Then drop both ZIPs into this /downloads folder with the exact names above.
