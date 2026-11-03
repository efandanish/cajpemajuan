ALTER TABLE project_variations DROP COLUMN updated_by, DROP COLUMN created_by;
ALTER TABLE project_expenses DROP COLUMN updated_by, DROP COLUMN created_by;
ALTER TABLE project_payment_schedules DROP COLUMN updated_by, DROP COLUMN created_by;
ALTER TABLE development_charge_payments DROP COLUMN updated_by, DROP COLUMN created_by;
ALTER TABLE development_charges DROP COLUMN updated_by, DROP COLUMN created_by;
ALTER TABLE projects DROP COLUMN updated_by, DROP COLUMN created_by;
ALTER TABLE users DROP COLUMN updated_by, DROP COLUMN created_by;
DROP TABLE audit_logs;
