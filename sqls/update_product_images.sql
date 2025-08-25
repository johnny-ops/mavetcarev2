-- Update existing products with sample image URLs
-- This will link products to existing images in the images/products directory

UPDATE inventory SET product_image = 'images/products/premium dog food.png' WHERE name = 'Premium Dog Food';
UPDATE inventory SET product_image = 'images/products/specialdelight.png' WHERE name = 'Cat Food Deluxe';
UPDATE inventory SET product_image = 'images/products/injectables.png' WHERE name = 'Anti-Parasitic Medicine';
UPDATE inventory SET product_image = 'images/products/vaccine.png' WHERE name = 'Vaccine Vial';
UPDATE inventory SET product_image = 'images/products/cabinet stocks.png' WHERE name = 'Surgical Gloves';
UPDATE inventory SET product_image = 'images/products/novapink.png' WHERE name = 'Pet Shampoo';
UPDATE inventory SET product_image = 'images/products/anesthetics.png' WHERE name = 'Dog Collar';
UPDATE inventory SET product_image = 'images/products/specialdelight.png' WHERE name = 'Cat Toy Set';

-- Add more products with images if needed
INSERT INTO inventory (name, category, quantity, price, product_image, minimum_stock) VALUES
('Premium Cat Food', 'Food', 30, 420.00, 'images/products/specialdelight.png', 8),
('Pet Vitamins', 'Medicine', 25, 180.00, 'images/products/injectables.png', 5),
('Pet Bed', 'Supplies', 15, 350.00, 'images/products/cabinet stocks.png', 3),
('Training Treats', 'Food', 40, 120.00, 'images/products/premium dog food.png', 10);

