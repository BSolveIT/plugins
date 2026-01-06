# Create Release Archive

Create a WordPress plugin release archive using 7-Zip.

## Instructions

### Step 1: Determine Version

Read the plugin header from the main plugin file to get the current version:
- Look for `Version:` in the plugin header comment
- Use this version for the archive name

### Step 2: Verify Plugin Slug

The plugin slug is the directory name: `365i-environment-indicator`
- Archive name format: `365i-environment-indicator-VERSION.zip`
- Archive extracts to: `365i-environment-indicator/` (fixed, no version)

### Step 3: Create Archive with 7-Zip

Use the 7z command to create the archive:

```bash
cd "e:/Development" && 7z a -tzip "365i-environment-indicator-VERSION.zip" "./365i-environment-indicator" \
  "-x!365i-environment-indicator/.git" \
  "-x!365i-environment-indicator/.claude" \
  "-x!365i-environment-indicator/*.zip" \
  "-x!365i-environment-indicator/specification.md" \
  "-x!365i-environment-indicator/.gitattributes" \
  "-x!365i-environment-indicator/.gitignore" \
  "-x!365i-environment-indicator/node_modules" \
  "-x!365i-environment-indicator/vendor" \
  "-x!365i-environment-indicator/.env" \
  "-x!365i-environment-indicator/*.log" \
  "-x!365i-environment-indicator/tests" \
  "-x!365i-environment-indicator/phpunit.xml" \
  "-x!365i-environment-indicator/.phpcs.xml" \
  "-x!365i-environment-indicator/composer.json" \
  "-x!365i-environment-indicator/composer.lock" \
  "-x!365i-environment-indicator/package.json" \
  "-x!365i-environment-indicator/package-lock.json" \
  "-x!365i-environment-indicator/CLAUDE.md"
```

### Step 4: Verify Archive Contents

List the archive to verify:
- No hidden files (.git*, etc.)
- No development files (tests, composer, etc.)
- No CLAUDE.md (not allowed by WordPress.org)
- Correct folder structure (365i-environment-indicator/)

```bash
7z l "e:/Development/365i-environment-indicator-VERSION.zip"
```

### Step 5: Report

Output:
- Archive location and size
- List of included files
- Confirmation that archive extracts to correct folder
- Reminder about WordPress.org upload requirements

## CRITICAL: Archive Structure

The archive MUST:
1. Use 7-Zip (`7z` command) - NOT zip or other tools
2. Name format: `plugin-slug-VERSION.zip`
3. Extract to: `plugin-slug/` (fixed name, no version in folder)
4. Exclude ALL development files

This ensures WordPress will properly overwrite the previous version when the user uploads the update.

## Files to ALWAYS Exclude

Development:
- `.git/`, `.gitignore`, `.gitattributes`
- `.claude/`
- `node_modules/`, `vendor/`
- `tests/`, `phpunit.xml`, `.phpcs.xml`
- `composer.json`, `composer.lock`
- `package.json`, `package-lock.json`

Documentation (non-standard):
- `CLAUDE.md` (WordPress.org only allows README.md, CHANGELOG.md, LICENSE.md)
- `specification.md`

Other:
- `*.zip` (existing archives)
- `*.log`
- `.env`
