#!/bin/bash

major='0.5'

cd /storage/websites/

minor=`ls -t -1 --reverse /storage/websites/firebox.anjero.com/download/*.gz | grep -v current | tail -1 | cut -d '.' -f 5`

if [ ${minor:0:1} -eq "0" ]
then
	minor=${minor:1}
fi
minor=$((minor+1))
if [ ${#minor} -eq "1" ]
then
	minor="0$minor"
fi

tar -cf firebox-$major.$minor.tar --exclude .svn firebox firebox_demo
gzip firebox-$major.$minor.tar

mv firebox-$major.$minor.tar.gz firebox.anjero.com/download

rm firebox.anjero.com/download/firebox-$major-current.tar.gz
ln -s firebox-$major.$minor.tar.gz firebox.anjero.com/download/firebox-$major-current.tar.gz

