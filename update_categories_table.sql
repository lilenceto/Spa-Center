-- Update service_categories table to add new fields for category management
USE spa_center;

-- Add new fields to service_categories table
ALTER TABLE service_categories 
ADD COLUMN description TEXT AFTER name,
ADD COLUMN icon VARCHAR(100) DEFAULT 'fas fa-spa' AFTER description,
ADD COLUMN color VARCHAR(7) DEFAULT '#d4af37' AFTER icon;

-- Update existing categories with better descriptions and icons
UPDATE service_categories SET 
    description = 'Experience ultimate relaxation with our premium massage and body treatment services',
    icon = 'fas fa-hands',
    color = '#d4af37'
WHERE id = 1;

UPDATE service_categories SET 
    description = 'Energize your body and mind with our fitness and wellness programs',
    icon = 'fas fa-dumbbell',
    color = '#4caf50'
WHERE id = 2;

UPDATE service_categories SET 
    description = 'Reveal your natural beauty with our advanced facial and beauty treatments',
    icon = 'fas fa-spa',
    color = '#9c27b0'
WHERE id = 3;

UPDATE service_categories SET 
    description = 'Dive into relaxation with our aqua therapy and pool services',
    icon = 'fas fa-swimming-pool',
    color = '#00bcd4'
WHERE id = 4;

-- Verify the changes
SELECT * FROM service_categories;
