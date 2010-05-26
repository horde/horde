ALTER TABLE hermes_deliverables DROP INDEX hermes_deliverables_client;
CREATE INDEX hermes_deliverables_client ON hermes_deliverables (client_id);
CREATE INDEX hermes_deliverables_active ON hermes_deliverables (deliverable_active);
