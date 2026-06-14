# Test Credentials for Eat&Run

## Quick Login Details

To test the application, use any of these test accounts:

### Test Account 1
- **Username:** `johndoe`
- **Email:** `john@gmail.com`
- **Password:** `password123`
- **Phone:** `09123456789`

### Test Account 2
- **Username:** `maria`
- **Email:** `maria@yahoo.com`
- **Password:** `maria123`
- **Phone:** `09987654321`

### Test Account 3
- **Username:** `juancruz`
- **Email:** `juan@outlook.com`
- **Password:** `juan2024`
- **Phone:** `09234567890`

---

## How to Create Test Users

### Option 1: Using phpMyAdmin or MySQL Workbench

1. Open your MySQL management tool
2. Navigate to your `food_ordering` database
3. Open the Query tab
4. Copy and paste the contents from: `database/insert_test_users.sql`
5. Execute the query

### Option 2: Command Line (MySQL)

```bash
mysql -u root -p food_ordering < database/insert_test_users.sql
```

### Option 3: Using the Web Interface (if available)

1. Go to the admin panel
2. Look for "User Management" section
3. Create new users with the credentials above

---

## Testing the Features

Once logged in, you can:

✅ **Browse Menu** - View all menu items with filters
✅ **Search Products** - Use the search functionality  
✅ **Add to Cart** - Click "Add" on any item (uses AJAX - no page reload!)
✅ **View Cart** - See your cart items and manage quantities
✅ **Update Quantities** - Use +/- buttons (AJAX enabled)
✅ **Checkout** - Proceed to place an order
✅ **Order History** - View your previous orders

---

## New AJAX Features

The application now includes enhanced AJAX functionality for a smoother experience:

- **Menu Filtering** - Switch categories without page reload
- **Product Search** - Real-time search with debouncing
- **Cart Operations** - Add/remove/update items instantly
- **Real-time Updates** - Cart count updates automatically
- **Toast Notifications** - Beautiful feedback messages
- **Loading Indicators** - Visual feedback during operations

---

## Database Issues?

If you're having connection issues:

1. Ensure your MySQL/PostgreSQL database is running
2. Check your `.env` file has correct credentials
3. Verify database `food_ordering` exists
4. Run the SQL migration files in `database/` folder
5. Check error logs in `logs/` or browser console (F12)

---

## Need More Help?

- Check the register page to create additional accounts
- Use the "Forgot Password" feature to reset credentials
- Check browser console (F12) for any JavaScript errors
- Review server logs for PHP errors

Enjoy testing! 🍔🍜
