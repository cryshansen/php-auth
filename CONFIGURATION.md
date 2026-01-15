# PHP Auth Setup - Configuration

## Secure Configuration Management

Your application should never commit credentials to version control. Here's the recommended approach:

### Option 1: config.php (Recommended for Development)

**Files:**
- `inc/config.example.php` - Template with placeholder values (SAFE TO COMMIT)
- `inc/config.php` - Your actual credentials (in .gitignore, NOT committed)

**Setup:**
```bash
# Copy the example
cp inc/config.example.php inc/config.php

# Edit with your credentials
nano inc/config.php
```

**In .gitignore:**
```
inc/config.php
```

### Option 2: Environment Variables (Recommended for Production)

**Files:**
- `.env.example` - Template with placeholder values (SAFE TO COMMIT)
- `.env` - Your actual credentials (in .gitignore, NOT committed)

**Setup:**
```bash
# Copy the example
cp .env.example .env

# Edit with your credentials
nano .env
```

**In .gitignore:**
```
.env
.env.local
```

**Load in your app:**
```php
// In inc/bootstrap.php
if (file_exists(__DIR__ . '/.env')) {
    $env = parse_ini_file(__DIR__ . '/.env');
    foreach ($env as $key => $value) {
        putenv("$key=$value");
    }
}
```

### Option 3: Docker / Container Secrets (Recommended for Production)

Set environment variables at container runtime:
```bash
docker run -e DB_USERNAME=user -e DB_PASSWORD=secret myapp
```

Or in docker-compose.yml:
```yaml
services:
  app:
    environment:
      DB_HOST: mysql
      DB_USERNAME: ${DB_USERNAME}
      DB_PASSWORD: ${DB_PASSWORD}
```

## Best Practices

✅ **DO:**
- Store credentials in environment variables
- Use `config.example.php` or `.env.example` as templates
- Add config files to `.gitignore`
- Use different credentials for dev/staging/production
- Rotate credentials if accidentally exposed

❌ **DON'T:**
- Commit config files with real credentials
- Hardcode credentials in code
- Share `.env` or `config.php` files
- Use same credentials across environments
- Store credentials in comments

## If You Accidentally Committed Credentials

1. **Immediately rotate the passwords** in your database/services
2. **Remove from git history:**
   ```bash
   # Remove file from history
   git filter-branch --tree-filter 'rm -f inc/config.php' HEAD
   
   # Or use git-filter-repo (better)
   git filter-repo --path inc/config.php --invert-paths
   
   # Force push (careful!)
   git push origin HEAD --force-with-lease
   ```
3. **Check if credentials were exposed** on public repos
4. **Add to .gitignore** to prevent future commits

## Your Current Setup

```
inc/
├── config.example.php  ← Template (COMMITTED)
└── config.php          ← Your credentials (IGNORED)

.env.example            ← Template (COMMITTED)
.env                    ← Your credentials (IGNORED)
```

### Current .gitignore protections:
```
inc/config.php
.env
.env.local
```

All sensitive files are protected! ✅

## Development Workflow

```bash
# 1. Clone repo
git clone repo-url

# 2. Set up config (choose one option)
cp inc/config.example.php inc/config.php
# Edit config.php with YOUR credentials

# 3. Set up database
bash setup-database.sh

# 4. Start developing
php -S localhost:8000
```

## GitHub Actions & CI/CD

For CI/CD pipelines, set secrets in GitHub:

1. Go to **Settings → Secrets and variables → Actions**
2. Add these secrets:
   - `DB_HOST`
   - `DB_USERNAME`
   - `DB_PASSWORD`
   - `DB_DATABASE_NAME`

3. Use in workflow:
```yaml
jobs:
  test:
    env:
      DB_HOST: ${{ secrets.DB_HOST }}
      DB_USERNAME: ${{ secrets.DB_USERNAME }}
      DB_PASSWORD: ${{ secrets.DB_PASSWORD }}
```

Your secrets are never exposed in logs! ✅

## Questions?

See [CODE_POLICY.md](CODE_POLICY.md) for security guidelines.
