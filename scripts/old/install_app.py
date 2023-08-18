import sys
import os
import subprocess

if __name__ == '__main__':
    apk_file_path = str(sys.argv[1])
    package_name = str(sys.argv[2])

    install_command = 'adb install ' + apk_file_path
    run_command = 'adb shell monkey -p ' + package_name + ' -c android.intent.category.LAUNCHER 1'
    stop_command = 'adb shell pm clear ' + package_name
    uninstall_command = 'adb uninstall ' + package_name

    #subprocess.call(install_command, shell=True)
    #print('App was succesfully installed')
    subprocess.call(run_command, shell=True)
    print('app is running')
    subprocess.call(stop_command, shell=True)
    print('app is stopped')
    subprocess.call(uninstall_command, shell=True)
    print('app is uninstalled')

