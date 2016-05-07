#!/bin/sh

sudo apt-get update
sudo apt-get upgrade -y

sudo apt-get install -y git tcsh emacs24-nox htop sysstat ufw fail2ban unattended-upgrades python-setuptools unzip
sudo dpkg-reconfigure --priority=low unattended-upgrades
