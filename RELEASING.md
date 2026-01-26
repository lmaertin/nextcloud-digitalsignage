# App Store Release Process

This document describes the steps to release a new version to the Nextcloud App Store.

## Prerequisites

- Code signing certificate set up (see [CODE_SIGNING.md](CODE_SIGNING.md))
- Nextcloud App Store account with app registered
- Git repository access
- Testing environment

## Release Steps

### 1. Update Version

Update version in [appinfo/info.xml](appinfo/info.xml):

```xml
<version>1.0.2</version>
```

### 2. Update Changelog

Add new version section to [CHANGELOG.md](CHANGELOG.md):

```markdown
## [1.0.2] - YYYY-MM-DD

### Added
- New features...

### Changed
- Changes...

### Fixed
- Bug fixes...
```

### 3. Commit Changes

```bash
git add appinfo/info.xml CHANGELOG.md
git commit -m "Release version 1.0.2"
git push
```

### 4. Create Git Tag

```bash
git tag -a v1.0.2 -m "Release version 1.0.2"
git push origin v1.0.2
```

### 5. Create Release Archive

```bash
# Create clean directory
mkdir -p releases
cd releases

# Clone or copy app (without .git)
git clone --depth 1 --branch v1.0.2 https://github.com/lmaertin/nextcloud-digitalsignage.git digitalsignage
cd digitalsignage
rm -rf .git .gitignore update-server.sh

# Create archive
cd ..
tar -czf digitalsignage-1.0.2.tar.gz digitalsignage/
```

### 6. Sign the Release

```bash
# Sign with your private key
openssl dgst -sha512 -sign /path/to/digitalsignage.key digitalsignage-1.0.2.tar.gz | openssl base64 > digitalsignage-1.0.2.tar.gz.sig

# Display signature (needed for app store)
cat digitalsignage-1.0.2.tar.gz.sig
```

### 7. Upload to App Store

1. Go to https://apps.nextcloud.com/developer/apps/digitalsignage
2. Click "Upload new release"
3. Upload `digitalsignage-1.0.2.tar.gz`
4. Paste the signature from `digitalsignage-1.0.2.tar.gz.sig`
5. Add changelog notes
6. Submit

### 8. Create GitHub Release

1. Go to https://github.com/lmaertin/nextcloud-digitalsignage/releases
2. Click "Draft a new release"
3. Select tag `v1.0.2`
4. Title: "Version 1.0.2"
5. Description: Copy from CHANGELOG.md
6. Attach `digitalsignage-1.0.2.tar.gz`
7. Publish release

## Testing Before Release

Before creating a release:

1. Test all features in a clean Nextcloud installation
2. Verify all settings work correctly
3. Test token creation and public display
4. Check translations
5. Run any available tests
6. Verify on different Nextcloud versions (min and max)

## Post-Release

1. Monitor App Store for approval status
2. Check for user feedback and issues
3. Update documentation if needed
4. Announce release (blog, social media, etc.)

## Rollback

If issues are discovered after release:

1. Don't panic - App Store has approval process
2. If not yet approved: withdraw and fix
3. If approved and critical bug: release hotfix version immediately
4. Contact Nextcloud if urgent action needed

## Automated Release Script

Consider creating a script to automate some of these steps:

```bash
#!/bin/bash
VERSION=$1
./scripts/release.sh $VERSION
```

## Links

- App Store Developer Portal: https://apps.nextcloud.com/developer/
- App Documentation: https://nextcloudappstore.readthedocs.io/
- Code Signing Guide: https://docs.nextcloud.com/server/latest/developer_manual/app_publishing_maintenance/code_signing.html
