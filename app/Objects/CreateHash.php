<?php
/**
 * @file CreateHash.php
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 */

namespace App\Objects;

use App\Exceptions\AppInstallationFailException;
use App\Exceptions\AppNameNotFoundException;
use App\Exceptions\AppUninstallationFailException;
use App\Exceptions\AppVersionNotFoundException;
use App\Exceptions\CloseAppFailException;
use App\Exceptions\CreateCommunicationOnEmulatorException;
use App\Exceptions\HashGeneratorFailException;
use App\Exceptions\LoadingPreinstalledAppsFailed;
use App\Exceptions\PackageNameNotFoundException;
use App\Exceptions\RunAppFailException;
use App\Exceptions\XapkFileExtractException;
use App\Models\Application;
use App\Models\Emulator;
use App\Models\File;
use App\Models\Hash;
use App\Models\Process as ProcessModel;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use ZipArchive;

// Hash generator python script path
const HASH_SCRIPT_PATH = 'scripts/hash_generator.py';
// Path to pcap directory in server storage
const PCAP_PATH = 'app/public/pcaps/';
// Path to preinstalled apps list file
const PRE_INSTALLED_APPS_FILE = 'scripts/pre_installed_apps.txt';
// Path to tmp_xapk directory in server storage
const TMP_XAPK_PATH = 'app/public/tmp_xapk/';

class CreateHash {

    protected array $files = [];
    protected array $hashes = [];
    protected array $hash_types = [];
    protected string $process_id = '';
    protected string $ip_address = '';
    protected HashProcessData $hash_process_data;

    public function __construct($hash_types, $input_type, $process_id, $ip_address, $channel_id, $process_name, $api_id) {

        $this->hash_types = $hash_types;
        $this->process_id = $process_id;
        $this->ip_address = $ip_address;
        $this->hash_process_data = new HashProcessData($process_id, $input_type, $ip_address, $channel_id, $process_name, $api_id);
    }

    /**
     * @brief The function ensures adding new files to file list
     * @param string $name File name
     * @param string $type File type
     * @param string $path File path
     * @return void
     */
    protected function addFileToFiles(string $name, string $type, string $path) : void {

        $file = [
            'name' => $name,
            'type' => $type,
            'path' => $path,
        ];

        $this->files[] = $file;
    }

