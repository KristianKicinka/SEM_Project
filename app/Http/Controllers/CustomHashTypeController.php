<?php
/**
 * @file CustomHashTypeController.php
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 */

namespace App\Http\Controllers;

use App\Models\CustomHashType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CustomHashTypeController extends Controller
{
    /**
     * @brief Display a listing of custom hash types
     * @param Request $request
     * @return \Illuminate\View\View|JsonResponse
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Get user's custom hash types and public ones
        $customHashTypes = CustomHashType::where(function($query) use ($user) {
            $query->where('user_id', $user->id)
                  ->orWhere('is_public', true);
        })
        ->active()
        ->orderBy('created_at', 'desc')
        ->paginate(10);

        if ($request->expectsJson()) {
            return response()->json($customHashTypes);
        }

        return view('admin.custom-hash-types.index', compact('customHashTypes'));
    }

    /**
     * @brief Show the form for creating a new custom hash type
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('admin.custom-hash-types.create');
    }

    /**
     * @brief Store a newly created custom hash type
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:custom_hash_types,name',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:simple_tls,custom_algorithm,python_script',
            'configuration' => 'required',
            'script_file' => 'nullable|file|mimes:py|max:1024',
            'is_public' => 'nullable',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $user = Auth::user();
        $data = $request->only(['name', 'display_name', 'description', 'type']);
        $data['configuration'] = $this->normalizeConfiguration($request->input('configuration'));
        $data['is_public'] = $this->booleanInput($request, 'is_public', false);
        $data['user_id'] = $user->id;
        $data['is_active'] = true;
        $data['usage_count'] = 0;

        // Handle script file upload
        if ($request->hasFile('script_file') && $data['type'] === 'python_script') {
            $scriptFile = $request->file('script_file');
            $fileName = Str::slug($data['name']) . '_' . time() . '.py';
            $scriptPath = 'scripts/custom_generators/' . $fileName;
            
            // Store the file
            Storage::disk('local')->put($scriptPath, file_get_contents($scriptFile->getRealPath()));
            $data['script_path'] = $scriptPath;
        }

        // Validate configuration based on type
        if (!$this->validateConfiguration($data['type'], $data['configuration'])) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Invalid configuration for the selected type'], 422);
            }
            return redirect()->back()->withErrors(['configuration' => 'Invalid configuration for the selected type'])->withInput();
        }

        $customHashType = CustomHashType::create($data);
        $customHashType->configuration = $this->normalizeConfiguration($customHashType->configuration);

        if ($request->expectsJson()) {
            return response()->json($customHashType, 201);
        }

        return redirect()->route('custom-hash-types.index')
            ->with('success', 'Custom hash type created successfully.');
    }

    /**
     * @brief Display the specified custom hash type
     * @param CustomHashType $customHashType
     * @return \Illuminate\View\View|JsonResponse
     */
    public function show(CustomHashType $customHashType)
    {
        $this->authorize('view', $customHashType);

        $customHashType->configuration = $this->normalizeConfiguration($customHashType->configuration);

        if (request()->expectsJson()) {
            return response()->json($customHashType);
        }

        return view('admin.custom-hash-types.show', compact('customHashType'));
    }

    /**
     * @brief Show the form for editing the specified custom hash type
     * @param CustomHashType $customHashType
     * @return \Illuminate\View\View
     */
    public function edit(CustomHashType $customHashType)
    {
        $this->authorize('update', $customHashType);

        return view('admin.custom-hash-types.edit', compact('customHashType'));
    }

    /**
     * @brief Update the specified custom hash type
     * @param Request $request
     * @param CustomHashType $customHashType
     * @return \Illuminate\Http\RedirectResponse|JsonResponse
     */
    public function update(Request $request, CustomHashType $customHashType)
    {
        $this->authorize('update', $customHashType);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:custom_hash_types,name,' . $customHashType->id,
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:simple_tls,custom_algorithm,python_script',
            'configuration' => 'required',
            'script_file' => 'nullable|file|mimes:py|max:1024',
            'is_public' => 'nullable',
            'is_active' => 'nullable',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $request->only(['name', 'display_name', 'description', 'type']);
        $data['configuration'] = $this->normalizeConfiguration($request->input('configuration'));
        $data['is_public'] = $this->booleanInput($request, 'is_public', (bool) $customHashType->is_public);
        $data['is_active'] = $this->booleanInput($request, 'is_active', (bool) $customHashType->is_active);

        // Handle script file upload
        if ($request->hasFile('script_file') && $data['type'] === 'python_script') {
            // Delete old script file
            if ($customHashType->script_path) {
                Storage::disk('local')->delete($customHashType->script_path);
            }

            $scriptFile = $request->file('script_file');
            $fileName = Str::slug($data['name']) . '_' . time() . '.py';
            $scriptPath = 'scripts/custom_generators/' . $fileName;
            
            Storage::disk('local')->put($scriptPath, file_get_contents($scriptFile->getRealPath()));
            $data['script_path'] = $scriptPath;
        }

        // Validate configuration
        if (!$this->validateConfiguration($data['type'], $data['configuration'])) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Invalid configuration for the selected type'], 422);
            }
            return redirect()->back()->withErrors(['configuration' => 'Invalid configuration for the selected type'])->withInput();
        }

        $customHashType->update($data);
        $customHashType->refresh();
        $customHashType->configuration = $this->normalizeConfiguration($customHashType->configuration);

        if ($request->expectsJson()) {
            return response()->json($customHashType);
        }

        return redirect()->route('custom-hash-types.index')
            ->with('success', 'Custom hash type updated successfully.');
    }

    /**
     * @brief Remove the specified custom hash type
     * @param CustomHashType $customHashType
     * @return \Illuminate\Http\RedirectResponse|JsonResponse
     */
    public function destroy(CustomHashType $customHashType)
    {
        $this->authorize('delete', $customHashType);

        // Delete script file if exists
        if ($customHashType->script_path) {
            Storage::disk('local')->delete($customHashType->script_path);
        }

        $customHashType->delete();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Custom hash type deleted successfully']);
        }

        return redirect()->route('custom-hash-types.index')
            ->with('success', 'Custom hash type deleted successfully.');
    }

    /**
     * @brief Test a custom hash type with uploaded files
     * @param Request $request
     * @param CustomHashType $customHashType
     * @return JsonResponse
     */
    public function test(Request $request, CustomHashType $customHashType)
    {
        $this->authorize('view', $customHashType);

        $validator = Validator::make($request->all(), [
            'apk_files' => 'nullable|array',
            'apk_files.*' => 'file|mimes:apk|max:10240',
            'package_names' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $results = [];

            // Test with APK files
            if ($request->hasFile('apk_files')) {
                foreach ($request->file('apk_files') as $apkFile) {
                    $result = $this->testWithApk($customHashType, $apkFile);
                    $results[] = [
                        'type' => 'apk',
                        'filename' => $apkFile->getClientOriginalName(),
                        'result' => $result
                    ];
                }
            }

            // Test with package names
            if ($request->filled('package_names')) {
                $packageNames = array_filter(explode("\n", $request->input('package_names')));
                foreach ($packageNames as $packageName) {
                    $packageName = trim($packageName);
                    if (!empty($packageName)) {
                        $result = $this->testWithPackageName($customHashType, $packageName);
                        $results[] = [
                            'type' => 'package_name',
                            'package_name' => $packageName,
                            'result' => $result
                        ];
                    }
                }
            }

            return response()->json([
                'message' => 'Test completed successfully',
                'results' => $results
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Test failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * @brief Get available custom hash types for API
     * @return JsonResponse
     */
    public function apiIndex()
    {
        $user = Auth::user();

        // Owners see all of their types (including inactive) so the edit form can
        // reload configuration/flags. Public types from other users stay active-only.
        $customHashTypes = CustomHashType::where(function ($query) use ($user) {
            $query->where('user_id', $user->id)
                  ->orWhere(function ($public) {
                      $public->where('is_public', true)->where('is_active', true);
                  });
        })
        ->orderBy('created_at', 'desc')
        ->get();

        $customHashTypes->transform(function (CustomHashType $hashType) {
            $hashType->configuration = $this->normalizeConfiguration($hashType->configuration);
            return $hashType;
        });

        return response()->json($customHashTypes);
    }

    /**
     * @brief Get custom hash types for Python hash generator
     * @param Request $request
     * @return JsonResponse
     */
    public function getForPythonGenerator(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'names' => 'nullable|array',
            'names.*' => 'string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $requestedNames = $request->input('names', []);
        
        $query = CustomHashType::where('is_active', true);
        
        // Ak sú zadané konkrétne názvy, filtruj podľa nich
        if (!empty($requestedNames)) {
            $query->whereIn('name', $requestedNames);
        }
        
        $customHashTypes = $query->select(
            'name', 
            'display_name', 
            'description', 
            'type', 
            'configuration', 
            'script_path'
        )->get();

        // Transformuj dáta pre Python generátor
        $generators = [];
        foreach ($customHashTypes as $hashType) {
            $generator = [
                'name' => $hashType->name,
                'display_name' => $hashType->display_name,
                'description' => $hashType->description,
                'type' => $hashType->type,
                'configuration' => $this->normalizeConfiguration($hashType->configuration),
            ];
            
            // Pre Python script pridaj script_path
            if ($hashType->type === 'python_script' && $hashType->script_path) {
                $generator['script_path'] = $hashType->script_path;
            }
            
            $generators[] = $generator;
        }

        return response()->json([
            'generators' => $generators,
            'count' => count($generators)
        ]);
    }

    /**
     * @brief Validate configuration based on type
     * @param string $type
     * @param array $configuration
     * @return bool
     */
    private function validateConfiguration(string $type, array $configuration): bool
    {
        switch ($type) {
            case 'simple_tls':
                return isset($configuration['fields']) && is_array($configuration['fields']) && count($configuration['fields']) > 0;
            case 'custom_algorithm':
                return isset($configuration['algorithm']) && is_string($configuration['algorithm']) && $configuration['algorithm'] !== ''
                    && isset($configuration['fields']) && is_array($configuration['fields']) && count($configuration['fields']) > 0;
            case 'python_script':
                return true; // Python script validation is done by file upload
            default:
                return false;
        }
    }

    /**
     * @brief Decode configuration that may arrive as a JSON string or PHP array
     * @param mixed $configuration
     * @return array
     */
    private function normalizeConfiguration($configuration): array
    {
        if (is_array($configuration)) {
            if (isset($configuration['fields']) && is_string($configuration['fields'])) {
                $decodedFields = json_decode($configuration['fields'], true);
                if (is_array($decodedFields)) {
                    $configuration['fields'] = $decodedFields;
                }
            }
            return $configuration;
        }

        if (is_string($configuration) && $configuration !== '') {
            $decoded = json_decode($configuration, true);
            if (is_array($decoded)) {
                return $this->normalizeConfiguration($decoded);
            }
        }

        return [];
    }

    /**
     * @brief Parse JSON/form boolean values without dropping false
     * @param Request $request
     * @param string $key
     * @param bool $default
     * @return bool
     */
    private function booleanInput(Request $request, string $key, bool $default): bool
    {
        if (!$request->exists($key)) {
            return $default;
        }

        $value = $request->input($key);
        if (is_bool($value)) {
            return $value;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @brief Test custom hash type with APK file
     * @param CustomHashType $customHashType
     * @param \Illuminate\Http\UploadedFile $apkFile
     * @return array
     */
    private function testWithApk(CustomHashType $customHashType, $apkFile): array
    {
        try {
            // Save APK file temporarily
            $tempFileName = 'test_' . time() . '_' . $apkFile->getClientOriginalName();
            $tempPath = storage_path('app/temp/' . $tempFileName);
            
            // Ensure temp directory exists
            if (!file_exists(dirname($tempPath))) {
                mkdir(dirname($tempPath), 0755, true);
            }
            
            $apkFile->move(dirname($tempPath), $tempFileName);
            
            // Create a test PCAP file (simplified version)
            $pcapFileName = str_replace('.apk', '.pcap', $tempFileName);
            $pcapPath = storage_path('app/temp/' . $pcapFileName);
            
            // Generate a simple test PCAP file
            $this->generateTestPcapFile($pcapPath);
            
            // Test the custom hash type with the PCAP file
            $result = $this->testCustomHashTypeWithPcap($customHashType, $pcapPath);
            
            // Clean up temporary files
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
            if (file_exists($pcapPath)) {
                unlink($pcapPath);
            }
            
            return $result;
            
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'APK test failed: ' . $e->getMessage(),
                'hashes_generated' => 0,
                'custom_hash_type_used' => $customHashType->name
            ];
        }
    }

    /**
     * @brief Test custom hash type with package name
     * @param CustomHashType $customHashType
     * @param string $packageName
     * @return array
     */
    private function testWithPackageName(CustomHashType $customHashType, string $packageName): array
    {
        try {
            // For package name testing, we'll create a mock test
            // In a real implementation, this would trigger the actual app analysis
            
            // Generate a test PCAP file for the package
            $pcapFileName = 'test_' . time() . '_' . str_replace('.', '_', $packageName) . '.pcap';
            $pcapPath = storage_path('app/temp/' . $pcapFileName);
            
            // Ensure temp directory exists
            if (!file_exists(dirname($pcapPath))) {
                mkdir(dirname($pcapPath), 0755, true);
            }
            
            // Generate a simple test PCAP file
            $this->generateTestPcapFile($pcapPath);
            
            // Test the custom hash type with the PCAP file
            $result = $this->testCustomHashTypeWithPcap($customHashType, $pcapPath);
            
            // Clean up temporary files
            if (file_exists($pcapPath)) {
                unlink($pcapPath);
            }
            
            return $result;
            
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Package name test failed: ' . $e->getMessage(),
                'hashes_generated' => 0,
                'custom_hash_type_used' => $customHashType->name
            ];
        }
    }

    /**
     * @brief Generate a simple test PCAP file
     * @param string $pcapPath
     * @return void
     */
    private function generateTestPcapFile(string $pcapPath): void
    {
        // Create a simple test PCAP file with basic TLS traffic
        // This is a simplified version - in reality, you'd use a proper PCAP generator
        $testData = "Test PCAP data for custom hash type testing";
        file_put_contents($pcapPath, $testData);
    }

    /**
     * @brief Test custom hash type with PCAP file
     * @param CustomHashType $customHashType
     * @param string $pcapPath
     * @return array
     */
    private function testCustomHashTypeWithPcap(CustomHashType $customHashType, string $pcapPath): array
    {
        try {
            // Prepare the command to run hash_generator.py with custom hash type
            $pythonCommand = env("PYTHON_COMMAND", "python3");
            $hashScriptPath = base_path('scripts/hash_generator.py');
            $customGenerators = json_encode([$customHashType->name]);
            
            // Set environment variables for database connection
            $env = [
                'LARAVEL_BASE_URL' => config('app.url'),
                'LARAVEL_API_KEY' => $this->generateApiKeyForPython()
            ];
            
            $command = "{$pythonCommand} {$hashScriptPath} {$pcapPath} {$customGenerators}";
            
            // Execute the command with environment variables
            $descriptorspec = [
                0 => ["pipe", "r"],
                1 => ["pipe", "w"],
                2 => ["pipe", "w"]
            ];
            
            $process = proc_open($command, $descriptorspec, $pipes, null, $env);
            
            if (!is_resource($process)) {
                return [
                    'status' => 'error',
                    'message' => 'Failed to start hash generator process',
                    'hashes_generated' => 0,
                    'custom_hash_type_used' => $customHashType->name
                ];
            }
            
            fclose($pipes[0]);
            $output = stream_get_contents($pipes[1]);
            $error = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            
            $returnValue = proc_close($process);
            
            if ($returnValue !== 0) {
                return [
                    'status' => 'error',
                    'message' => 'Hash generator failed: ' . $error,
                    'hashes_generated' => 0,
                    'custom_hash_type_used' => $customHashType->name
                ];
            }
            
            // Parse the output
            $results = json_decode($output, true);
            
            if ($results === null) {
                return [
                    'status' => 'error',
                    'message' => 'Invalid JSON output from hash generator',
                    'hashes_generated' => 0,
                    'custom_hash_type_used' => $customHashType->name
                ];
            }
            
            // Count hashes generated with this custom hash type
            $hashesGenerated = 0;
            foreach ($results as $result) {
                $customHashKey = 'custom_' . $customHashType->name;
                if (isset($result[$customHashKey]) && !empty($result[$customHashKey])) {
                    $hashesGenerated++;
                }
            }
            
            // Increment usage count
            $customHashType->incrementUsage();
            
            return [
                'status' => 'success',
                'message' => 'Test completed successfully',
                'hashes_generated' => $hashesGenerated,
                'custom_hash_type_used' => $customHashType->name,
                'total_results' => count($results)
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Test failed: ' . $e->getMessage(),
                'hashes_generated' => 0,
                'custom_hash_type_used' => $customHashType->name
            ];
        }
    }
    
    /**
     * @brief Generate API key for Python script authentication
     * @return string
     */
    private function generateApiKeyForPython(): string
    {
        // For now, return a simple API key
        // In production, you should use proper API authentication
        return 'python_hash_generator_key_' . time();
    }
}
