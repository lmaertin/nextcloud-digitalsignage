# Development and deployment scripts

This directory contains helper scripts for development, release preparation, and deployment. The scripts are excluded from release archives through the repository export rules, but they remain available in the Git repository for maintainers and deployment operators.

## Release management

### `create-release.sh`
Creates a signed release archive for the Nextcloud App Store.

```bash
./create-release.sh
# Creates: digitalsignage-X.Y.Z.tar.gz
```

### `upload-to-appstore.sh`
Uploads a release artifact to the Nextcloud App Store.

```bash
./upload-to-appstore.sh VERSION DOWNLOAD_URL [--nightly]
```

Requirements:
- `NC_APPSTORE_TOKEN` environment variable
- A signed release archive

### `verify-signature.sh`
Verifies the signature of an uploaded or generated release archive.

```bash
./verify-signature.sh digitalsignage-X.Y.Z.tar.gz
```

### `generate-app-registration-json.sh`
Generates the JSON payload used for app store registration when required.

## Adding new scripts

When adding deployment or development tools:

1. Place release tools in the root `scripts/` folder.
2. Place platform-specific tools in a dedicated subfolder such as `docker/` or `kubernetes/`.
3. Document the script in this README.
4. Mark the file executable when needed with `chmod +x script.sh`.

## Best practices

- Never commit secrets or tokens.
- Prefer environment variables for runtime configuration.
- Include usage instructions at the top of each script.
- Test scripts on a clean system before merging changes.
- Keep each script focused on one responsibility.

## Not included in releases

All scripts in this folder are excluded from release archives through the repository export rules.

- `.gitattributes` excludes the directory from exported archives.
- GitHub Actions and packaging workflows can omit `/scripts` from release bundles.

This keeps release downloads small while retaining the deployment tooling needed for operational setups.

## Related documentation

- [CODE_SIGNING.md](../CODE_SIGNING.md) - App signing documentation
- [README.md](../README.md) - Main app documentation
- [CHANGELOG.md](../CHANGELOG.md) - Release history