    /**
     * @brief The function ensures pcap file creation
     * @param Emulator $emulator Selected emulator
     * @param string $pcap_file_name Output pcap file name
     * @param string $package_name Application package name
     * @return string Out pcap file path
     * @throws CloseAppFailException
     * @throws CreateCommunicationOnEmulatorException
     * @throws PackageNameNotFoundException
     * @throws RunAppFailException
     */
    public function createPcapFile(Emulator $emulator, string $pcap_file_name, string $package_name) : string {

        $pcap_out_path = storage_path(PCAP_PATH).$pcap_file_name;
        $analysis_count = (int) env("ANALYSIS_COUNT", 10);
        $network_analysis_time = (int) env("NETWORK_ANALYSIS_TIME", 10);

        Log::channel('devlog')->info('Starting PCAP file creation', [
            'pcap_file_name' => $pcap_file_name,
            'pcap_out_path' => $pcap_out_path,
            'emulator' => $emulator->name,
            'package_name' => $package_name,
            'analysis_count' => $analysis_count,
            'network_analysis_time' => $network_analysis_time,
            'process_id' => $this->process_id
        ]);

        $this->addFileToFiles($pcap_file_name, 'PCAP', $pcap_out_path);

        $command = "tshark -i ".$emulator->network_interface." -F pcap -w ".$pcap_out_path;

        Log::channel('devlog')->info('Starting tshark capture', [
            'command' => $command,
            'network_interface' => $emulator->network_interface,
            'process_id' => $this->process_id
        ]);

        $tshark_start_time = microtime(true);
        $process = Process::fromShellCommandline($command);
        $process->start();
        Log::channel('devlog')->info('Tshark process started successfully', [
            'process_id' => $this->process_id,
            'pid' => $process->getPid()
        ]);

        $total_iteration_start_time = microtime(true);
        for($index = 0; $index < $analysis_count; $index ++){
            $iteration_start_time = microtime(true);
            Log::channel('devlog')->info('Starting analysis iteration', [
                'iteration' => $index + 1,
                'total_iterations' => $analysis_count,
                'package_name' => $package_name,
                'emulator' => $emulator->name,
                'process_id' => $this->process_id
            ]);

            // Run app
            $run_app_start = microtime(true);
            $this->runAppOnEmulator($emulator, $package_name);
            $run_app_duration = round(microtime(true) - $run_app_start, 2);
            Log::channel('devlog')->info('App run completed', [
                'iteration' => $index + 1,
                'duration_seconds' => $run_app_duration,
                'process_id' => $this->process_id
            ]);

            // Create communication
            $comm_start = microtime(true);
            $this->createCommunicationOnEmulator($emulator, $package_name);
            $comm_duration = round(microtime(true) - $comm_start, 2);
            Log::channel('devlog')->info('Communication generation completed', [
                'iteration' => $index + 1,
                'duration_seconds' => $comm_duration,
                'process_id' => $this->process_id
            ]);

            // Sleep for network analysis
            Log::channel('devlog')->info('Sleeping for network analysis', [
                'iteration' => $index + 1,
                'sleep_seconds' => $network_analysis_time,
                'process_id' => $this->process_id
            ]);
            sleep($network_analysis_time);

            // Close app
            $close_app_start = microtime(true);
            $this->closeAppOnEmulator($emulator, $package_name);
            $close_app_duration = round(microtime(true) - $close_app_start, 2);
            Log::channel('devlog')->info('App close completed', [
                'iteration' => $index + 1,
                'duration_seconds' => $close_app_duration,
                'process_id' => $this->process_id
            ]);

            $iteration_duration = round(microtime(true) - $iteration_start_time, 2);
            Log::channel('devlog')->info('Analysis iteration completed', [
                'iteration' => $index + 1,
                'total_duration_seconds' => $iteration_duration,
                'breakdown' => [
                    'run_app' => $run_app_duration,
                    'communication' => $comm_duration,
                    'sleep' => $network_analysis_time,
                    'close_app' => $close_app_duration
                ],
                'process_id' => $this->process_id
            ]);
        }

        $total_iteration_duration = round(microtime(true) - $total_iteration_start_time, 2);
        Log::channel('devlog')->info('All analysis iterations completed', [
            'total_iterations' => $analysis_count,
            'total_duration_seconds' => $total_iteration_duration,
            'average_per_iteration' => round($total_iteration_duration / $analysis_count, 2),
            'process_id' => $this->process_id
        ]);

        $tshark_duration = round(microtime(true) - $tshark_start_time, 2);
        Log::channel('devlog')->info('Stopping tshark capture', [
            'tshark_duration_seconds' => $tshark_duration,
            'process_id' => $this->process_id
        ]);
        $process->stop(0.2);

        Log::channel('devlog')->info('PCAP file creation completed successfully', [
            'pcap_file_name' => $pcap_file_name,
            'pcap_out_path' => $pcap_out_path,
            'total_duration_seconds' => round(microtime(true) - $tshark_start_time, 2),
            'process_id' => $this->process_id
        ]);

        return $pcap_out_path;
    }

