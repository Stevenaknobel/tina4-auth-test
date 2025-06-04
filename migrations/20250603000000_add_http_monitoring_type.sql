-- Add HTTP monitoring type
INSERT INTO monitoring_types (type_name) 
SELECT 'HTTP' 
WHERE NOT EXISTS (SELECT 1 FROM monitoring_types WHERE type_name = 'HTTP');