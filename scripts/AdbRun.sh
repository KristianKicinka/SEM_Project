#!/bin/bash

PACKAGE_NAME=$1;

URL="market://details?id=$PACKAGE_NAME";
#EMULATOR_NAME="Pixel_4_API_33";

# Kill and run adb servers
#adb kill-server;
#adb start-server;

# Run adb emulator
#emulator -avd $EMULATOR_NAME;

# Run play store and open target application page
adb shell am start -a android.intent.action.VIEW -d "$URL";

sleep 15;

# Tap to install application

# MAC OS
adb shell input tap 700 800
# Windows
#adb shell input tap 500 500

sleep 60;

# Close play store
adb shell am force-stop com.android.vending

sleep 3

#Uninstall application
adb uninstall "$PACKAGE_NAME";