    /**
     * @brief The function ensures hashes creation
     * @param string $pcap_file_name Input pcap file name
     * @param string $pcap_file_path Input pcap file path
     * @return array Hashes list
     * @throws HashGeneratorFailException
     */
    public function createHashes(string $pcap_file_name, string $pcap_file_path): array {

        Log::channel('devlog')->info('Starting hash generation from PCAP file', [
            'pcap_file_name' => $pcap_file_name,
            'pcap_file_path' => $pcap_file_path,
            'hash_types' => $this->hash_types,
            'process_id' => $this->process_id
        ]);

        $command = env("PYTHON_COMMAND", "python3")." ".base_path(HASH_SCRIPT_PATH);
        $command = $command." ".escapeshellarg($pcap_file_path);
        
        // Add custom generators as second argument if any are specified
        $custom_generators = [];
        if (!empty($this->hash_types)) {
            $standardTypes = ['JA3', 'JA3S', 'JA4', 'JA4S', 'JA4X'];
            $custom_generators = array_values(array_filter($this->hash_types, function ($type) use ($standardTypes) {
                return is_string($type) && !in_array($type, $standardTypes, true);
            }));
            
            if (!empty($custom_generators)) {
                $command = $command." ".escapeshellarg(json_encode($custom_generators));
                Log::channel('devlog')->info('Custom hash generators included', [
                    'custom_generators' => $custom_generators,
                    'process_id' => $this->process_id
                ]);
            }
        }

        Log::channel('devlog')->info('Python hash generation command prepared', [
            'command' => $command,
            'hash_types_count' => count($this->hash_types),
            'custom_generators_count' => count($custom_generators),
            'process_id' => $this->process_id
        ]);

        $start_time = microtime(true);
        $process = Process::fromShellCommandline($command);
        
        // Set environment variables for Python script
        $process->setEnv([
            'LARAVEL_BASE_URL' => config('app.url', 'http://localhost:8000'),
            'LARAVEL_API_KEY' => 'python_hash_generator_key_' . env('APP_KEY', 'default_key'),
            'USE_MANUAL_CONFIG' => 'false', // Use database instead of manual config
            'PYTHONUNBUFFERED' => '1',
            'PYTHONIOENCODING' => 'utf-8',
            'OMP_NUM_THREADS' => '4'
        ]);
        
        // Set timeout to 300 seconds (5 minutes) for Python script execution
        $timeout = 300;
        $process->setTimeout($timeout);
        
        Log::channel('devlog')->info('Starting Python hash generation process', [
            'command' => $command,
            'timeout_seconds' => $timeout,
            'pcap_file_size_bytes' => file_exists($pcap_file_path) ? filesize($pcap_file_path) : 0,
            'process_id' => $this->process_id
        ]);
        
        $process->run();
        $duration = round(microtime(true) - $start_time, 2);

        if (!$process->isSuccessful()) {
            $errorOutput = $process->getErrorOutput();
            $exitCode = $process->getExitCode();
            Log::channel('devlog')->error('Python hash generation failed', [
                'exit_code' => $exitCode,
                'error_output' => $errorOutput,
                'output' => $process->getOutput(),
                'duration_seconds' => $duration,
                'timeout_seconds' => $timeout,
                'command' => $command,
                'process_id' => $this->process_id
            ]);
            throw new HashGeneratorFailException("Python script failed with exit code {$exitCode}: {$errorOutput}");
        }

        $output = $process->getOutput();
        Log::channel('devlog')->info('Python hash generation process completed', [
            'duration_seconds' => $duration,
            'timeout_seconds' => $timeout,
            'time_remaining' => round($timeout - $duration, 2),
            'output_length' => strlen($output),
            'exit_code' => $process->getExitCode(),
            'process_id' => $this->process_id
        ]);
        
        $decodedOutput = json_decode($output);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::channel('devlog')->error('Failed to decode Python output as JSON', [
                'json_error' => json_last_error_msg(),
                'json_error_code' => json_last_error(),
                'output_preview' => substr($output, 0, 500),
                'output_length' => strlen($output),
                'process_id' => $this->process_id
            ]);
            throw new HashGeneratorFailException("Invalid JSON output from Python script: " . json_last_error_msg());
        }

        $hashes_count = is_array($decodedOutput) ? count($decodedOutput) : (is_object($decodedOutput) && isset($decodedOutput->hashes) ? count($decodedOutput->hashes) : 0);
        Log::channel('devlog')->info('Hash generation completed successfully', [
            'hashes_count' => $hashes_count,
            'total_duration_seconds' => $duration,
            'process_id' => $this->process_id
        ]);

