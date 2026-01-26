# Code Signing Certificate

## For Nextcloud App Store Submission

To publish this app in the Nextcloud App Store, you need to sign the app with a code signing certificate.

### Generate Certificate

1. Create a private key and CSR:
```bash
openssl req -nodes -newkey rsa:4096 -keyout digitalsignage.key -out digitalsignage.csr -subj "/CN=digitalsignage"
```

2. Submit the CSR to Nextcloud:
   - Go to https://apps.nextcloud.com/developer/apps
   - Navigate to your app
   - Upload the CSR file

3. Nextcloud will return a signed certificate (digitalsignage.crt)

4. Keep both files safe:
   - `digitalsignage.key` (private key - DO NOT commit to Git!)
   - `digitalsignage.crt` (signed certificate - can be committed)

### Sign a Release

```bash
# Create signature for release
openssl dgst -sha512 -sign digitalsignage.key digitalsignage-1.0.1.tar.gz | openssl base64
```

### Important Notes

- The private key (`digitalsignage.key`) must NEVER be committed to the repository
- Add `*.key` to `.gitignore` to prevent accidental commits
- The certificate (`digitalsignage.crt`) can be committed
- Each release must be signed with your certificate
- Store the private key securely (password manager, encrypted storage)

### Files to Keep

```
certificates/
├── digitalsignage.key  # Private key (DO NOT commit!)
├── digitalsignage.crt  # Signed certificate (can commit)
└── digitalsignage.csr  # Certificate signing request (for reference)
```

## Links

- Nextcloud Developer Portal: https://apps.nextcloud.com/developer/
- App Store Documentation: https://nextcloudappstore.readthedocs.io/
- Code Signing Guide: https://docs.nextcloud.com/server/latest/developer_manual/app_publishing_maintenance/code_signing.html
