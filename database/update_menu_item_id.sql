-- Update menu_item_id for existing orders
-- First, update from order_details table
UPDATE orders o
JOIN (
    SELECT order_id, menu_item_id 
    FROM order_details 
    GROUP BY order_id 
    HAVING COUNT(*) = 1
) od ON o.id = od.order_id
SET o.menu_item_id = od.menu_item_id
WHERE o.menu_item_id IS NULL OR o.menu_item_id = 0;

-- Then, update from order_items table if it exists
UPDATE orders o
JOIN (
    SELECT order_id, menu_item_id 
    FROM order_items 
    GROUP BY order_id 
    HAVING COUNT(*) = 1
) oi ON o.id = oi.order_id
SET o.menu_item_id = oi.menu_item_id
WHERE o.menu_item_id IS NULL OR o.menu_item_id = 0; 