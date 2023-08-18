#!/bin/bash

EMULATOR_NAME="Pixel_4_API_33";

# Kill and run adb servers
adb kill-server;
adb start-server;

# Run adb emulator
emulator -avd $EMULATOR_NAME;