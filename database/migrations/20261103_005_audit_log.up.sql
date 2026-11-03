-- Insert-only audit trail. actor snapshots remain available after a user is deleted.
CREATE TABLE audit_logs (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    action VARCHAR(20) NOT NULL,
    entity_type VARCHAR(80) NOT NULL,
    entity_id INT NULL,
    actor_id INT NULL,
    actor_name VARCHAR(150) NULL,
    actor_email VARCHAR(150) NULL,
    details TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY audit_entity (entity_type, entity_id),
    KEY audit_actor (actor_id),
    KEY audit_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE users
    ADD COLUMN created_by INT NULL AFTER department,
    ADD COLUMN updated_by INT NULL AFTER created_by;

ALTER TABLE projects
    ADD COLUMN created_by INT NULL AFTER project_status,
    ADD COLUMN updated_by INT NULL AFTER created_by;

ALTER TABLE development_charges
    ADD COLUMN created_by INT NULL AFTER record_year,
    ADD COLUMN updated_by INT NULL AFTER created_by;

ALTER TABLE development_charge_payments
    ADD COLUMN created_by INT NULL AFTER status,
    ADD COLUMN updated_by INT NULL AFTER created_by;

ALTER TABLE project_payment_schedules
    ADD COLUMN created_by INT NULL AFTER status,
    ADD COLUMN updated_by INT NULL AFTER created_by;

ALTER TABLE project_expenses
    ADD COLUMN created_by INT NULL AFTER description,
    ADD COLUMN updated_by INT NULL AFTER created_by;

ALTER TABLE project_variations
    ADD COLUMN created_by INT NULL AFTER variation_date,
    ADD COLUMN updated_by INT NULL AFTER created_by;
