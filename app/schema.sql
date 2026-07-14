CREATE TABLE admin_users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  username TEXT NOT NULL UNIQUE,
  password_hash TEXT NOT NULL,
  display_name TEXT NOT NULL DEFAULT 'Ahmet Çetin',
  is_active INTEGER NOT NULL DEFAULT 1 CHECK(is_active IN (0,1)),
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_login_at TEXT
);

CREATE TABLE audit_log (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER,
  action TEXT NOT NULL,
  entity_type TEXT NOT NULL,
  entity_id INTEGER,
  details TEXT NOT NULL DEFAULT '',
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(user_id) REFERENCES admin_users(id) ON DELETE SET NULL
);

CREATE TABLE categories (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  slug TEXT NOT NULL UNIQUE,
  title TEXT NOT NULL,
  sort_order INTEGER NOT NULL DEFAULT 0,
  is_active INTEGER NOT NULL DEFAULT 1 CHECK(is_active IN (0,1))
);

CREATE TABLE channels (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  slug TEXT NOT NULL UNIQUE,
  title TEXT NOT NULL,
  url TEXT NOT NULL DEFAULT '',
  sort_order INTEGER NOT NULL DEFAULT 0,
  is_active INTEGER NOT NULL DEFAULT 1 CHECK(is_active IN (0,1))
);

