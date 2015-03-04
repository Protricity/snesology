#!/bin/sh

sudo rpm -Uvh http://nginx.org/packages/centos/7/noarch/RPMS/nginx-release-centos-7-0.el7.ngx.noarch.rpm;
sudo yum install nginx;
sudo systemctl start nginx.service;
sudo systemctl enable nginx.service;

ip addr show eth0 | grep inet | awk '{ print $2; }' | sed 's/\/.*$//';
sudo yum install mariadb-server mariadb;

sudo systemctl start mariadb;
sudo mysql_secure_installation;

sudo systemctl enable mariadb.service
sudo yum install php php-mysql php-fpm

# cgi.fix_pathinfo=0


# sudo systemctl start php-fpmsudo vi /etc/php-fpm.d/www.conf
# Find the line that specifies the listen parameter, and change it so it looks like the following:
# listen = /var/run/php-fpm/php-fpm.sock

sudo systemctl start php-fpm;
sudo systemctl enable php-fpm.service;

nano /etc/nginx/conf.d/default.conf;

sudo systemctl restart nginx;


yum install git;

git clone https://code.google.com/p/snesology/ /usr/share/nginx/snesology;