# Remote Database Connection Setup Guide

## Problem
The error "Host '192.168.0.104' is not allowed to connect to this MariaDB server" indicates that the remote MariaDB server at `192.168.0.101` is not configured to accept connections from your local machine (`192.168.0.104`).

## ⚠️ IMPORTANT: Fix Corrupt Index First

**If you get the error "#1034 - Index for table 'db' is corrupt; try to repair it", you need to fix this BEFORE granting privileges:**

### Step 1: Stop MariaDB Service
```bash
sudo systemctl stop mariadb
```

### Step 2: Repair the Database
```bash
sudo mysqlcheck -r --all-databases
```

### Step 3: Alternative Repair Method
If the above doesn't work, try:
```bash
sudo mysqlcheck -r --all-databases --force
```

### Step 4: Start MariaDB Service
```bash
sudo systemctl start mariadb
```

### Step 5: Verify Repair
```bash
sudo mysqlcheck --all-databases
```

**After fixing the corrupt index, proceed with the solutions below.**

## Solution Options

### Option 1: Configure Remote MariaDB Server (Recommended)

**On the remote server (192.168.0.101), follow these steps:**

1. **Access MySQL/MariaDB as root:**
   ```bash
   mysql -u root -p
   ```

2. **Grant permissions to allow connections from your local machine:**
   ```sql
   -- Option A: Allow connections from any host (less secure)
   GRANT ALL PRIVILEGES ON hotel_management.* TO 'your_username'@'%' IDENTIFIED BY 'your_password';
   
   -- Option B: Allow connections from specific IP (more secure)
   GRANT ALL PRIVILEGES ON hotel_management.* TO 'your_username'@'192.168.0.104' IDENTIFIED BY 'your_password';
   
   -- Option C: Allow connections from local network (recommended)
   GRANT ALL PRIVILEGES ON hotel_management.* TO 'your_username'@'192.168.0.%' IDENTIFIED BY 'your_password';
   ```

3. **Flush privileges to apply changes:**
   ```sql
   FLUSH PRIVILEGES;
   ```

4. **Verify the user permissions:**
   ```sql
   SHOW GRANTS FOR 'your_username'@'192.168.0.104';
   ```

5. **Exit MySQL:**
   ```sql
   EXIT;
   ```

### Option 2: Configure MariaDB to Accept Remote Connections

**On the remote server (192.168.0.101):**

1. **Edit MariaDB configuration:**
   ```bash
   sudo nano /etc/mysql/mariadb.conf.d/50-server.cnf
   ```

2. **Find and modify the bind-address line:**
   ```ini
   # Change from:
   bind-address = 127.0.0.1
   
   # To:
   bind-address = 0.0.0.0
   ```

3. **Restart MariaDB service:**
   ```bash
   sudo systemctl restart mariadb
   ```

### Option 3: Check Firewall Settings

**On the remote server (192.168.0.101):**

1. **Allow MySQL port through firewall:**
   ```bash
   sudo ufw allow 3306
   ```

2. **Or for iptables:**
   ```bash
   sudo iptables -A INPUT -p tcp --dport 3306 -j ACCEPT
   ```

### Option 4: Alternative - Use SSH Tunnel

If you can't modify the remote server, create an SSH tunnel:

```bash
ssh -L 3307:localhost:3306 username@192.168.0.101
```

Then update the connection in `ratings_api.php`:
```php
$remote_host = '127.0.0.1';  // Local tunnel
$remote_port = 3307;         // Tunnel port
```

## Testing the Connection

1. **Test from command line:**
   ```bash
   mysql -h 192.168.0.101 -u your_username -p hotel_management
   ```

2. **Test using the PHP script:**
   Visit: `admin/test_remote_connection.php`

3. **Test the API:**
   Visit: `admin/api/ratings_api.php`

## Troubleshooting

### Common Issues:

1. **"Access denied" error:**
   - Check username/password
   - Verify user exists on remote server
   - Check host permissions

2. **"Connection refused" error:**
   - Check if MariaDB is running
   - Verify bind-address configuration
   - Check firewall settings

3. **"Host not allowed" error:**
   - Grant proper permissions
   - Check user@host combination
   - Verify network connectivity

4. **"Index for table 'db' is corrupt" error:**
   - Follow the repair steps above
   - Restart MariaDB service
   - Try granting privileges again

### Debug Commands:

```bash
# Test network connectivity
ping 192.168.0.101

# Test port connectivity
telnet 192.168.0.101 3306

# Check MariaDB status
sudo systemctl status mariadb

# Check MariaDB logs
sudo tail -f /var/log/mysql/error.log

# Check database integrity
sudo mysqlcheck --all-databases
```

## Security Considerations

1. **Use specific IP addresses** instead of `%` when possible
2. **Use strong passwords** for database users
3. **Limit database privileges** to only what's needed
4. **Consider using SSL connections** for sensitive data
5. **Regularly audit** database user permissions

## Current Status

The API will now:
- ✅ Attempt to connect to remote database
- ✅ Provide detailed error messages
- ✅ Fall back to local database only if remote fails
- ✅ Show system message when remote connection fails
- ✅ Continue working with local data

## Next Steps

1. **Fix the corrupt index** using the repair steps above
2. **Choose a solution option** from above
3. **Implement the chosen solution**
4. **Test the connection** using the test script
5. **Verify the API** returns combined data
6. **Check the admin dashboard** shows all ratings
