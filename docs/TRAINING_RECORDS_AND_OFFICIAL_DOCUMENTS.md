# MCARE Training Records and Official Documents

## Workflow

1. The trainer records each trainee's learning-outcome result and percentage score.
2. The achievement chart shows those detailed outcome results per batch.
3. The progress chart derives each unit result from the same record.
4. MCARE checks that the trainee is approved and paid, the batch is complete, every required competency and outcome is competent, all published modules are complete, and all published quizzes are passed.
5. An admin generates and reviews the COTC and TOR. Generation locks the source grades so an issued document cannot silently change.
6. The admin releases the COTC. The trainee receives one audited download; an interrupted or lost copy requires an admin reissue with a reason.
7. TOR generation, release, download, and batch export remain admin-only. Large batch exports run through the queue and are delivered as expiring ZIP files.

## Server Setup

```powershell
composer install
php artisan migrate
php artisan queue:work --queue=default --tries=2 --timeout=600
```

Official COTC and TOR PDFs are generated in PHP with FPDF by default when Node.js or Chrome is not available. That is the path used on shared hosting such as Hostinger. Optional Chromium rendering still works when Node, npm, and Chrome or Edge are installed:

```dotenv
OFFICIAL_DOCUMENT_PDF_ENGINE=auto
OFFICIAL_DOCUMENT_DISK=local
OFFICIAL_DOCUMENT_EXPORT_EXPIRY_HOURS=24
MCARE_TRAINER_SIGNATORY="Maricris N. Collao"
MCARE_REGISTRAR_SIGNATORY="Salvacion A. Collao"
```

Set `OFFICIAL_DOCUMENT_PDF_ENGINE=fpdf` to force the PHP engine. Set `browsershot` only on machines that have Node.js and a browser binary. On Windows, MCARE detects Microsoft Edge or Google Chrome when `BROWSERSHOT_CHROME_PATH` is blank.

Use Supervisor, systemd, or the hosting platform's worker service in production instead of leaving `queue:work` in a terminal. Keep the document disk private. For multiple application servers or heavier download traffic, switch `OFFICIAL_DOCUMENT_DISK` to an S3-compatible private disk and return short-lived signed links.

## Security and Scaling Decisions

- PDFs are rendered from server-controlled templates; users do not submit HTML.
- Source records are locked after generation and every generation, release, reissue, and download is logged.
- The trainee route consumes a COTC download atomically while holding a database lock, preventing parallel requests from obtaining extra copies.
- Admin batch TOR exports process trainees in chunks of 25, use unique export identifiers, and expire automatically.
- Browser rendering runs in the queue so the web request remains responsive.
- The system stores generated files outside the public web root.

## Client Items to Confirm Before Final Release

- Approved TESDA logo asset and permission to reproduce it.
- Exact COTC serial-number format, seal, signatures, and issuing place.
- Whether the 786 training hours and current trainer/registrar signatories are still correct.
- The official handling of scores below 75 and whether every TOR competency receives one final grade.
- The source progress form says `Caregiving NC I`, while the TOR, COTC, project scope, and competency content indicate `Caregiving NC II`; MCARE currently uses NC II.
