# ThemisDB Versioning & Release Strategy

**Version:** 1.0  
**Date:** 2. Mai 2026  
**Scope:** Plugin versioning, release cadence, compatibility tracking

---

## Version Format: Semantic Versioning

We follow [Semantic Versioning 2.0.0](https://semver.org/):

```
MAJOR.MINOR.PATCH[-PRERELEASE][+BUILD]

Example: 1.2.3-beta.1+build.123
         └ Patch:1.2.3
            └ Minor:1.2
               └ Major:1
```

| Component | When to Increment | Example |
|-----------|-------------------|---------|
| **MAJOR** | Breaking API changes, major refactor | 1.x.x → 2.0.0 |
| **MINOR** | New features, non-breaking changes | 1.2.x → 1.3.0 |
| **PATCH** | Bug fixes, security patches | 1.2.0 → 1.2.1 |
| **PRERELEASE** | Alpha/Beta/RC versions | 1.2.0-beta.1 |
| **BUILD** | Build metadata (not version precedence) | 1.2.0+build.123 |

---

## Release Types & Cadence

### Patch Releases (As Needed)

**Format:** `v1.2.1`, `v1.2.2`, etc.

**When:**
- Critical security fixes
- High-priority bug fixes
- Minimal code changes
- No database migrations
- No feature additions

**Frequency:** Every 1-2 weeks (as needed)

**Process:**
```bash
# On develop branch
git checkout develop
git pull origin develop

# Fix bug
git fix/bug-name
git commit -m "fix: description"
git push

# Create release
git checkout main
git pull origin main
git merge --no-ff develop -m "Merge develop into main"
git tag -a v1.2.1 -m "Release v1.2.1: Bug fixes"
git push origin main --tags
```

### Minor Releases (Quarterly)

**Format:** `v1.3.0`, `v1.4.0`, etc.

**When:**
- New features added
- Non-breaking improvements
- Database schema changes (backward compatible)
- Performance optimizations

**Frequency:** Every 3 months (planned)

**Process:**
```bash
# Features developed on feature branches
git checkout -b feature/new-feature

# When ready for release
git checkout develop
git pull origin develop
git merge feature/new-feature -m "Add new feature"
git push

# Plan release
# - Write CHANGELOG
# - Update README
# - Update version in plugin file
# - Create release branch

git checkout -b release/v1.3.0
# Update version numbers
git commit -am "Release v1.3.0"
git push origin release/v1.3.0

# Create PR, review, merge
git checkout main
git merge release/v1.3.0
git tag -a v1.3.0 -m "Release v1.3.0"
git push origin main --tags
```

### Major Releases (Annual)

**Format:** `v2.0.0`, `v3.0.0`, etc.

**When:**
- Major breaking changes
- Complete rewrites
- Database migrations required
- Significant workflow changes
- Requires customer action/testing

**Frequency:** Annually (planned)

**Process:**
```bash
# Major feature work on develop
# 1-2 months of feature development

# Create release branch
git checkout -b release/v2.0.0

# Extensive testing
# - Create beta versions
git tag -a v2.0.0-beta.1 -m "v2.0.0 Beta 1"
git tag -a v2.0.0-rc.1 -m "v2.0.0 Release Candidate 1"

# Prepare migration guide
# Document breaking changes
# Create upgrade script

# Final release
git tag -a v2.0.0 -m "Release v2.0.0"
git push origin main --tags
```

### Security Releases (ASAP)

**Format:** `v1.2.3-sec` or `v1.2.4` (next patch)

**When:**
- Critical security vulnerability discovered
- Requires immediate deployment
- Other work on develop is irrelevant

**Process:**
```bash
# EMERGENCY: Branch from main only
git checkout main
git pull origin main
git checkout -b hotfix/security-issue

# Fix vulnerability
git commit -m "security: CVE-XXXX-XXXXX description"

# Test fix thoroughly
# Merge directly to main
git checkout main
git merge hotfix/security-issue

# Release immediately
git tag -a v1.2.4 -m "Security patch: CVE-XXXX-XXXXX"
git push origin main --tags

# Also merge back to develop
git checkout develop
git merge hotfix/security-issue
git push origin develop
```

---

## Version Numbering Examples

### themisdb-order-request

| Version | Type | Release Date | Notes |
|---------|------|--------------|-------|
| 1.0.0 | Initial Release | 2026-01-15 | First stable version |
| 1.0.1 | Patch | 2026-01-22 | Bug fix: email formatting |
| 1.1.0 | Minor | 2026-03-01 | New feature: B2B portal |
| 1.1.1 | Patch | 2026-03-10 | Security: XSS vulnerability |
| 1.2.0 | Minor | 2026-05-15 | Feature: Advanced reporting |
| 1.2.0-beta.1 | Beta | 2026-05-01 | Preview release |
| 1.2.0-rc.1 | Release Candidate | 2026-05-08 | Final testing |
| 2.0.0 | Major | 2027-01-01 | Complete rewrite |

---

## Compatibility Matrix

Track which versions work with which WordPress/PHP versions:

### themisdb-order-request

| Version | PHP Min | PHP Max | WordPress Min | WordPress Max | Notes |
|---------|---------|---------|---------------|---------------|-------|
| 1.0.0 | 7.2 | 8.1 | 5.0 | 6.0 | |
| 1.1.0 | 7.2 | 8.2 | 5.0 | 6.2 | Dropped PHP 7.2 in 1.2 |
| 1.2.0 | 7.4 | 8.3 | 5.0 | 6.4 | |
| 2.0.0 | 8.0 | 8.4 | 6.0 | 6.5 | Requires PHP 8.0+ |

**Update:** In plugin header and database

```php
<?php
/**
 * Plugin Name: ThemisDB Order Request
 * Requires PHP: 7.4
 * Requires WP: 5.0
 * Tested Up To: 6.4
 */
```

---

## Changelog Format

Keep changelog in Git tags and CHANGELOG.md:

**File:** `CHANGELOG.md`

```markdown
# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added
- New feature X
- New feature Y

### Changed
- Modified behavior of Z

### Fixed
- Fixed bug A
- Fixed bug B

### Deprecated
- Old API endpoint (use new endpoint instead)

### Removed
- Removed deprecated function

### Security
- Fixed security vulnerability CVE-XXXX-XXXXX

## [1.2.0] - 2026-05-15

### Added
- Advanced reporting dashboard
- Export to PDF functionality
- Custom report scheduling

### Fixed
- Email template rendering issue
- License validation edge case

### Breaking Changes
⚠️ None - backward compatible

## [1.1.0] - 2026-03-01

### Added
- B2B portal for bulk orders
- API for integrations

### Changed
- Improved order flow

## [1.0.1] - 2026-01-22

### Fixed
- Email formatting for HTML clients

## [1.0.0] - 2026-01-15

### Added
- Initial release
```

---

## Breaking Changes Policy

### What Counts as Breaking

❌ **Breaking:**
- Removing a public function
- Changing function signature (adding required parameters)
- Renaming database tables/columns
- Changing hook names
- Removing support for WordPress version

✅ **Not Breaking:**
- Adding optional function parameters
- Deprecating a function (with trigger_error)
- Adding new hooks
- Improving error handling

### Deprecation Process

1. **Version N:** Deprecate function
```php
function old_function() {
    _deprecated_function(__FUNCTION__, '1.2.0', 'new_function');
    return new_function();
}
```

2. **Version N+1:** Keep deprecated function
```php
// Still works, but generates warning
old_function();  // Warning: old_function is deprecated since 1.2.0
```

3. **Version N+2:** Remove function
```php
// Function completely removed
// Developers must migrate by now
```

---

## Pre-Release Process

### Beta Releases (v1.2.0-beta.1, beta.2, ...)

**When:** Major features ready for testing, but not production-ready

```bash
# Create beta tag
git tag -a v1.2.0-beta.1 -m "v1.2.0 Beta 1"
git push origin --tags

# GitHub Actions automatically:
# 1. Builds artifact
# 2. Uploads to GitHub Releases
# 3. Marks as "Pre-release"
```

**Testing:**
- Limited customer group (opt-in)
- Beta plugin portal
- 1-2 weeks of testing
- Collect feedback

### Release Candidates (v1.2.0-rc.1, rc.2, ...)

**When:** All features complete, only bug fixes remaining

```bash
# Create RC tag
git tag -a v1.2.0-rc.1 -m "v1.2.0 Release Candidate 1"
git push origin --tags
```

**Testing:**
- Broader customer group
- Production-like environment
- Production data testing
- 1 week minimum

### Final Release

```bash
# Create final tag (no pre-release suffix)
git tag -a v1.2.0 -m "Release v1.2.0"
git push origin --tags

# GitHub Actions:
# 1. Builds final artifact
# 2. Marks as "Latest Release"
# 3. Sends update notifications to customers
# 4. Enables auto-update
```

---

## Upgrade Paths & Compatibility

### Supported Upgrade Paths

```
1.0.x → 1.1.x ✅ (Minor upgrade)
1.1.x → 1.2.x ✅ (Minor upgrade)
1.0.x → 1.2.x ✅ (Skip versions OK for minor)
1.x.x → 2.0.0 ⚠️  (MAJOR - Requires migration)
2.0.0 → 1.x.x ❌  (Downgrades not supported)
```

### Database Migration Strategy

**For MINOR upgrades:**
```sql
-- Auto-applied on plugin activation
-- Uses WordPress dbDelta() function
-- Backward compatible

ALTER TABLE wp_themisdb_licenses
ADD COLUMN new_field VARCHAR(100) DEFAULT NULL;  -- Safe, adds column
```

**For MAJOR upgrades:**
```sql
-- May require manual intervention
-- Create migration script

-- Backup data first
-- Apply schema changes
-- Verify data integrity
-- Test thoroughly before production

-- Example: Rename table structure
CREATE TABLE wp_themisdb_licenses_v2 AS SELECT * FROM wp_themisdb_licenses;
ALTER TABLE wp_themisdb_licenses_v2 ...;  -- Apply changes
DROP TABLE wp_themisdb_licenses;
RENAME TABLE wp_themisdb_licenses_v2 TO wp_themisdb_licenses;
```

---

## Version Lifecycle

```
                  BETA PHASE (1-2 weeks)
                        ↓
        v1.2.0-beta.1 → beta.2 → beta.3
                        ↓
                  RC PHASE (1 week)
                        ↓
        v1.2.0-rc.1 → rc.2 → rc.3
                        ↓
                  RELEASE PHASE
                        ↓
                  v1.2.0 FINAL
                        ↓
        ACTIVE SUPPORT (6 months)
                        ↓
        SECURITY-ONLY (6 months)
                        ↓
                  EOL (Deprecated)
```

---

## Support Matrix

| Version | Release Date | Active Support Until | Security Support Until | Status |
|---------|--------------|----------------------|------------------------|--------|
| 1.0.x | 2026-01-15 | 2026-07-15 | 2027-01-15 | EOL |
| 1.1.x | 2026-03-01 | 2026-09-01 | 2027-03-01 | Security Only |
| 1.2.x | 2026-05-15 | 2026-11-15 | 2027-05-15 | Active |
| 2.0.x | 2027-01-01 | 2027-07-01 | 2028-01-01 | Future |

---

## Git Workflow

```
main branch (stable releases only)
├── v1.0.0 tag
├── v1.0.1 tag
├── v1.1.0 tag
└── v1.2.0 tag

develop branch (next release prep)
├── feature/new-feature
├── feature/another-feature
└── release/v1.3.0 (when ready)

hotfix branches (emergency fixes)
├── hotfix/security-issue
└── hotfix/critical-bug
```

**Branch Rules:**
- `main`: Tagged releases only, protected
- `develop`: Integration branch, pre-release
- `feature/*`: Feature development
- `hotfix/*`: Emergency fixes from `main`
- `release/*`: Release preparation

---

## Tools & Automation

### GitHub Actions Workflow

Automatically:
- Tests all commits
- Builds artifacts on tags
- Generates release notes
- Publishes to GitHub Releases
- Notifies customers of updates

### Release Notes Generation

Auto-generated from:
- Git commits (conventional commits)
- Closed issues
- Pull requests
- Tags

**Format:**
```
## 🎉 New Features
- Feature A
- Feature B

## 🐛 Bug Fixes
- Fixed bug X
- Fixed bug Y

## ⚠️ Breaking Changes
- None

## 📦 Download
https://github.com/makr-code/wordpressPlugins/releases/tag/v1.2.0
```

---

## Troubleshooting Version Issues

### Scenario: Customer still on old version

1. Check if update available:
```bash
wp eval "
  \$transient = get_transient('themisdb_update_...');
  print_r(\$transient);
"
```

2. Clear update cache:
```bash
wp transient delete themisdb_update_check
```

3. Force update check:
```bash
wp plugin update themisdb-order-request --dry-run
```

### Scenario: Cannot downgrade after major update

Document procedure:
```bash
# 1. Backup current database
mysqldump wordpress > backup-v2.0.0.sql

# 2. Restore from backup before 2.0.0
mysql wordpress < backup-v1.2.0.sql

# 3. Download previous plugin version from portal
# https://themisdb.org/customer-downloads

# 4. Deploy v1.2.0
./deploy.sh themisdb-order-request v1.2.0 /var/www/wordpress
```

---

## Decision Tree

```
New change needed?
    ↓
Does it change existing behavior? (Backward compatible?)
    ├─ YES, existing behavior broken → MAJOR version
    │   (1.x.x → 2.0.0)
    │
    └─ NO, fully backward compatible?
        ├─ YES, new feature or improvement → MINOR version
        │   (1.2.x → 1.3.0)
        │
        └─ Only bug fixes or security? → PATCH version
            (1.2.0 → 1.2.1)
```

---

## Success Criteria

✅ Complete when:
- Version scheme clearly defined
- Release process documented
- Compatibility matrix tracked
- Upgrade paths tested
- Deprecation policy established
- Release notes automated
- Customers informed of updates

**Next Steps:**
1. Update all plugin headers with version info
2. Create CHANGELOG.md in each plugin
3. Test update checking
4. Deploy first release (v1.2.0)
