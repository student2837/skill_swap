#!/bin/bash

# MySQL Setup Script for Laravel SkillSwap
# Run this script to set up MySQL for your Laravel application

echo "🚀 Setting up MySQL for SkillSwap..."
echo ""

# Check if MySQL is installed
if ! command -v mysql &> /dev/null; then
    echo "❌ MySQL is not installed. Please install it first:"
    echo "   brew install mysql"
    exit 1
fi

echo "✅ MySQL is installed"

# Start MySQL service
echo ""
echo "📦 Starting MySQL service..."
brew services start mysql || mysqld_safe --user=mysql &

# Wait for MySQL to start
echo "⏳ Waiting for MySQL to start..."
sleep 5

# Check MySQL connection
echo ""
echo "🔌 Testing MySQL connection..."
if mysql -u root -e "SELECT 1;" &> /dev/null; then
    echo "✅ MySQL is running"
else
    echo "⚠️  MySQL connection failed. You may need to:"
    echo "   1. Set a root password: mysql_secure_installation"
    echo "   2. Or use: mysql -u root -p"
    echo ""
    read -p "Enter MySQL root password (or press Enter if no password): " MYSQL_PASSWORD
    if [ -z "$MYSQL_PASSWORD" ]; then
        MYSQL_CMD="mysql -u root"
    else
        MYSQL_CMD="mysql -u root -p$MYSQL_PASSWORD"
    fi
fi

# Create database
DB_NAME="skill_swap"
echo ""
echo "📊 Creating database: $DB_NAME"
$MYSQL_CMD -e "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>&1

if [ $? -eq 0 ]; then
    echo "✅ Database '$DB_NAME' created successfully"
else
    echo "❌ Failed to create database. Please create it manually:"
    echo "   mysql -u root -e \"CREATE DATABASE $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\""
    exit 1
fi

echo ""
echo "✅ MySQL setup complete!"
echo ""
echo "📝 Next steps:"
echo "   1. Update your .env file with MySQL settings:"
echo "      DB_CONNECTION=mysql"
echo "      DB_HOST=127.0.0.1"
echo "      DB_PORT=3306"
echo "      DB_DATABASE=$DB_NAME"
echo "      DB_USERNAME=root"
echo "      DB_PASSWORD="
echo ""
echo "   2. Run migrations:"
echo "      php artisan migrate"
echo ""
