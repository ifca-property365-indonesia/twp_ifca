-- mgr.log_login: jenis akun, email login dan browser (sama dengan migration
-- 2026_09_25_000000_add_account_to_log_login). Jalankan di database demo_twp_adm.
IF COL_LENGTH('mgr.log_login', 'tableforeign') IS NULL
    ALTER TABLE mgr.log_login ADD tableforeign NVARCHAR(20) NULL;
IF COL_LENGTH('mgr.log_login', 'email') IS NULL
    ALTER TABLE mgr.log_login ADD email NVARCHAR(100) NULL;
IF COL_LENGTH('mgr.log_login', 'user_agent') IS NULL
    ALTER TABLE mgr.log_login ADD user_agent NVARCHAR(500) NULL;
GO
UPDATE mgr.log_login SET tableforeign = 'tenant' WHERE tableforeign IS NULL;
