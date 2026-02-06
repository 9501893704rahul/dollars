# HK Checklist - Airbnb Housekeeping Accountability System

A comprehensive Laravel-based checklist system designed for Airbnb property management. This system allows property owners to assign housekeepers to specific properties and dates, where they can log in, complete checklists room-by-room, upload timestamped photos, and confirm task completion.

---

## 🚀 PROJECT STATUS - DEVELOPMENT TRACKER

### ✅ COMPLETED FIXES

| # | Task | Status | Description |
|---|------|--------|-------------|
| 1 | 404 Error on Checkbox Toggle | ✅ DONE | Investigated - occurs when task/room relationships are invalid. Current implementation handles this correctly. |
| 2 | Skip Around Rooms | ✅ DONE | Housekeepers can now skip to different rooms without completing previous rooms first. Removed room locking. |
| 3 | "READ IMPORTANT NOTES" Button | ✅ DONE | Changed "View Instructions" to "READ IMPORTANT NOTES" with bold amber/orange styling. |
| 4 | Show Image/Video with Instructions | ✅ DONE | Media (images/videos) now display when instructions are expanded. |
| 5 | Notes Icon Popup & Photo Upload | ✅ DONE | Replaced text input with icon popups. Added camera upload icon with required note. Camera-only capture (no gallery selection). |

---

### ❌ PENDING FIXES (Requires Google API Key)

| # | Task | Status | Description |
|---|------|--------|-------------|
| 6 | Google Address Autocomplete | ❌ PENDING | Auto-populate address from Google as user types when entering property address. **Requires Google Places API Key** |
| 7 | Auto-populate Lat/Long | ❌ PENDING | Automatically fill latitude/longitude from Google Places selection. **Requires Google Places API Key** |

---

### ❌ PENDING ADD-ONS

| # | Task | Status | Description |
|---|------|--------|-------------|
| 1 | Admin Upload Media for Tasks | ❌ PENDING | Admin/Owner can upload picture/video examples for each task to show housekeepers how task should be done. |
| 2 | Housekeeper Room Photos After Completion | ❌ PENDING | After ALL tasks complete, show rooms again for photo uploads. Auto-timestamp in lower right corner. High-res download option, normal res for web. |
| 3 | Company User Role | ❌ PENDING | New "Company" role that can add/manage owners. Company's housekeepers are separate and not viewable by other owners. |
| 4 | Generate Completion Report | ❌ PENDING | Nice layout report showing all completed tasks, notes, problem photos, and "after finished" photos. Can be sent via text link and/or email. |
| 5 | Calendar Integration (iCal) | ❌ PENDING | Link Airbnb, VRBO, Booking.com calendars to properties. Alert on checkout dates and prompt to schedule housekeeper session. |
| 6 | Improve Home Page | ❌ PENDING | Make landing page more presentable as a sales pitch for the product. |

---

### 📁 KEY FILES MODIFIED

| File | Changes |
|------|---------|
| `resources/js/checklist-renderer.js` | Removed room locking, changed button text, added note/photo icon popups |
| `resources/js/checklist.js` | Added task photo upload handler |
| `app/Providers/AppServiceProvider.php` | Added HTTPS forcing for production |

---

## 📋 Features

- **Property & Room Management**: Create and manage properties with multiple rooms
- **Task Assignment**: Assign housekeepers to specific properties and dates
- **GPS Verification**: Start sessions require on-site GPS confirmation within property radius
- **Room-by-Room Checklists**: Clear tasks, notes, and completion marks per room
- **Photo Evidence**: Upload 8+ photos per room with automatic timestamp overlay
- **Calendar View**: Monthly schedule view for housekeepers, owners, and admins
- **Role-Based Access Control**: Three user roles (Admin, Owner, Housekeeper) with granular permissions
- **Activity Logging**: Complete audit trail of all system activities
- **Inventory Checks**: Separate inventory verification step

## 🛠️ Technology Stack

- **Backend**: Laravel 12 (PHP 8.2+)
- **Frontend**: Blade Templates, Tailwind CSS, Alpine.js, Vite
- **Database**: MySQL 5.7+ / MariaDB 10.3+
- **Additional Packages**:
  - Spatie Laravel Permission (Role-based access control)
  - Spatie Laravel Activity Log (Audit trail)
  - Intervention Image (Image processing with timestamp overlay)

