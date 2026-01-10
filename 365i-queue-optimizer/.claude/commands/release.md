# Create Release Archive

Create a release-ready ZIP archive for WordPress plugin distribution.

## Instructions

### Step 1: Determine Plugin Details
Read the main plugin file to extract:
- **Plugin slug**: The directory/file name (e.g., `365i-queue-optimizer`)
- **Version**: From the `Version:` header in the main plugin file

### Step 2: Create Release Directory
Ensure the `releases/` directory exists (it should be in .gitignore).

### Step 3: Build Archive with 7-Zip

**CRITICAL**: Use 7-Zip (`7z` command) only. Never use built-in zip utilities.

Archive naming: `{plugin-slug}-{VERSION}.zip`
Example: `365i-queue-optimizer-1.2.3.zip`

Archive structure must extract to: `{plugin-slug}/` (fixed name, NO version number)
This ensures WordPress properly overwrites old versions on upload.

### Step 4: Exclude Development Files

Never include these in the release:
- `.git/` and `.gitignore`, `.gitattributes`
- `.claude/` directory
- `node_modules/`
- `vendor/` (unless production dependencies)
- `.vscode/`, `.idea/`
- `*.log`, `*.map`
- `composer.json`, `composer.lock` (unless needed)
- `package.json`, `package-lock.json`
- `phpcs.xml`, `phpunit.xml`
- `tests/` directory
- `releases/` directory
- Any `.md` files except README.md if needed

### Step 5: 7-Zip Command

Use this pattern:
```bash
cd /path/to/plugin
7z a -tzip "../releases/plugin-slug-VERSION.zip" . -xr!.git -xr!.claude -xr!node_modules -xr!vendor -xr!releases -xr!.gitignore -xr!.gitattributes -xr!*.log
```

Or create a temporary directory with the correct name:
```bash
mkdir -p /tmp/plugin-slug
cp -r . /tmp/plugin-slug/ --exclude=.git --exclude=.claude ...
cd /tmp
7z a -tzip "/path/to/releases/plugin-slug-VERSION.zip" plugin-slug/
rm -rf /tmp/plugin-slug
```

### Step 6: Verify Archive

After creation:
1. List archive contents to verify structure
2. Confirm top-level folder is plugin-slug (not plugin-slug-version)
3. Confirm no development files included
4. Report file size

## Example Output

```
Created: releases/365i-queue-optimizer-2.0.5.zip
Size: 45.2 KB
Structure: 365i-queue-optimizer/
  ├── 365i-queue-optimizer.php
  ├── admin/
  ├── includes/
  ├── public/
  └── README.txt
```

## Why This Matters

WordPress expects the ZIP to contain a single folder matching the plugin slug. When users upload a new version:
- If folder is `plugin-slug/` → WordPress overwrites correctly
- If folder is `plugin-slug-1.2.3/` → Creates duplicate, breaks updates

Always use the fixed plugin slug folder name, never include version in the folder.