CREATE TABLE links (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  owner_type TEXT NOT NULL CHECK(owner_type IN ('project','update','story_section')),
  owner_id INTEGER NOT NULL,
  link_type TEXT NOT NULL DEFAULT 'external',
  title TEXT NOT NULL,
  url TEXT NOT NULL,
  sort_order INTEGER NOT NULL DEFAULT 0,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE media (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  project_id INTEGER,
  file_name TEXT NOT NULL,
  original_name TEXT NOT NULL,
  relative_path TEXT NOT NULL UNIQUE,
  mime_type TEXT NOT NULL DEFAULT 'application/octet-stream',
  media_type TEXT NOT NULL DEFAULT 'file' CHECK(media_type IN ('image','video','audio','file')),
  title TEXT NOT NULL DEFAULT '',
  alt_text TEXT NOT NULL DEFAULT '',
  caption TEXT NOT NULL DEFAULT '',
  width INTEGER,
  height INTEGER,
  size_bytes INTEGER NOT NULL DEFAULT 0,
  checksum_sha256 TEXT NOT NULL DEFAULT '',
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  deleted_at TEXT,
  FOREIGN KEY(project_id) REFERENCES projects(id) ON DELETE SET NULL
);

CREATE TABLE notes (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  message TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'pending' CHECK(status IN ('pending','published','rejected','trash')),
  ip_hash TEXT NOT NULL DEFAULT '',
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  published_at TEXT
);

CREATE TABLE project_tags (
  project_id INTEGER NOT NULL,
  tag_id INTEGER NOT NULL,
  PRIMARY KEY(project_id, tag_id),
  FOREIGN KEY(project_id) REFERENCES projects(id) ON DELETE CASCADE,
  FOREIGN KEY(tag_id) REFERENCES tags(id) ON DELETE CASCADE
);

CREATE TABLE projects (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  slug TEXT NOT NULL UNIQUE,
  title TEXT NOT NULL,
  question TEXT NOT NULL DEFAULT '',
  summary TEXT NOT NULL DEFAULT '',
  category_id INTEGER,
  status TEXT NOT NULL DEFAULT 'draft',
  status_label TEXT NOT NULL DEFAULT 'Taslak',
  type_label TEXT NOT NULL DEFAULT 'Proje',
  visibility TEXT NOT NULL DEFAULT 'private' CHECK(visibility IN ('public','unlisted','private')),
  workshop_status TEXT NOT NULL DEFAULT 'none' CHECK(workshop_status IN ('none','open','paused','closed')),
  workshop_question TEXT NOT NULL DEFAULT '',
  closing_state TEXT,
  closing_note TEXT NOT NULL DEFAULT '',
  started_at TEXT,
  ended_at TEXT,
  cover_media_id INTEGER,
  show_on_home INTEGER NOT NULL DEFAULT 0 CHECK(show_on_home IN (0,1)),
  show_in_archive INTEGER NOT NULL DEFAULT 1 CHECK(show_in_archive IN (0,1)),
  show_in_widget INTEGER NOT NULL DEFAULT 0 CHECK(show_in_widget IN (0,1)),
  is_pinned INTEGER NOT NULL DEFAULT 0 CHECK(is_pinned IN (0,1)),
  home_section TEXT NOT NULL DEFAULT 'none' CHECK(home_section IN ('none','focus','trace')),
  sort_order REAL NOT NULL DEFAULT 999,
  published_at TEXT,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  deleted_at TEXT,
  FOREIGN KEY(category_id) REFERENCES categories(id) ON DELETE SET NULL,
  FOREIGN KEY(cover_media_id) REFERENCES media(id) ON DELETE SET NULL
);

CREATE TABLE schema_migrations (
  version INTEGER PRIMARY KEY,
  applied_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE settings (
  key TEXT PRIMARY KEY,
  value TEXT NOT NULL,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE stories (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  project_id INTEGER NOT NULL UNIQUE,
  title TEXT NOT NULL,
  question TEXT NOT NULL DEFAULT '',
  summary TEXT NOT NULL DEFAULT '',
  reading_time TEXT NOT NULL DEFAULT '',
  status TEXT NOT NULL DEFAULT 'draft' CHECK(status IN ('draft','published','archived')),
  visibility TEXT NOT NULL DEFAULT 'public' CHECK(visibility IN ('public','unlisted','private')),
  show_on_home INTEGER NOT NULL DEFAULT 0 CHECK(show_on_home IN (0,1)),
  show_in_archive INTEGER NOT NULL DEFAULT 1 CHECK(show_in_archive IN (0,1)),
  is_pinned INTEGER NOT NULL DEFAULT 0 CHECK(is_pinned IN (0,1)),
  sort_order REAL NOT NULL DEFAULT 999,
  published_at TEXT,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  deleted_at TEXT,
  FOREIGN KEY(project_id) REFERENCES projects(id) ON DELETE CASCADE
);

CREATE TABLE story_parts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  story_id INTEGER NOT NULL,
  title TEXT NOT NULL,
  subtitle TEXT NOT NULL DEFAULT '',
  description TEXT NOT NULL DEFAULT '',
  anchor TEXT NOT NULL DEFAULT '',
  sort_order INTEGER NOT NULL DEFAULT 0,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(story_id) REFERENCES stories(id) ON DELETE CASCADE
);

CREATE TABLE story_section_items (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  section_id INTEGER NOT NULL,
  group_key TEXT NOT NULL DEFAULT '',
  item_type TEXT NOT NULL DEFAULT 'item',
  step TEXT NOT NULL DEFAULT '',
  title TEXT NOT NULL DEFAULT '',
  subtitle TEXT NOT NULL DEFAULT '',
  text TEXT NOT NULL DEFAULT '',
  state TEXT NOT NULL DEFAULT '',
  value TEXT NOT NULL DEFAULT '',
  media_id INTEGER,
  source_update_id INTEGER,
  url TEXT NOT NULL DEFAULT '',
  sort_order INTEGER NOT NULL DEFAULT 0,
  FOREIGN KEY(section_id) REFERENCES story_sections(id) ON DELETE CASCADE,
  FOREIGN KEY(media_id) REFERENCES media(id) ON DELETE SET NULL,
  FOREIGN KEY(source_update_id) REFERENCES updates(id) ON DELETE SET NULL
);

CREATE TABLE story_section_media (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  section_id INTEGER NOT NULL,
  media_id INTEGER NOT NULL,
  role TEXT NOT NULL DEFAULT 'gallery',
  caption_override TEXT NOT NULL DEFAULT '',
  sort_order INTEGER NOT NULL DEFAULT 0,
  FOREIGN KEY(section_id) REFERENCES story_sections(id) ON DELETE CASCADE,
  FOREIGN KEY(media_id) REFERENCES media(id) ON DELETE CASCADE
);

CREATE TABLE story_sections (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  story_id INTEGER NOT NULL,
  part_id INTEGER,
  source_update_id INTEGER,
  type TEXT NOT NULL DEFAULT 'text',
  layout TEXT NOT NULL DEFAULT 'default',
  section_kind TEXT NOT NULL DEFAULT '',
  label TEXT NOT NULL DEFAULT '',
  title TEXT NOT NULL DEFAULT '',
  body_text TEXT NOT NULL DEFAULT '',
  quote_text TEXT NOT NULL DEFAULT '',
  intro_text TEXT NOT NULL DEFAULT '',
  note_text TEXT NOT NULL DEFAULT '',
  code_text TEXT NOT NULL DEFAULT '',
  media_id INTEGER,
  sort_order INTEGER NOT NULL DEFAULT 0,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  deleted_at TEXT,
  FOREIGN KEY(story_id) REFERENCES stories(id) ON DELETE CASCADE,
  FOREIGN KEY(part_id) REFERENCES story_parts(id) ON DELETE SET NULL,
  FOREIGN KEY(source_update_id) REFERENCES updates(id) ON DELETE SET NULL,
  FOREIGN KEY(media_id) REFERENCES media(id) ON DELETE SET NULL
);

CREATE TABLE tags (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL UNIQUE,
  slug TEXT NOT NULL UNIQUE
);

CREATE TABLE update_media (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  update_id INTEGER NOT NULL,
  media_id INTEGER NOT NULL,
  role TEXT NOT NULL DEFAULT 'gallery',
  caption_override TEXT NOT NULL DEFAULT '',
  sort_order INTEGER NOT NULL DEFAULT 0,
  FOREIGN KEY(update_id) REFERENCES updates(id) ON DELETE CASCADE,
  FOREIGN KEY(media_id) REFERENCES media(id) ON DELETE CASCADE
);

CREATE TABLE updates (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  project_id INTEGER NOT NULL,
  slug TEXT NOT NULL,
  work_date TEXT,
  display_label TEXT NOT NULL DEFAULT '',
  title TEXT NOT NULL,
  summary TEXT NOT NULL DEFAULT '',
  entry_kind TEXT NOT NULL DEFAULT 'journal' CHECK(entry_kind IN ('journal','experiment','problem','decision','media','source')),
  story_role TEXT NOT NULL DEFAULT 'auto',
  story_section_type TEXT NOT NULL DEFAULT 'auto',
  story_layout TEXT NOT NULL DEFAULT 'auto',
  story_label TEXT NOT NULL DEFAULT '',
  tried TEXT NOT NULL DEFAULT '',
  failed TEXT NOT NULL DEFAULT '',
  decision TEXT NOT NULL DEFAULT '',
  next_step TEXT NOT NULL DEFAULT '',
  phase TEXT NOT NULL DEFAULT 'Genel',
  is_milestone INTEGER NOT NULL DEFAULT 0 CHECK(is_milestone IN (0,1)),
  status TEXT NOT NULL DEFAULT 'draft' CHECK(status IN ('draft','published')),
  visibility TEXT NOT NULL DEFAULT 'public' CHECK(visibility IN ('public','unlisted','private')),
  show_in_recent INTEGER NOT NULL DEFAULT 1 CHECK(show_in_recent IN (0,1)),
  sort_order REAL NOT NULL DEFAULT 999,
  published_at TEXT,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  deleted_at TEXT,
  UNIQUE(project_id, slug),
  FOREIGN KEY(project_id) REFERENCES projects(id) ON DELETE CASCADE
);

CREATE TABLE update_blocks (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  update_id INTEGER NOT NULL,
  block_type TEXT NOT NULL,
  title TEXT NOT NULL DEFAULT '',
  body TEXT NOT NULL DEFAULT '',
  sort_order INTEGER NOT NULL DEFAULT 0,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(update_id) REFERENCES updates(id) ON DELETE CASCADE
);

CREATE INDEX idx_links_owner ON links(owner_type, owner_id, sort_order);

CREATE INDEX idx_projects_public ON projects(visibility, deleted_at, show_in_archive, sort_order);

CREATE INDEX idx_projects_workshop ON projects(workshop_status, visibility, deleted_at);

CREATE INDEX idx_sections_story ON story_sections(story_id, deleted_at, sort_order);

CREATE INDEX idx_sections_part ON story_sections(part_id, sort_order, id);

CREATE INDEX idx_story_parts_story ON story_parts(story_id, sort_order, id);

CREATE INDEX idx_stories_public ON stories(status, visibility, deleted_at, show_in_archive, sort_order);

CREATE INDEX idx_updates_project ON updates(project_id, status, deleted_at, work_date, sort_order);

CREATE INDEX idx_updates_recent ON updates(show_in_recent, status, visibility, deleted_at, work_date);

CREATE INDEX idx_update_blocks_update ON update_blocks(update_id, sort_order, id);

CREATE UNIQUE INDEX ux_story_section_media ON story_section_media(section_id,media_id);

CREATE UNIQUE INDEX ux_story_parts_anchor ON story_parts(story_id,anchor);

CREATE UNIQUE INDEX ux_update_media ON update_media(update_id,media_id);
