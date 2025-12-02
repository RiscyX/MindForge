## Windows setup (XAMPP)

Assuming XAMPP is installed in `C:\xampp`:

### 0. Clone the repo to xampp htdocs

```bash
    cd C:\xampp\htdocs
    git clone https://github.com/RiscyX/MindForge.git
```

### 1. Database setup
1. Create the database
2. Start Apache and MySQL services in XAMPP
3. Open phpMyAdmin [Your PHPMyAdmin](http://localhost/phpmyadmin).
4. Create a new database, for example:

```text
CREATE DATABASE mindforge CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```
Import the initial schema if provided (e.g. database/schema.sql).

### 2. Environment variables

1. Create a new .env file in /config (use the keys from [This file](/config/.env.example))

### 3. Install PHP dependencies

```bash
    cd C:\xampp\htdocs\MindForge
    composer install
```


### 4. Install precommit hook for clean coding.

```bash
    copy setup\pre-commit .git\hooks\pre-commit
```

-- If you are using Git Bash instead of CMD/PowerShell:

```bash
    cp scripts/pre-commit .git/hooks/pre-commit
```

**After copying, the hook will run automatically on every git commit.**

### 5. **OPTIONAL** Setup Vhost for apache on windows

**[Tutorial](https://stackoverflow.com/questions/2658173/set-up-apache-virtualhost-on-windows)**

