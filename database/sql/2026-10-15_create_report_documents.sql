-- Menu admin Reports & Documents (App\Support\ReportDocuments, Admin\ReportController).
-- Sama dengan migration 2026_10_15_000000_create_report_documents_tables (database demo_twp_adm).
--
--   mgr.report_doc          : satu report (group, nama, versi terakhir)
--   mgr.report_doc_version  : satu baris per versi (V1, V2, ...) + siapa & kapan upload
--   mgr.report_doc_file     : file di tiap versi. File yang dibawa dari versi sebelumnya (tidak diganti)
--                             memakai stored_path yang sama; from_version = versi asal file itu.
-- File fisik: storage/app/private/reports/{report_id}/v{version}/{nama file}

IF OBJECT_ID('mgr.report_doc', 'U') IS NULL
CREATE TABLE mgr.report_doc (
    id              INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    group_cd        NVARCHAR(30)  NOT NULL,
    name            NVARCHAR(200) NOT NULL,
    current_version INT           NOT NULL DEFAULT 1,
    created_by      NVARCHAR(100) NULL,
    created_at      DATETIME2(0)  NOT NULL DEFAULT SYSDATETIME(),
    updated_by      NVARCHAR(100) NULL,
    updated_at      DATETIME2(0)  NOT NULL DEFAULT SYSDATETIME()
);

IF OBJECT_ID('mgr.report_doc_version', 'U') IS NULL
CREATE TABLE mgr.report_doc_version (
    id             INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    report_id      INT           NOT NULL REFERENCES mgr.report_doc(id) ON DELETE CASCADE,
    version        INT           NOT NULL,
    uploaded_by    NVARCHAR(100) NULL,
    uploaded_email NVARCHAR(100) NULL,
    uploaded_at    DATETIME2(0)  NOT NULL DEFAULT SYSDATETIME(),
    CONSTRAINT uq_report_doc_version UNIQUE (report_id, version)
);

IF OBJECT_ID('mgr.report_doc_file', 'U') IS NULL
CREATE TABLE mgr.report_doc_file (
    id           INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    version_id   INT           NOT NULL REFERENCES mgr.report_doc_version(id) ON DELETE CASCADE,
    file_name    NVARCHAR(255) NOT NULL,
    stored_path  NVARCHAR(500) NOT NULL,
    size         BIGINT        NOT NULL DEFAULT 0,
    mime         NVARCHAR(150) NULL,
    from_version INT           NOT NULL
);

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'ix_report_doc_group')
CREATE INDEX ix_report_doc_group ON mgr.report_doc (group_cd);

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'ix_report_doc_file_version')
CREATE INDEX ix_report_doc_file_version ON mgr.report_doc_file (version_id);
