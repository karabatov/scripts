#!/bin/sh
x11vnc -passwd PASSWORD -display :0 -auth `ps aux | grep Xorg | sed -n -e 's/^.*-auth //p'`