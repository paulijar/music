#!/usr/bin/env bash

SCRIPT_DIR=$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )
MUSIC_APP_DIR=$SCRIPT_DIR/../..

cd $SCRIPT_DIR/..
mkdir -p ci
cd ci

# download the cloud and setup folders
wget https://download.nextcloud.com/server/releases/nextcloud-31.0.7.zip
unzip nextcloud-31.0.7.zip
mv nextcloud server
mkdir server/data
ln -s $MUSIC_APP_DIR server/apps/music

# install the cloud
cd server
php occ maintenance:install --database-name oc_autotest --database-user oc_autotest --admin-user admin --admin-pass 0aVnqOWH1rurCrNdTJTM --database sqlite --database-pass=''
OC_PASS=ampache123456 php occ user:add ampache --password-from-env

# enable the Music app, removing the ownCloud-specific files first
rm apps/music/appinfo/database.xml
rm apps/music/appinfo/app.php
php occ app:enable music
