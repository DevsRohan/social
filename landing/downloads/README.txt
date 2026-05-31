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
HOW TO BUILD THE TWO ZIPS (from the /social-proof-live plugin folder)
----------------------------------------------------------------

PRO build:
  - Just zip the plugin folder as-is.
  - zip -r social-proof-live-pro.zip social-proof-live

TRIAL build (24h self-expiring):
  - Open social-proof-live/social-proof-live.php
  - Add this line right after the other define() constants near the top:
        define( 'SPLIVE_TRIAL', true );
  - Then zip it:
        zip -r social-proof-live-trial.zip social-proof-live
  - The trial runs fully for 24 hours, then deactivates AND deletes itself
    (the bundled includes/class-trial.php handles this automatically).