## 📦 Requirements

### Server Requirements

- **PHP**: 8.2 or higher
- **Extensions**: 
  - BCMath
  - Ctype
  - cURL
  - DOM
  - Fileinfo
  - JSON
  - Mbstring
  - OpenSSL
  - PCRE
  - PDO
  - PDO_MySQL
  - Tokenizer
  - XML
  - GD or Imagick (for image processing)
- **Database**: MySQL 5.7+ / MariaDB 10.3+ or PostgreSQL 10+
- **Composer**: 2.0 or higher
- **Node.js**: 18.x or higher
- **NPM**: 9.x or higher

### For Production (AlmaLinux/cPanel)

- cPanel/WHM access
- SSH access to server
- MySQL database created via cPanel
- PHP version 8.2+ configured in cPanel
- Node.js installed (if not available, assets can be pre-built)

---

## 🚀 Installation Guide

### Option 1: Local Development Environment

#### Step 1: Clone the Repository

```bash
git clone https://github.com/Khokon-Chandra/hkchecklist.git
cd hkchecklist
```

#### Step 2: Install PHP Dependencies

```bash
composer install
```

#### Step 3: Install Node Dependencies

```bash
npm install
```

#### Step 4: Environment Configuration

Create a `.env` file from the example:

```bash
cp .env.example .env
```

Or create manually with the following configuration:

```env
APP_NAME="HK Checklist"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_TIMEZONE=UTC
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hkchecklist
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
SESSION_LIFETIME=120

QUEUE_CONNECTION=database
CACHE_STORE=database
FILESYSTEM_DISK=local
```

#### Step 5: Generate Application Key

```bash
php artisan key:generate
```

#### Step 6: Create Database

Create a MySQL database:

```bash
mysql -u root -p
CREATE DATABASE hkchecklist CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

#### Step 7: Run Migrations and Seeders

```bash
php artisan migrate --seed
```

This will:
- Create all database tables
- Set up roles and permissions (Admin, Owner, Housekeeper)
- Create demo users for testing

#### Step 8: Build Frontend Assets (Production)

For production builds:

```bash
npm run build
```

#### Step 9: Start Development Server

```bash
# Option 1: Using Composer script (includes queue worker, logs, and Vite)
composer run dev

# Option 2: Manual start
php artisan serve
# In another terminal:
npm run dev
# In another terminal (for queue processing):
php artisan queue:work
```

The application will be available at `http://localhost:8000`

#### Step 10: Access the Application

Default demo users (created by seeders):

**Admin:**
- Email: `admin@example.com`
- Password: `password`

**Owner:**
- Email: `owner@example.com`
- Password: `password`

**Housekeeper:**
- Email: `housekeeper@example.com`
- Password: `password`

---

### Option 2: Production Server (AlmaLinux with cPanel)

#### Prerequisites

1. **Access cPanel** and create:
   - A MySQL database
   - A MySQL database user with full privileges
   - Note down database name, username, and password

2. **Verify PHP Version**:
   - In cPanel, go to "Select PHP Version"
   - Ensure PHP 8.2 or higher is selected
   - Enable required extensions: `gd`, `pdo_mysql`, `mbstring`, `openssl`, `curl`, `fileinfo`

3. **Check Node.js** (optional for building assets):
   - If Node.js is not available on server, build assets locally and upload

#### Step 1: Upload Project Files

