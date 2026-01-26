#!/bin/bash

# Update Script for the web server
# Updates the Digital Signage App on the server

SERVER="ssh-w00e0173@w00e0173.kasserver.com"
APP_PATH="/www/htdocs/w00e0173/nc/apps/digitalsignage"

echo "🔄 Updating web server..."
echo "Server: $SERVER"
echo "Path: $APP_PATH"
echo ""

# SSH connection and Git Pull
ssh $SERVER "cd $APP_PATH && git pull --rebase origin main || (git checkout --theirs README.md && git add README.md && EDITOR=true git rebase --continue)"

if [ $? -eq 0 ]; then
    echo ""
    echo "Server successfully updated!"
else
    echo ""
    echo "Error updating server"
    exit 1
fi
