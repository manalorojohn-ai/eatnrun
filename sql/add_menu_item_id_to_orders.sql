-- Add menu_item_id column to orders table if it doesn't exist
SET @dbname = 'food_ordering';

-- Check if column exists
SET @exists = 0;
SELECT COUNT(*) INTO @exists 
FROM information_schema.columns 
WHERE table_schema = @dbname 
AND table_name = 'orders' 
AND column_name = 'menu_item_id';

-- Add column if it doesn't exist
SET @query = IF(@exists = 0,
    'ALTER TABLE orders ADD COLUMN menu_item_id INT(11) NULL AFTER received_at, ADD CONSTRAINT fk_orders_menu_item FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT "Column already exists" AS message'
);

PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update existing orders by getting menu_item_id from order_details
UPDATE orders o
JOIN (
    SELECT order_id, menu_item_id 
    FROM order_details 
    GROUP BY order_id 
    HAVING COUNT(*) = 1
) od ON o.id = od.order_id
SET o.menu_item_id = od.menu_item_id
WHERE o.menu_item_id IS NULL;

-- Update the ratings system to use this menu_item_id if needed
-- This creates a trigger to automatically set menu_item_id in ratings table from order table
DELIMITER //
CREATE TRIGGER IF NOT EXISTS set_rating_menu_item_id
BEFORE INSERT ON ratings
FOR EACH ROW
BEGIN
    -- If menu_item_id is not set but order_id is set, get it from the order
    IF NEW.menu_item_id = 0 OR NEW.menu_item_id IS NULL THEN
        SELECT menu_item_id INTO NEW.menu_item_id 
        FROM orders 
        WHERE id = NEW.order_id
        LIMIT 1;
    END IF;
END; //
DELIMITER ; 