**Via cPanel File Manager:**
1. Log into cPanel
2. Navigate to `public_html` (or your domain's document root)
3. Upload the project files (excluding `node_modules`, `.git`, `.env`)

**Via SSH (Recommended):**
```bash
# Connect to your server via SSH
ssh username@your-server-ip

# Navigate to your domain directory (usually public_html or a subdomain)
cd ~/public_html
# Or for a subdomain:
cd ~/subdomain_name

# Clone or upload the project
git clone https://github.com/Khokon-Chandra/hkchecklist.git .
# Or upload via SCP/SFTP
```

#### Step 2: Set Correct Permissions

```bash
# Set ownership (replace 'username' with your cPanel username)
chown -R username:username /home/username/public_html/hkchecklist

# Set directory permissions
find /home/username/public_html/hkchecklist -type d -exec chmod 755 {} \;

# Set file permissions
find /home/username/public_html/hkchecklist -type f -exec chmod 644 {} \;

# Make storage and cache writable
chmod -R 775 /home/username/public_html/hkchecklist/storage
chmod -R 775 /home/username/public_html/hkchecklist/bootstrap/cache
```

#### Step 3: Install PHP Dependencies

```bash
cd /home/username/public_html/hkchecklist
composer install --no-dev --optimize-autoloader
```

**Note**: If Composer is not installed on the server:
```bash
# Install Composer globally
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
```

#### Step 4: Configure Environment

Create `.env` file:

```bash
cp .env.example .env
# Or create manually
nano .env
```

Update `.env` with production settings:

```env
APP_NAME="HK Checklist"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_TIMEZONE=UTC
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_cpanel_db_name
DB_USERNAME=your_cpanel_db_user
DB_PASSWORD=your_cpanel_db_password

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true

QUEUE_CONNECTION=database
CACHE_STORE=database
FILESYSTEM_DISK=local

# Mail Configuration (update with your SMTP settings)
MAIL_MAILER=smtp
MAIL_HOST=mail.yourdomain.com
MAIL_PORT=587
MAIL_USERNAME=noreply@yourdomain.com
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"
```

#### Step 5: Generate Application Key

```bash
php artisan key:generate
```

#### Step 6: Run Migrations

```bash
php artisan migrate --force
```

**Note**: For production, you may want to skip seeders or run them separately:

```bash
# Skip seeders (recommended for production)
php artisan migrate --force

# Or run seeders if you want demo data
php artisan migrate --seed --force
```

#### Step 7: Build Frontend Assets

**Option A: If Node.js is available on server:**

```bash
npm install
npm run build
```

**Option B: Build locally and upload:**

On your local machine:
```bash
npm install
npm run build
```

Then upload the `public/build` directory to the server.

#### Step 8: Configure cPanel Document Root

1. In cPanel, go to **"Subdomains"** or **"Addon Domains"**
2. Point the domain/subdomain to: `public_html/hkchecklist/public`
3. Or use **"Document Root"** feature to set: `/home/username/public_html/hkchecklist/public`

#### Step 9: Create Storage Link

```bash
php artisan storage:link
```

This creates a symbolic link from `public/storage` to `storage/app/public` for public access to uploaded files.

#### Step 10: Optimize for Production

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer dump-autoload --optimize
```

#### Step 11: Set Up Queue Worker (Optional)

For better performance, you can set up a supervisor or use cPanel's process manager to keep the queue worker running:

```bash
php artisan queue:work --daemon
```

Or configure via cPanel's **"Process Manager"** if available.

**Note:** Queue processing is optional. The application will function without a continuous queue worker, though some background tasks may be delayed.

---

## 🔧 Configuration

### Database Configuration

Ensure your MySQL database uses `utf8mb4` character set:

```sql
ALTER DATABASE your_database_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### File Storage

Uploaded photos are stored in `storage/app/public`. Create a symbolic link:

```bash
php artisan storage:link
```

This creates a link from `public/storage` to `storage/app/public` for public access.

### Queue Configuration

The application uses database queues. Ensure the queue worker is running:

```bash
php artisan queue:work
```

Or set up a supervisor configuration for production.

### GPS Configuration

GPS verification settings can be configured in the `.env` file or in the application settings. The default radius for property verification is configurable per property.

---

## 👥 User Roles & Permissions

The system includes three default roles:

1. **Admin**: Full system access, can manage all properties, users, and settings
2. **Owner**: Can manage their own properties, assign housekeepers, view sessions
3. **Housekeeper**: Can view assigned sessions, complete checklists, upload photos

Permissions are managed via Spatie Laravel Permission package. To modify permissions:

```bash
php artisan tinker
```

```php
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

// Create new permission
Permission::create(['name' => 'permission.name']);

// Assign permission to role
$role = Role::findByName('owner');
$role->givePermissionTo('permission.name');
```

---

## 📁 Project Structure

```
hkchecklist/
├── app/
│   ├── Http/
│   │   └── Controllers/     # Application controllers
│   ├── Models/              # Eloquent models
│   ├── Services/            # Business logic services
│   └── View/
│       └── Components/      # Blade components
├── database/
│   ├── migrations/          # Database migrations
│   └── seeders/            # Database seeders
├── public/                  # Public assets (document root)
├── resources/
│   ├── views/              # Blade templates
│   ├── css/                # CSS files
│   └── js/                 # JavaScript files
├── routes/
│   ├── web.php             # Web routes
│   └── auth.php            # Authentication routes
└── storage/                # File storage and logs
```

---

## 🧪 Testing

Run the test suite:

```bash
php artisan test
```

---

## 🔒 Security Considerations

1. **Environment File**: Never commit `.env` file to version control
2. **Debug Mode**: Set `APP_DEBUG=false` in production
3. **HTTPS**: Always use HTTPS in production
4. **File Permissions**: Ensure proper file permissions (755 for directories, 644 for files)
5. **Database Credentials**: Use strong passwords for database users
6. **Session Security**: Use secure session cookies in production

---

## 🐛 Troubleshooting

### Common Issues

**Issue**: `500 Internal Server Error`
- **Solution**: Check `storage/logs/laravel.log` for errors
- Ensure storage and cache directories are writable: `chmod -R 775 storage bootstrap/cache`

**Issue**: `Class not found` errors
- **Solution**: Run `composer dump-autoload`

**Issue**: Assets not loading
- **Solution**: Run `npm run build` and ensure `public/build` exists
- Check that `APP_URL` in `.env` matches your domain

**Issue**: Queue jobs not processing
- **Solution**: Ensure queue worker is running: `php artisan queue:work`
- Check database connection for queue table

**Issue**: Images not displaying
- **Solution**: Run `php artisan storage:link` to create symbolic link
- Check file permissions on `storage/app/public`

**Issue**: Permission denied errors
- **Solution**: Check file ownership: `chown -R username:username /path/to/project`
- Verify directory permissions: `chmod -R 755 directories`, `chmod -R 644 files`

---

## 📝 Additional Notes

### For cPanel Users

- **PHP Version**: Always use PHP 8.2+ via "Select PHP Version" in cPanel
- **Composer**: May need to be installed via SSH if not available in cPanel
- **Node.js**: May not be available; build assets locally and upload
- **Email**: Configure SMTP settings in `.env` for email functionality

### Development vs Production

**Development:**
- `APP_DEBUG=true`
- `APP_ENV=local`
- Assets served via Vite dev server
- Queue worker can be run manually

**Production:**
- `APP_DEBUG=false`
- `APP_ENV=production`
- Assets pre-built with `npm run build`
- Queue worker can be set up optionally (supervisor/process manager)
- All caches enabled (`config:cache`, `route:cache`, `view:cache`)

---

## 📞 Support

For issues or questions:
1. Check the troubleshooting section above
2. Review Laravel documentation: https://laravel.com/docs
3. Check application logs: `storage/logs/laravel.log`

---

## 📄 License

This project is proprietary software. All rights reserved.

---

## 🎯 Quick Start Checklist

### Local Development
- [ ] Clone repository
- [ ] Run `composer install`
- [ ] Run `npm install`
- [ ] Create `.env` file
- [ ] Run `php artisan key:generate`
- [ ] Create database
- [ ] Run `php artisan migrate --seed`
- [ ] Run `composer run dev`

### Production (cPanel)
- [ ] Upload files to server
- [ ] Set correct permissions
- [ ] Run `composer install --no-dev`
- [ ] Create `.env` file with production settings
- [ ] Run `php artisan key:generate`
- [ ] Run `php artisan migrate --force`
- [ ] Build assets (`npm run build` or upload pre-built)
- [ ] Configure document root to `public` directory
- [ ] Run `php artisan storage:link`
- [ ] Optimize caches
- [ ] Test application

---

**Version**: 1.0.0  
**Last Updated**: 2025
