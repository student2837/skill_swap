# MySQL Setup Guide for SkillSwap

## Quick Setup

### Step 1: Start MySQL Server

```bash
# Start MySQL service
brew services start mysql

# Or if that doesn't work, start manually:
mysqld_safe --user=mysql &
```

### Step 2: Create Database

```bash
# Connect to MySQL (use -p if you have a password)
mysql -u root

# Create the database
CREATE DATABASE skill_swap CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

### Step 3: Update .env File

Edit your `.env` file in the `backend` directory and update these lines:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=skill_swap
DB_USERNAME=root
DB_PASSWORD=          # Leave empty if no password, or add your password
```

### Step 4: Run Migrations

```bash
cd /Users/fdfvxvcx/Desktop/skill_swap/backend
php artisan migrate
```

### Step 5: Verify Connection

```bash
php artisan db:show
```

## Alternative: Use Setup Script

Run the automated setup script:

```bash
cd /Users/fdfvxvcx/Desktop/skill_swap/backend
./setup_mysql.sh
```

## Troubleshooting

### MySQL Not Starting
```bash
# Check if MySQL is running
ps aux | grep mysql

# Check MySQL logs
tail -f /usr/local/var/mysql/*.err
# or
tail -f /opt/homebrew/var/mysql/*.err
```

### Connection Refused
- Make sure MySQL server is running
- Check if MySQL is listening on port 3306: `lsof -i :3306`

### Access Denied
- Reset MySQL root password if needed
- Or create a new user:
```sql
CREATE USER 'skillswap'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON skill_swap.* TO 'skillswap'@'localhost';
FLUSH PRIVILEGES;
```

Then update `.env`:
```env
DB_USERNAME=skillswap
DB_PASSWORD=your_password
```

## Migrating Data from SQLite (Optional)

If you want to migrate existing data from SQLite to MySQL:

1. Export SQLite data:
```bash
sqlite3 database/database.sqlite .dump > sqlite_dump.sql
```

2. Convert the dump file (may need manual editing for MySQL compatibility)

3. Import to MySQL:
```bash
mysql -u root skill_swap < sqlite_dump.sql
```

**Note:** This is complex and may require manual adjustments. Consider starting fresh with migrations if you don't have critical data.
