<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

use Docker\Docker;
use Docker\API\Model\NetworksCreatePostBody;
use Docker\API\Model\ExecIdStartPostBody;
use Docker\API\Model\ContainersCreatePostBody;
use Docker\API\Model\HostConfig;
use Docker\API\Model\NetworkingConfig;
use Docker\API\Model\EndpointSettings;

use Symfony\Component\Process\Process;

use App\Models\Emulator;
use Exception;

class EmulatorController extends Controller
{

    protected $docker;

    public function __construct()
    {
        // Initialize Docker client
        $this->docker = Docker::create();
    }

    /**
     * @brief The function ensures the getting all emulators from database
     * @return JsonResponse List of emulators from database
     */
    public function getEmulatorsForAdmin(): JsonResponse {

        $emulators = DB::table('emulators')->get();
        return response()->json($emulators);
    }


    public function createEmulator(Request $request): JsonResponse {

        // Debug: Log the request data
        \Log::info('Emulator create request:', $request->all());

        $validator = Validator::make($request->all(), [
            'container_name' => 'required|string',
            'network_name' => 'required|string',
            'mount_source' => 'required|string',
            'mount_target' => 'required|string',
            'image' => 'required|string',
            'memory' => 'required|integer|min:128',
            'cpu_count' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        try {

            $mountTarget = $request->mount_target;
            $mountSource = $request->mount_source;
            
            // Expand tilde to absolute path
            if (strpos($mountSource, '~') === 0) {
                $mountSource = str_replace('~', $_SERVER['HOME'] ?? getenv('HOME'), $mountSource);
            }
            
            // Validate that mount source exists
            if (!is_dir($mountSource)) {
                return response()->json([
                    'message' => 'Mount source directory does not exist',
                    'error' => "Directory '{$mountSource}' not found"
                ], 400);
            }

            $memory = (int) $request->memory * 1024 * 1024; // Convert MB to bytes
            $cpu_count = (int) $request->cpu_count;

            $image = $request->image;

            // Create the Docker network
            $networkBody = new NetworksCreatePostBody();
            $networkBody->setName($request->network_name);
            $networkBody->setDriver('bridge');
 
            $network = $this->docker->networkCreate($networkBody);

            // Inspect the network to get its ID
            $networkDetails = $this->docker->networkInspect($request->network_name);
            $networkId = $networkDetails->getId();

            // Configure networking settings
            $endpointSettings = new EndpointSettings();
            $endpointSettings->setNetworkID($networkId);
   
            $networkingConfig = new NetworkingConfig();
            $networkingConfig->setEndpointsConfig([
                $request->network_name => $endpointSettings,
            ]);

            // Configure host settings for the container
            $hostConfig = new HostConfig();
            $hostConfig->setPrivileged(true)
                ->setBinds(["$mountSource:$mountTarget"])
                ->setMemory($memory)
                ->setCpuQuota($cpu_count * 100000);
                
            // Debug: Log Docker configuration
            \Log::info('Docker configuration:', [
                'mount_source' => $mountSource,
                'mount_target' => $mountTarget,
                'memory' => $memory,
                'cpu_count' => $cpu_count,
                'image' => $image,
                'container_name' => $request->container_name
            ]);


            // Create the container
            $containerBody = new ContainersCreatePostBody();
            $containerBody->setImage($image)
                ->setHostConfig($hostConfig)
                ->setNetworkingConfig($networkingConfig);
 
            $container = $this->docker->containerCreate(
                $containerBody,
                ['name' => $request->container_name] // Query parameters
            );
 
            // Start the container
            $this->docker->containerStart($container->getId());

            // Save data to database
            $emulator = Emulator::create([
                'name' => $request->container_name,
                'network_interface' => "br-".substr($networkId, 0, 12),
                'docker_id' => substr($container->getId(), 0, 12),
                'memory' => (int) $request->memory,
                'cpu_count' => $cpu_count,
                'image' => $image
            ]);
    
            $emulator->save();
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Docker operation failed',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 400);
        }

        return response()->json(['status' => 'success'], 200);
    }

    /**
     * @brief The function ensures the deleting of specified emulator from system
     * @param Request $request HTTP request data
     * @return JsonResponse Operation status message
     */
    public function deleteEmulator(Request $request): JsonResponse {

        $validator = Validator::make($request->all(), [
            'container_name' => 'required|string',
            'network_name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $containerName = $request->input('container_name');
        $networkName = substr($request->input('network_name'), 3);

        // Step 1: Stop the container (if running)
        $containerDetails = $this->docker->containerInspect($containerName);
        if ($containerDetails->getState()->getRunning()) {
            $this->docker->containerStop($containerName);
        }

        // Step 2: Remove the container
        $this->docker->containerDelete($containerName, ['force' => true]);

        // Step 3: Remove the network (if provided)
        if ($networkName) {
            $this->docker->networkDelete($networkName);
        }

        // Delete data from database
        DB::table('emulators')->where('name','=', $containerName)->delete();

        return response()->json(['status' => 'success'], 200);
    }

    /**
     * @brief The function ensures starting android emulator 
     * @param Request $request HTTP request data
     * @return JsonResponse Operation status message
     */
    public function startEmulator(Request $request): JsonResponse {
        $validator = Validator::make($request->all(), [
            'container_name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $containerName = $request->input('container_name');

        try {
            // Start the container
            $this->docker->containerStart($containerName);
            
        } catch (\Exception $e) {
            return response()->json(['errors' => $e->getMessage()], 400);
        }

        return response()->json(['status' => 'success'], 200);
    }

    /**
     * @brief The function ensures stopping android emulator 
     * @param Request $request HTTP request data
     * @return JsonResponse Operation status message
     */
    public function stopEmulator(Request $request): JsonResponse {
        $validator = Validator::make($request->all(), [
            'container_name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $containerName = $request->input('container_name');

        try {
            // First, check if container is running
            $containerDetails = $this->docker->containerInspect($containerName);
            if (!$containerDetails->getState()->getRunning()) {
                return response()->json(['status' => 'success', 'message' => 'Emulator is already stopped'], 200);
            }

            // Try to stop the emulator gracefully inside the container
            // First try common emulator ports
            $ports = ['emulator-5554', 'emulator-5556', 'emulator-5558'];
            $command = '';
            
            foreach ($ports as $port) {
                $testCommand = 'adb -s ' . $port . ' emu kill';
                if (env("ENVIRONMENT", "local") == "server"){
                    $testCommand = 'docker exec '.$containerName.' '.$testCommand;
                }
                
                $testProcess = Process::fromShellCommandline($testCommand);
                $testProcess->setTimeout(5);
                $testProcess->run();
                
                if ($testProcess->isSuccessful()) {
                    $command = $testCommand;
                    break;
                }
            }
            
            // If no specific port worked, try to kill all emulator processes
            if (empty($command)) {
                $command = 'pkill -f emulator';
                if (env("ENVIRONMENT", "local") == "server"){
                    $command = 'docker exec '.$containerName.' '.$command;
                }
            }

            if (!empty($command)) {
                $process = Process::fromShellCommandline($command);
                $process->setTimeout(10);
                $process->run();
                
                // Log the result
                \Log::info("Stop emulator command: " . $command);
                \Log::info("Command output: " . $process->getOutput());
                \Log::info("Command error: " . $process->getErrorOutput());
            }

            // Wait a moment for graceful shutdown
            sleep(3);

            // Check if container is still running
            $containerDetails = $this->docker->containerInspect($containerName);
            if ($containerDetails->getState()->getRunning()) {
                // If still running, stop the container directly
                \Log::info("Container still running, stopping directly");
                $this->docker->containerStop($containerName);
            }

            return response()->json(['status' => 'success'], 200);
            
        } catch (\Exception $e) {
            // If all else fails, try to stop the container directly
            try {
                $this->docker->containerStop($containerName);
                return response()->json(['status' => 'success'], 200);
            } catch (\Exception $stopException) {
                return response()->json(['errors' => 'Failed to stop emulator: ' . $stopException->getMessage()], 400);
            }
        }
    }


    /**
     * @brief The function ensures getting status of android emulator 
     * @param Request $request HTTP request data
     * @return JsonResponse Operation status message
     */
    public function getEmulatorStatus(Request $request): JsonResponse {
        $validator = Validator::make($request->all(), [
            'container_name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $containerName = $request->input('container_name');

        try {
            // Inspect the container
            $containerDetails = $this->docker->containerInspect($containerName);

            // Retrieve the status from the 'State' field
            $status = $containerDetails->getState()->getStatus();
            
        } catch (\Exception $e) {
            return response()->json(['errors' => $e->getMessage()], 400);
        }

        return response()->json(['status' => 'success', 'container_status' => $status], 200);
    }

}
