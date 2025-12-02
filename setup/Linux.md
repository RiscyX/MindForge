## Linux setup (LAMPP)

Assuming LAMPP is installed in `/opt/lampp`:

### 0. Clone the repo to lampp htdocs

```bash
    cd /opt/lampp/htdocs
    sudo git clone https://github.com/RiscyX/MindForge.git
    sudo chown -R $USER:$USER MindForge
```


### 1. Database setup

```bash
    sudo /opt/lampp/lampp start
```

-- Open phpMyAdmin:
[PHPMyAdmin in localhost](https://localhost/phpmyadmin)

-- Create a new database, for example:

```text
       CREATE DATABASE mindforge CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

-- Import the initial schema if provided (e.g. database/schema.sql).

### 2. Environment variables

1. Create a new .env file in /config (use the keys from [This file](/config/.env.example))

### 3. Install PHP dependencies

```bash
    cd /opt/lampp/htdocs/MindForge
    composer install
```

### 4. Install precommit hook for clean coding.

Copy the pre-commit hook into Git’s hook directory:

```bash
    cp setup/pre-commit .git/hooks/pre-commit
```

Make sure the script is executable:

```bash
    chmod +x .git/hooks/pre-commit
```

After copying, the hook will run automatically on every git commit.

### 5. OPTIONAL: Setup Vhost for Apache on Linux

[Linux help for Apache Vhost](https://stackoverflow.com/questions/10878284/virtual-hosts-xampp-linux-ubuntu-not-working)


