
Diva Lender Matcher – Deploy Guide
==================================

Folders to upload under your public_html:
- /data  -> put lenders.csv or lenders.json here
- /api   -> lender_match.php endpoint
- /public -> optional example page

Steps:
1) Upload the whole `diva-lender-matcher` folder contents into your hosting:
   - /public_html/data/lenders.csv
   - /public_html/data/lenders.json (optional)
   - /public_html/api/lender_match.php
   - /public_html/public/example-chatbot.html (demo)

2) Test in browser:
   https://YOUR-DOMAIN.com/api/lender_match.php?q=society%20patta

3) Frontend integration:
   fetch("https://YOUR-DOMAIN.com/api/lender_match.php?q=" + encodeURIComponent(userMessage))
     .then(r => r.json())
     .then(data => { /* show top 4 results in chat */ });

CSV columns:
- id, displayName, rawText

You can keep only CSV or only JSON; endpoint prefers CSV if both exist.
