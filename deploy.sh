#!/bin/bash
# Deploy Firebox framework and test project to 10.0.0.10

SERVER=10.0.0.10
FBX_DEST=/var/www/html/firebox
TEST_DEST=/var/www/html/firebox-test

set -e

echo "Deploying framework to $SERVER:$FBX_DEST ..."
rsync -a --delete \
    --exclude='.git/' \
    --exclude='testproject/' \
    --exclude='*.md' \
    --exclude='deploy.sh' \
    --exclude='claude_notes.md' \
    /home/afabian/firebox/ \
    $SERVER:$FBX_DEST/

echo "Deploying test project to $SERVER:$TEST_DEST ..."
rsync -a \
    --exclude='.git/' \
    --exclude='parsed/' \
    /home/afabian/firebox/testproject/ \
    $SERVER:$TEST_DEST/

echo "Setting up parsed directories and permissions ..."
ssh $SERVER "
    mkdir -p $TEST_DEST/parsed/dev $TEST_DEST/parsed/prod
    chmod 777 $TEST_DEST/parsed $TEST_DEST/parsed/dev $TEST_DEST/parsed/prod
    chmod 666 $TEST_DEST/todo.data
"

echo ""
echo "Done. Test app: http://$SERVER/firebox-test/"
