#!/bin/bash
rsync -zrE --delete-before --stats --rsh='ssh -p2222' 11.11.11.11:/var/www/vhosts/example.com/subdomains/wiki/httpdocs/* /srv/www/htdocs;
chmod -R 755 /srv/www/htdocs;
exit;