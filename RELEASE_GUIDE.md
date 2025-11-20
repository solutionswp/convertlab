# ConvertLab Release Guide

This guide explains how to create releases for the ConvertLab WordPress plugin.

## Understanding Git Tags and Releases

### What is a Git Tag?
A **Git tag** is a reference to a specific commit in your repository. It's like a bookmark that marks important milestones (like version 1.0.0, 1.1.0, etc.).

### What is a GitHub Release?
A **GitHub Release** is a way to package and distribute your plugin. It includes:
- A downloadable ZIP file
- Release notes
- Links to the source code

## Step-by-Step Release Process

### Step 1: Prepare Your Code

Before creating a release, make sure:
- ✅ All code is committed and pushed to GitHub
- ✅ Version number is updated in `convertlab.php`
- ✅ Changelog is updated in `changelog.txt`
- ✅ Code is tested and working

### Step 2: Update Version Number

Edit `convertlab.php` and update the version:
```php
define( 'CONVERTLAB_VERSION', '1.0.1' ); // Update this
```

Also update the plugin header:
```php
 * Version: 1.0.1
```

### Step 3: Update Changelog

Edit `changelog.txt` and add your new version entry at the top:
```
== 1.0.1 - 2024-01-XX ==
* Fixed bug in popup rendering
* Added new feature X
* Improved performance
```

### Step 4: Commit and Push Changes

```bash
git add convertlab.php changelog.txt
git commit -m "Prepare for v1.0.1 release"
git push origin main
```

### Step 5: Create Git Tag

Create an annotated tag (recommended for releases):
```bash
git tag -a v1.0.1 -m "ConvertLab v1.0.1

- Fixed bug in popup rendering
- Added new feature X
- Improved performance"
```

**Tag naming convention:**
- Use semantic versioning: `v1.0.0`, `v1.0.1`, `v1.1.0`, `v2.0.0`
- Always prefix with `v`
- Follow MAJOR.MINOR.PATCH format

### Step 6: Push Tag to GitHub

```bash
git push origin v1.0.1
```

This triggers the GitHub Actions workflow (if configured) to:
- Create a plugin ZIP file
- Upload it as a release asset
- Generate update.json

### Step 7: Create GitHub Release (Manual Method)

If you prefer to create the release manually:

1. Go to: https://github.com/solutionswp/convertlab/releases/new
2. **Tag**: Select `v1.0.1` (or create new tag)
3. **Title**: `ConvertLab v1.0.1`
4. **Description**: Copy from your changelog
5. **Attach files**: Upload the plugin ZIP (if not auto-generated)
6. Click **"Publish release"**

### Step 8: Verify Release

1. Check the releases page: https://github.com/solutionswp/convertlab/releases
2. Verify the ZIP file is downloadable
3. Test the update mechanism in WordPress

## Understanding Semantic Versioning

- **MAJOR** (1.0.0 → 2.0.0): Breaking changes
- **MINOR** (1.0.0 → 1.1.0): New features (backward compatible)
- **PATCH** (1.0.0 → 1.0.1): Bug fixes

## Quick Reference Commands

```bash
# Create and push a new release tag
git tag -a v1.0.1 -m "Release message"
git push origin v1.0.1

# List all tags
git tag

# Delete a tag (if needed)
git tag -d v1.0.1
git push origin --delete v1.0.1

# View tag details
git show v1.0.1
```

## GitHub Actions Workflow

The `.github/workflows/build.yml` file automatically:
1. Detects when you push a tag starting with `v`
2. Creates a plugin ZIP file
3. Creates a GitHub release
4. Uploads the ZIP as a release asset
5. Generates `update.json` for automatic updates

## Update Mechanism

When users install your plugin:
1. They configure the `update.json` URL in Settings
2. Plugin checks this URL periodically
3. Compares version numbers
4. Shows update notification if new version available
5. User clicks "Update" and WordPress downloads from GitHub

## Best Practices

1. **Always test before releasing** - Test on a staging site
2. **Update changelog** - Users need to know what changed
3. **Use descriptive commit messages** - Makes history clear
4. **Tag every release** - Makes it easy to track versions
5. **Keep releases focused** - One major feature or bug fix per release

## Troubleshooting

**Tag not showing on GitHub?**
- Make sure you pushed the tag: `git push origin v1.0.1`

**GitHub Actions not running?**
- Check if workflow file exists: `.github/workflows/build.yml`
- Verify the workflow is enabled in repository settings

**Update not showing in WordPress?**
- Verify `update.json` URL is correct in Settings
- Check that `update.json` is accessible (visit URL in browser)
- Ensure version number in `update.json` is higher than current

## Next Steps After Release

1. Announce the release (if applicable)
2. Monitor for issues
3. Update documentation if needed
4. Plan next version features

---

**Remember**: Creating a release is a commitment. Make sure your code is stable and tested!

