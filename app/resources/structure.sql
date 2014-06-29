PRAGMA foreign_keys = OFF;

-- ----------------------------
-- Table structure for "main"."certificates"
-- ----------------------------
DROP TABLE "main"."certificates";
CREATE TABLE "certificates" (
"id"  INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
"user_id"  INTEGER,
"name"  TEXT,
"slug"  TEXT,
"need_update"  INTEGER DEFAULT 1,
"need_remove"  INTEGER DEFAULT 0,
CONSTRAINT "user_fk" FOREIGN KEY ("user_id") REFERENCES "users" ("id") ON DELETE SET NULL ON UPDATE CASCADE
);

-- ----------------------------
-- Table structure for "main"."domains"
-- ----------------------------
DROP TABLE "main"."domains";
CREATE TABLE "domains" (
"id"  INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
"user_id"  INTEGER,
"certificate_id"  INTEGER,
"domain"  TEXT,
"target"  TEXT,
"wildcard"  INTEGER,
"need_update"  INTEGER DEFAULT 1,
"need_remove"  INTEGER DEFAULT 0,
CONSTRAINT "certificate_fk" FOREIGN KEY ("certificate_id") REFERENCES "certificates" ("id") ON DELETE SET NULL ON UPDATE CASCADE,
CONSTRAINT "user_fk" FOREIGN KEY ("user_id") REFERENCES "users" ("id") ON DELETE SET NULL ON UPDATE CASCADE
);

-- ----------------------------
-- Table structure for "main"."misc"
-- ----------------------------
DROP TABLE "main"."misc";
CREATE TABLE "misc" (
"key"  TEXT NOT NULL,
"value"  TEXT,
PRIMARY KEY ("key")
);

-- ----------------------------
-- Table structure for "main"."sqlite_sequence"
-- ----------------------------
DROP TABLE "main"."sqlite_sequence";
CREATE TABLE sqlite_sequence(name,seq);

-- ----------------------------
-- Table structure for "main"."users"
-- ----------------------------
DROP TABLE "main"."users";
CREATE TABLE "users" (
"id"  INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
"login"  TEXT,
"password"  TEXT
);

-- ----------------------------
-- Indexes structure for table certificates
-- ----------------------------
CREATE UNIQUE INDEX "main"."slug_unique"
ON "certificates" ("slug" ASC);

-- ----------------------------
-- Indexes structure for table domains
-- ----------------------------
CREATE UNIQUE INDEX "main"."domain_unique"
ON "domains" ("domain" ASC);
