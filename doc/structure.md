/dragonherd
│
├── dragonherd.php                    ← Main plugin file
├── /includes
│   ├── class-dragonherd-manager.php ← Your core logic (BugHerd + OpenAI)
│   └── admin-page.php               ← Optional admin page UI
├── /logs                             ← Stores messages/summary (if file-based)
│   ├── messages.txt
│   └── summary.txt
└── /vendor (if using Guzzle)

