-- Update services table to ensure it has the correct structure
USE spa_center;

-- Check if category_id column exists, if not add it
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'spa_center' 
     AND TABLE_NAME = 'services' 
     AND COLUMN_NAME = 'category_id') > 0,
    'SELECT "category_id column already exists" as status',
    'ALTER TABLE services ADD COLUMN category_id INT AFTER id'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- If category_id was just added, set default values based on existing category field
-- (This assumes the old category field had text values like 'масаж', 'фитнес', etc.)
UPDATE services SET category_id = 1 WHERE category = 'масаж' OR category LIKE '%massage%' OR category LIKE '%body%';
UPDATE services SET category_id = 2 WHERE category = 'фитнес' OR category LIKE '%fitness%' OR category LIKE '%yoga%';
UPDATE services SET category_id = 3 WHERE category LIKE '%beauty%' OR category LIKE '%facial%' OR category LIKE '%skin%';
UPDATE services SET category_id = 4 WHERE category LIKE '%pool%' OR category LIKE '%aqua%' OR category LIKE '%hydro%';

-- Set default category_id for any remaining services
UPDATE services SET category_id = 1 WHERE category_id IS NULL;

-- Add foreign key constraint if it doesn't exist
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
     WHERE TABLE_SCHEMA = 'spa_center' 
     AND TABLE_NAME = 'services' 
     AND COLUMN_NAME = 'category_id' 
     AND REFERENCED_TABLE_NAME = 'service_categories') > 0,
    'SELECT "Foreign key constraint already exists" as status',
    'ALTER TABLE services ADD CONSTRAINT fk_service_category FOREIGN KEY (category_id) REFERENCES service_categories(id)'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Remove the old category column if it exists
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'spa_center' 
     AND TABLE_NAME = 'services' 
     AND COLUMN_NAME = 'category') > 0,
    'ALTER TABLE services DROP COLUMN category',
    'SELECT "Old category column does not exist" as status'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verify the changes
SELECT s.id, s.name, s.category_id, c.name as category_name 
FROM services s 
JOIN service_categories c ON s.category_id = c.id;
