-- Add Beverages (Category ID: 2)
INSERT INTO menu_items (name, description, price, category_id, status) VALUES
('Coke', 'Refreshing cola drink', 45.00, 2, 'available'),
('Sprite', 'Lemon-lime carbonated drink', 45.00, 2, 'available'),
('Royal', 'Orange-flavored carbonated drink', 45.00, 2, 'available'),
('Mango Juice', 'Fresh Philippine mango juice', 55.00, 2, 'available'),
('Calamansi Juice', 'Fresh Filipino citrus juice', 45.00, 2, 'available');

-- Add Rice Meals (Category ID: 3)
INSERT INTO menu_items (name, description, price, category_id, status) VALUES
('Sisig with Rice', 'Sizzling chopped pork with egg and rice', 120.00, 3, 'available'),
('Adobo with Rice', 'Classic Filipino pork adobo with rice', 130.00, 3, 'available'),
('Fried Chicken with Rice', 'Crispy fried chicken with rice', 125.00, 3, 'available'),
('Burger Steak with Rice', 'Beef patty with mushroom gravy and rice', 115.00, 3, 'available'),
('Bicol Express with Rice', 'Spicy coconut milk-based pork dish with rice', 135.00, 3, 'available');

-- Add Desserts (Category ID: 4)
INSERT INTO menu_items (name, description, price, category_id, status) VALUES
('Leche Flan', 'Classic Filipino caramel custard', 60.00, 4, 'available'),
('Halo-Halo', 'Mixed Filipino dessert with shaved ice, sweet beans, fruits, and ice cream', 85.00, 4, 'available');

-- Add Burgers (Category ID: 1)
INSERT INTO menu_items (name, description, price, category_id, status) VALUES
('Plain Burger', 'Classic beef patty with fresh vegetables', 85.00, 1, 'available'),
('Cheese Burger', 'Beef patty with melted cheese and fresh vegetables', 95.00, 1, 'available'); 