#! /bin/sh

# this compiles all the .po files in the folder to their .mo
# we use this because developers might not have the application installed so CLI tools are useless

# from http://codex.wordpress.org/I18n_for_WordPress_Developers
for file in `find . -name "*.po"` ; do msgfmt -o `echo $file | sed s/\.po/\.mo/` $file ; done