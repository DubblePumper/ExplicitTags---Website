# AI_Analyser_Website
website for my ai porn analyser

## Feel free to help me with my website.

# File structure of project
```text
AI_Analyser_Website/
├── .gitignore
├── README.md
├── LICENSE
├── docker-compose.yaml
└── app/
    ├── composer.json
    ├── composer.lock
    ├── docker/
    │   └── Dockerfile
    ├── vendor/                # Composer dependencies
    ├── config/
    │   ├── config.php
    │   └── globals.php
    ├── public/
    │   ├── .htaccess
    │   ├── index.php
    │   ├── assets/
    │   │   ├── css/
    │   │   ├── js/
    │   │   │   ├── experiencePage/
    │   │   │   │   ├── pagination.js
    │   │   │   │   ├── question2.js
    │   │   │   │   ├── question3.js
    │   │   │   │   └── question4.js
    │   │   │   ├── modals/
    │   │   │   │   └── performerModal.js
    │   │   │   ├── extension/
    │   │   │   │   └── messageListener.js
    │   │   │   ├── tagPage/
    │   │   │   │   └── functions.js
    │   │   │   └── utils/
    │   │   │       └── cache.js
    │   │   ├── images/
    │   │   │   ├── icons/
    │   │   │   └── website_images/
    │   │   └── 3dmodels/
    │   ├── api/
    │   │   ├── performer_detail_sse.php
    │   │   └── performers_sse.php
    │   └── pages/              # Publieke pagina’s voor gebruikers
    │       ├── experience.php
    │       └── tag.php
    ├── src/                    # Interne applicatielogica (controllers, views, etc.)
    │   ├── Controller/
    │   ├── Includes/
    │   │   ├── head.php
    │   │   ├── include-all.php
    │   │   ├── minimal-header.php
    │   │   └── scripts.php
    │   ├── Utils/
    │   │   ├── VideoDownloader.php
    │   │   ├── tagVideoThroughUrl/
    │   │   │   ├── check-processing-status.php
    │   │   │   ├── database-functions.php
    │   │   │   └── process-video.php
    │   │   └── Workers/
    │   │       └── process_video_queue.php
    │   └── Database/
    │       └── database_structure.sql
    └── storage/                # Niet-publieke data (uploads, logs)
        ├── uploads/
        │   ├── temp/
        │   └── videos/
        │       └── debug_html_67c1b5a10fa9e.txt
        └── logs/
            ├── video_downloader.log
            └── video_processing.log
```