        return $decodedOutput;
    }

    /**
     * @brief The function ensures starting app on emulator
     * @param Emulator $emulator Selected emulator
     * @param string $package_name Application package name
     * @return void
     * @throws RunAppFailException
     */
    private function runAppOnEmulator(Emulator $emulator, string $package_name): void {

        $command = 'adb shell monkey -p '.trim($package_name).' -c android.intent.category.LAUNCHER 1';

        if (env("ENVIRONMENT", "local") == "server"){
            $command = 'docker exec '.$emulator->name.' '.$command;
        }

        Log::channel('devlog')->info('Running app on emulator', [
            'command' => $command,
            'emulator' => $emulator->name,
            'package_name' => $package_name,
            'process_id' => $this->process_id
        ]);

        $start_time = microtime(true);
        $process = Process::fromShellCommandline($command);
        $process->setTimeout(30); // 30 seconds should be enough for app launch
        $process->run();
        $duration = round(microtime(true) - $start_time, 2);

        if (!$process->isSuccessful()) {
            Log::channel('devlog')->error('Run app command failed', [
                'command' => $command,
                'exit_code' => $process->getExitCode(),
                'error_output' => $process->getErrorOutput(),
                'output' => $process->getOutput(),
                'duration_seconds' => $duration,
                'process_id' => $this->process_id
            ]);
            throw new RunAppFailException($process->getErrorOutput());
        }

        Log::channel('devlog')->info('Run app command completed successfully', [
            'command' => $command,
            'duration_seconds' => $duration,
            'output' => $process->getOutput(),
            'process_id' => $this->process_id
        ]);
    }

    /**
     * @brief The function ensures application events generation
     * @param Emulator $emulator Selected emulator
     * @param string $package_name Application package name
     * @return void
     * @throws CreateCommunicationOnEmulatorException
     */
    private function createCommunicationOnEmulator(Emulator $emulator, string $package_name): void {

       $command = 'adb shell monkey -p ' . escapeshellarg(trim($package_name)) .
        ' --ignore-crashes' .
        ' --ignore-timeouts' .
        ' --ignore-security-exceptions' .
        ' --monitor-native-crashes' .
        ' --throttle 200' .
        ' --pct-syskeys 0' .
        ' --pct-appswitch 0' .
        ' --pct-anyevent 0' .
        ' -v -v -v 500';


        if (env("ENVIRONMENT", "local") == "server"){
            $command = 'docker exec '.$emulator->name.' '.$command;
        }

        Log::channel('devlog')->info('Starting monkey command to generate communication', [
            'command' => $command,
            'emulator' => $emulator->name,
            'package_name' => $package_name,
            'process_id' => $this->process_id
        ]);

        $start_time = microtime(true);
        $process = Process::fromShellCommandline($command);
        // Set timeout for monkey command - configurable via environment variable
        // Default is 90 seconds for monkey command with 500 events
        // On production servers, Docker operations can be slower, so this timeout can be adjusted via env
        $timeout = (int) env('MONKEY_COMMAND_TIMEOUT', 90);
        $process->setTimeout($timeout);
        Log::channel('devlog')->info('Monkey command configuration', [
            'timeout_seconds' => $timeout,
            'command' => $command,
            'process_id' => $this->process_id
        ]);
        
        try {
            $process->run();
            $duration = round(microtime(true) - $start_time, 2);
            
            Log::channel('devlog')->info('Monkey command execution completed', [
                'duration_seconds' => $duration,
                'timeout_seconds' => $timeout,
                'time_remaining' => round($timeout - $duration, 2),
                'process_id' => $this->process_id
            ]);
        } catch (\Symfony\Component\Process\Exception\ProcessTimedOutException $e) {
            $duration = round(microtime(true) - $start_time, 2);
            Log::channel('devlog')->error('Monkey command timed out', [
                'timeout_seconds' => $timeout,
                'actual_duration_seconds' => $duration,
                'command' => $command,
                'emulator' => $emulator->name,
                'package_name' => $package_name,
                'error' => $e->getMessage(),
                'process_id' => $this->process_id
            ]);
            throw new CreateCommunicationOnEmulatorException(
                "Monkey command timed out after {$timeout} seconds. Command: {$command}. " .
                "This may indicate that the emulator is slow or overloaded. " .
                "Consider increasing MONKEY_COMMAND_TIMEOUT environment variable."
            );
        }

        if (!$process->isSuccessful()) {
            $duration = round(microtime(true) - $start_time, 2);
            Log::channel('devlog')->error('Monkey command failed', [
                'exit_code' => $process->getExitCode(),
                'error_output' => $process->getErrorOutput(),
                'output' => $process->getOutput(),
                'command' => $command,
                'duration_seconds' => $duration,
                'emulator' => $emulator->name,
                'package_name' => $package_name,
                'process_id' => $this->process_id
            ]);
            throw new CreateCommunicationOnEmulatorException(
                "Monkey command failed: " . $process->getErrorOutput()
            );
        }

        Log::channel('devlog')->info('Monkey command succeeded', [
            'duration_seconds' => $duration ?? round(microtime(true) - $start_time, 2),
            'exit_code' => $process->getExitCode(),
            'output_length' => strlen($process->getOutput()),
            'process_id' => $this->process_id
        ]);
    }

    /**
     * @brief The function ensures closing app on emulator
     * @param Emulator $emulator Selected emulator
     * @param string $package_name Application package name
     * @return void
     * @throws CloseAppFailException
     */
    private function closeAppOnEmulator(Emulator $emulator, string $package_name) : void {
        Log::channel('devlog')->info('Starting app close/clear process', [
            'emulator' => $emulator->name,
            'package_name' => $package_name,
            'process_id' => $this->process_id
        ]);

        $ensure_adb_start = microtime(true);
        // First, check if ADB server is running and devices are available
        $this->ensureAdbServerRunning($emulator);
        $ensure_adb_duration = round(microtime(true) - $ensure_adb_start, 2);
        Log::channel('devlog')->info('ADB server check completed', [
            'duration_seconds' => $ensure_adb_duration,
            'process_id' => $this->process_id
        ]);

        $command = 'adb shell pm clear '.$package_name;

        if (env("ENVIRONMENT", "local") == "server"){
            $command = 'docker exec '.$emulator->name.' '.$command;
        }

        Log::channel('devlog')->info('Executing ADB CLEAR APP command', [
            'command' => $command,
            'emulator' => $emulator->name,
            'package_name' => $package_name,
            'process_id' => $this->process_id
        ]);

        $clear_start_time = microtime(true);
        $process = Process::fromShellCommandline($command);
        // Set timeout for ADB clear command - configurable via environment variable
        // Default is 90 seconds, can be adjusted for slower production servers
        $timeout = (int) env('ADB_CLEAR_COMMAND_TIMEOUT', 90);
        $process->setTimeout($timeout);
        Log::channel('devlog')->info('ADB CLEAR command configuration', [
            'timeout_seconds' => $timeout,
            'command' => $command,
            'process_id' => $this->process_id
        ]);
        
        $process->run();
        $clear_duration = round(microtime(true) - $clear_start_time, 2);

        if (!$process->isSuccessful()) {
            Log::channel('devlog')->error('ADB CLEAR APP command failed', [
                'command' => $command,
                'exit_code' => $process->getExitCode(),
                'error_output' => $process->getErrorOutput(),
                'output' => $process->getOutput(),
                'duration_seconds' => $clear_duration,
                'timeout_seconds' => $timeout,
                'emulator' => $emulator->name,
                'package_name' => $package_name,
                'process_id' => $this->process_id
            ]);
            throw new CloseAppFailException($process->getErrorOutput());
        }

        Log::channel('devlog')->info('ADB CLEAR APP command completed successfully', [
            'command' => $command,
            'duration_seconds' => $clear_duration,
            'timeout_seconds' => $timeout,
            'time_remaining' => round($timeout - $clear_duration, 2),
            'exit_code' => $process->getExitCode(),
            'output' => $process->getOutput(),
            'process_id' => $this->process_id
        ]);
    }

    /**
     * @brief TODO
     * @param string $file_path Path to apk file intended for analysis
     * @return bool
     */
    private function isXAPK(string $file_path) : bool {
        return strtolower(pathinfo($file_path, PATHINFO_EXTENSION)) === 'xapk';
    }

    /**
     * @brief TODO
     * @param string $file_path Path to apk file intended for analysis
     * @return bool
     */
    private function isAPK(string $file_path) : bool {
        return strtolower(pathinfo($file_path, PATHINFO_EXTENSION)) === 'apk';
    }

    /**
     * @brief Ensures ADB server is running and devices are available
     * @param Emulator $emulator
     * @return void
     * @throws AppInstallationFailException
     */
    private function ensureAdbServerRunning(Emulator $emulator) : void {
        Log::channel('devlog')->info('Ensuring ADB server is running', [
            'emulator' => $emulator->name,
            'process_id' => $this->process_id
        ]);

        // Start ADB server
        $startCommand = 'adb start-server';
        if (env("ENVIRONMENT", "local") == "server"){
            $startCommand = 'docker exec '.$emulator->name.' '.$startCommand;
        }
        
        Log::channel('devlog')->info('Starting ADB server', [
            'command' => $startCommand,
            'emulator' => $emulator->name,
            'process_id' => $this->process_id
        ]);

        $start_time = microtime(true);
        $startProcess = Process::fromShellCommandline($startCommand);
        $startProcess->setTimeout(30);
        $startProcess->run();
        $start_duration = round(microtime(true) - $start_time, 2);
        
        Log::channel('devlog')->info('ADB START-SERVER command completed', [
            'command' => $startCommand,
            'duration_seconds' => $start_duration,
            'exit_code' => $startProcess->getExitCode(),
            'success' => $startProcess->isSuccessful(),
            'output' => $startProcess->getOutput(),
            'error_output' => $startProcess->getErrorOutput(),
            'process_id' => $this->process_id
        ]);
        
        // Wait a moment for ADB to initialize
        Log::channel('devlog')->info('Waiting for ADB to initialize', [
            'sleep_seconds' => 3,
            'process_id' => $this->process_id
        ]);
        sleep(3);
        
        // Check if devices are available
        $devicesCommand = 'adb devices';
        if (env("ENVIRONMENT", "local") == "server"){
            $devicesCommand = 'docker exec '.$emulator->name.' '.$devicesCommand;
        }
        
        Log::channel('devlog')->info('Checking ADB devices', [
            'command' => $devicesCommand,
            'emulator' => $emulator->name,
            'process_id' => $this->process_id
        ]);

        $devices_start_time = microtime(true);
        $devicesProcess = Process::fromShellCommandline($devicesCommand);
        $devicesProcess->setTimeout(30);
        $devicesProcess->run();
        $devices_duration = round(microtime(true) - $devices_start_time, 2);
        
        Log::channel('devlog')->info('ADB DEVICES command completed', [
            'command' => $devicesCommand,
            'duration_seconds' => $devices_duration,
            'exit_code' => $devicesProcess->getExitCode(),
            'success' => $devicesProcess->isSuccessful(),
            'output' => $devicesProcess->getOutput(),
            'error_output' => $devicesProcess->getErrorOutput(),
            'process_id' => $this->process_id
        ]);
        
        if (!$devicesProcess->isSuccessful()) {
            Log::channel('devlog')->error('ADB devices check failed', [
                'command' => $devicesCommand,
                'error_output' => $devicesProcess->getErrorOutput(),
                'process_id' => $this->process_id
            ]);
            throw new AppInstallationFailException('Failed to check ADB devices: ' . $devicesProcess->getErrorOutput());
        }
        
        $output = $devicesProcess->getOutput();
        $has_device = strpos($output, 'device') !== false;
        $has_emulator = strpos($output, 'emulator') !== false;
        
        Log::channel('devlog')->info('ADB devices check result', [
            'has_device' => $has_device,
            'has_emulator' => $has_emulator,
            'output' => $output,
            'process_id' => $this->process_id
        ]);

        if (!$has_device && !$has_emulator) {
            Log::channel('devlog')->error('No ADB devices/emulators found', [
                'output' => $output,
                'process_id' => $this->process_id
            ]);
            throw new AppInstallationFailException('No devices/emulators found. ADB output: ' . $output);
        }

        Log::channel('devlog')->info('ADB server check completed successfully', [
            'total_duration_seconds' => round(microtime(true) - $start_time, 2),
            'process_id' => $this->process_id
        ]);
    }

    /**
     * @brief TODO
     * @param Emulator $emulator
     * @param string $file_path Path to apk file intended for analysis
     * @return void
     * @throws AppInstallationFailException
     */
    private function installAPK(Emulator $emulator, string $file_path) : void {
        // First, check if ADB server is running and devices are available
        $this->ensureAdbServerRunning($emulator);
        
        $command = 'adb install '.$file_path;

        if (env("ENVIRONMENT", "local") == "server"){
            $command = 'docker exec '.$emulator->name.' '.$command;
        }

        Log::channel('devlog')->info('ADB INSTALL command {command}', ['command' => $command]);

        $process = Process::fromShellCommandline($command);
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            Log::channel('devlog')->error('ADB INSTALL failed: {error}', ['error' => $process->getErrorOutput()]);
            throw new AppInstallationFailException($process->getErrorOutput());
        }
    }

    protected function extractXAPKfile(string $file_path) : string {
        Log::channel('devlog')->info('FILE PATH {path}', ['path' => $file_path]);
        $xapk_file = Storage::path($file_path);
        Log::channel('devlog')->info('XAPK FILE PATH {path}', ['path' => $xapk_file]);

        // create tmp folder
        $tmp_folder_name = uniqid() . pathinfo($xapk_file, PATHINFO_FILENAME);
        $xapk_unzipped_path = storage_path(TMP_XAPK_PATH . $tmp_folder_name);

        // Unzip the .xapk file
        $zip = new ZipArchive;
        if ($zip->open($xapk_file) === TRUE) {
            $zip->extractTo($xapk_unzipped_path);
            $zip->close();
        } else {
            Storage::deleteDirectory($xapk_unzipped_path);
            throw new XapkFileExtractException('Failed to unzip .xapk file!');
        }

        return $xapk_unzipped_path;
    }


    /**
     * @brief TODO
     * @param Emulator $emulator
     * @param string $file_path Path to apk file intended for analysis
     * @return void
     * @throws AppInstallationFailException
     */
    private function installXAPK(Emulator $emulator, string $xapk_folder_path) : void {
        // First, check if ADB server is running and devices are available
        $this->ensureAdbServerRunning($emulator);

        // Ensure the folder path has a trailing slash
        $folder_path = rtrim($xapk_folder_path, '/') . '/';

        // Get all .apk files from the unzipped directory
        $apk_files = glob($folder_path . '*.apk');

        if (empty($apk_files)) {
            throw new AppInstallationFailException('No APK files found in the .xapk package!');
        }

        // update path for emulator
        $apk_files = array_map(function ($path) {
            return preg_replace('~^.*(?=/storage)~', '/mnt', $path);
        }, $apk_files);

        Log::channel('devlog')->info('APK FILES FROM XAPK: {xapk_files}', ['xapk_files' => $apk_files]);

        // Use adb install-multiple for multiple APK files
        $static_part_command = ['docker', 'exec', $emulator->name, 'adb', 'install-multiple'];
        $command = array_merge($static_part_command, $apk_files);
        $process = new Process($command);
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            Log::channel('devlog')->error('XAPK INSTALL failed: {error}', ['error' => $process->getErrorOutput()]);
            throw new AppInstallationFailException('Failed to install APKs: ' . $process->getErrorOutput());
        }
    }


    /**
     * @brief The function ensures app installation on emulator
     * @param Emulator $emulator Selected emulator
     * @param string $input_file_path
     * @return void
     * @throws AppInstallationFailException
     */
    protected function installAppOnEmulator(Emulator $emulator, string $input_path, string $input_type) : void {

        if ($input_type == "XAPK") {
            // Handle .xapk installation
            $this->installXAPK($emulator, $input_path);
        } elseif ($input_type == "APK") {
            // Handle .apk installation
            $this->installAPK($emulator, $input_path);
        }
    }

    /**
     * @brief The function ensures app uninstallation on emulator
     * @param Emulator $emulator Selected emulator
     * @param string $package_name Application package name
     * @return void
     * @throws AppUninstallationFailException
     */
    protected function uninstallAppOnEmulator(Emulator $emulator, string $package_name) : void {

        $command = 'adb uninstall '.$package_name;

        if (env("ENVIRONMENT", "local") == "server"){
            $command = 'docker exec '.$emulator->name.' '.$command;
        }

        $process = Process::fromShellCommandline($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new AppUninstallationFailException($process->getErrorOutput());
        }
    }

    /**
     * @brief The function ensures getting an application package name
     * @param Emulator $emulator Selected emulator
     * @param string $apk_file_path Path to apk file intended for analysis
     * @return string Application package name
     * @throws PackageNameNotFoundException
     */
    protected function getAppPackageName(Emulator $emulator, string $apk_file_path) : string {

        $command = "aapt dump badging ".trim($apk_file_path)." | grep 'package: name' | awk -F \"'\" '{print $2}'";

        if (env("ENVIRONMENT", "local") == "server"){
            $command = 'docker exec '.$emulator->name.' '.$command;
        }

        Log::channel('devlog')->info('aapt command: {command}', ['package_name' => $command]);

        $process = Process::fromShellCommandline($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new PackageNameNotFoundException($process->getErrorOutput());
        }

        Log::channel('devlog')->info('package name: {package_name}', ['package_name' => $process->getOutput()]);

        return $process->getOutput();
    }

    /**
     * @brief The function ensures getting an application version
     * @param Emulator $emulator Selected emulator
     * @param string $apk_file_path Path to apk file intended for analysis
     * @return string Application version
     * @throws AppVersionNotFoundException
     */
    protected function getAppVersionName(Emulator $emulator, string $apk_file_path) : string {

        $command = 'aapt dump badging '.trim($apk_file_path);
        $command = $command.' | grep package | awk \'{print $4}\' | sed s/versionName=//g | sed s/\\\'//g';

        if (env("ENVIRONMENT", "local") == "server"){
            $command = 'docker exec '.$emulator->name.' '.$command;
        }

        $process = Process::fromShellCommandline($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new AppVersionNotFoundException($process->getErrorOutput());
        }

        return $process->getOutput();
    }

    /**
     * @brief The function ensures getting an application name
     * @param Emulator $emulator Selected emulator
     * @param string $apk_file_path Path to apk file intended for analysis
     * @return string Application name
     * @throws AppNameNotFoundException
     */
    protected function getAppName(Emulator $emulator, string $apk_file_path) : string {

        $command = 'aapt dump badging '.trim($apk_file_path).' | sed -n "s/^application-label:\'\(.*\)\'/\1/p"';

        if (env("ENVIRONMENT", "local") == "server"){
            $command = 'docker exec '.$emulator->name.' '.$command;
        }

        $process = Process::fromShellCommandline($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new AppNameNotFoundException($process->getErrorOutput());
        }

        return $process->getOutput();
    }

    /**
     * @brief The function ensures getting preinstalled apps
     * @return array Preinstalled apps package names
     * @throws LoadingPreinstalledAppsFailed
     */
    protected function getPreInstalledApps(): array {

        try {
            $file = base_path(PRE_INSTALLED_APPS_FILE);
            $packages = [];

            $file_handle = fopen($file, "r");

            if($file_handle){

                while(($line = fgets($file_handle)) !== false){
                    $packages[] = trim($line);
                }

                fclose($file_handle);
                $response = [];

                foreach($packages as $package){
                    $response[] = str_replace("package:", "", $package);
                }

                return $response;
            }else {
                throw new Exception("Error opening file!");
            }

        } catch(Exception $e){
            throw new LoadingPreinstalledAppsFailed($e);
        }
    }

    /**
     * @brief The function ensures saving hashes to database
     * @param array $data Database data
     * @return void
     */
    protected function saveHashes(array $data) : void {

        Log::channel('devlog')->info('Starting to save hashes to database for process: {process_id}', ['process_id' => $this->process_id]);
        
        try {
            $process = ProcessModel::where('job_id', '=', $this->process_id)->first();
            
            if (!$process) {
                Log::channel('devlog')->error('Process not found for job_id: {job_id}', ['job_id' => $this->process_id]);
                throw new Exception('Process not found for job_id: ' . $this->process_id);
            }
            
            $process_id = $process->id;
            Log::channel('devlog')->info('Found process with ID: {process_id}', ['process_id' => $process_id]);
        } catch (Exception $e) {
            Log::channel('devlog')->error('Database error when finding process: {error}', ['error' => $e->getMessage()]);
            throw $e;
        }

        $identifier = [
            'name' => $data['app_name'],
            'package_name' => $data['package_name'],
            'version' => $data['version'],
        ];

        $new_application = [
            'name' => $data['app_name'],
            'package_name' => $data['package_name'],
            'version' => $data['version'],
        ];

        try {
            $application = Application::firstOrCreate($identifier, $new_application);
            Log::channel('devlog')->info('Application created/found with ID: {app_id}', ['app_id' => $application->id]);
        } catch (Exception $e) {
            Log::channel('devlog')->error('Database error when creating/finding application: {error}', ['error' => $e->getMessage()]);
            throw $e;
        }

        foreach($this->files as $file){
            try {
                $db_file = File::create([
                    'name' => $file['name'],
                    'type' => $file['type'],
                    'path' => $file['path'],
                    'app_id' => $application->id,
                ]);
                $db_file->save();
                Log::channel('devlog')->info('File saved with ID: {file_id}', ['file_id' => $db_file->id]);
            } catch (Exception $e) {
                Log::channel('devlog')->error('Database error when saving file: {error}', ['error' => $e->getMessage()]);
                throw $e;
            }
        }

        foreach($data["hashes"] as $hash){

            Log::channel('devlog')->info('Hashes : {name}', ['name' => $hash]);
            Log::channel('devlog')->info('SNI Flag: {flag}, Is Flagged: {flagged}', [
                'flag' => $hash->sni_flag ?? 'null',
                'flagged' => $hash->is_flagged ?? 'null'
            ]);

            // Extract custom hashes from the hash object
            $custom_hashes = [];
            foreach($hash as $key => $value) {
                if(strpos($key, 'custom_') === 0) {
                    $custom_hashes[$key] = $value;
                }
            }

            $new_record = [
                'app_id' => $application->id,
                'process_id' => $process_id,
                'ja3_hash' => $hash->ja3_hash,
                'ja3s_hash' => $hash->ja3s_hash,
                'sni' => $hash->sni,
                'sni_flag' => isset($hash->sni_flag) ? $hash->sni_flag : null,
                'is_flagged' => isset($hash->is_flagged) ? (bool)$hash->is_flagged : false,
                'ja4_hash' => $hash->ja4_hash,
                'ja4s_hash' => $hash->ja4s_hash,
                'ja4x_hash' => $hash->ja4x_hash,
                'custom_hashes' => !empty($custom_hashes) ? $custom_hashes : null,
                'ip_src' => $hash->ip_src,
                'port_src' => $hash->port_src,
                'ip_dest' => $hash->ip_dest,
                'port_dest' => $hash->port_dest,
            ];
            
            Log::channel('devlog')->info('Saving hash with flag: {flag}, flagged: {flagged}', [
                'flag' => $new_record['sni_flag'],
                'flagged' => $new_record['is_flagged']
            ]);

            try {
                $db_hash = Hash::create($new_record);
                $db_hash->save();
                Log::channel('devlog')->info('Hash saved with ID: {hash_id}', ['hash_id' => $db_hash->id]);
            } catch (Exception $e) {
                Log::channel('devlog')->error('Database error when saving hash: {error}', ['error' => $e->getMessage()]);
                Log::channel('devlog')->error('Hash data that failed: {hash_data}', ['hash_data' => json_encode($new_record)]);
                throw $e;
            }
        }
    }

    /**
     * @brief The function ensures deleting APK file after hash creation
     * @param string $file_path APK file path
     * @return void
     */
    protected function delete_apk_file(string $file_path): void {

        $relative_path = substr($file_path,
            strpos($file_path, '/storage/app') + strlen('/storage/app'));

        if (Storage::exists($relative_path)) {
            Storage::delete($relative_path);
            DB::table('files')->where('files.path', '=', $file_path)->delete();
        }
    }

    /**
     * @brief The function ensures the getting free emulator from database
     * @return mixed
     */
    protected function get_free_emulator(): mixed {
        return Emulator::where("is_working", "=", false)->first();
    }

    /**
     * @brief The function ensures the setting emulator state to database
     * @param Emulator $emulator Emulator data object
     * @param bool $state New emulator state
     * @return void
     */
    protected function set_emulator_working_state(Emulator $emulator, bool $state): void {
        Emulator::where("name",$emulator->name)->update(["is_working" => $state]);
    }
}
