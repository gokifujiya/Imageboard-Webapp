# Imageboard Webapp

A lightweight PHP/MySQL imageboard-style application built from scratch.

## Features
- Threads and replies (with subject, content, optional images)
- DAO-based architecture with `PostDAO` / `PostDAOImpl`
- Migration system to create the `posts` table
- Clean MVC-like structure (Routing, Views, Models, Database)
- Example `.gitignore` and `config/database.php.example` for safe sharing

## Setup
1. Clone this repository:
   ```bash
   git clone git@github.com:gokifujiya/Imageboard-Webapp.git
   cd Imageboard-Webapp
2. Copy and edit database config:
   ```bashcp
   config/database.php.example config/database.php
3. Run migrations:
   ```bashcp
   php console migrate
4. Start local server:
   ```bashcp
   php -S 127.0.0.1:8000 -t public
5. http://127.0.0.1:8000/threads
