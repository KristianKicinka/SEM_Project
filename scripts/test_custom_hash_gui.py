#!/usr/bin/env python3
"""
Test script to verify custom hash generation works correctly
This simulates the exact command that Laravel runs for custom hash generation
"""

import os
import sys
import json
import subprocess
from pathlib import Path

def test_custom_hash_generation():
    """Test custom hash generation with the same parameters as Laravel"""
    
    # Set environment variables (same as Laravel)
    os.environ['LARAVEL_BASE_URL'] = 'http://localhost:8000'
    os.environ['LARAVEL_API_KEY'] = 'python_hash_generator_key_base64:4R8IeTr48CfBRNHIZTos1t12f/gElEnkxmJTOwjIY4k='
    os.environ['USE_MANUAL_CONFIG'] = 'false'
    
    # Find a PCAP file to test with
    pcap_dir = Path('/home/xbwolf02/www/sem_project/storage/app/public/pcaps')
    pcap_files = list(pcap_dir.glob('*.pcap'))
    
    if not pcap_files:
        print("❌ No PCAP files found in storage/app/public/pcaps/")
        return False
    
    pcap_file = pcap_files[0]
    print(f"📁 Testing with PCAP file: {pcap_file}")
    
    # Test with custom hash types
    custom_generators = ['CUSTOM_TLS_01']
    
    # Build command (same as Laravel)
    script_path = Path('/home/xbwolf02/www/sem_project/scripts/hash_generator.py')
    command = [
        'python3.11',
        str(script_path),
        str(pcap_file),
        json.dumps(custom_generators)
    ]
    
    print(f"🔧 Command: {' '.join(command)}")
    print(f"🌍 Environment:")
    print(f"   LARAVEL_BASE_URL: {os.environ.get('LARAVEL_BASE_URL')}")
    print(f"   LARAVEL_API_KEY: {os.environ.get('LARAVEL_API_KEY')}")
    print(f"   USE_MANUAL_CONFIG: {os.environ.get('USE_MANUAL_CONFIG')}")
    
    try:
        # Run the command
        print("\n🚀 Running Python script...")
        result = subprocess.run(
            command,
            capture_output=True,
            text=True,
            timeout=300  # 5 minutes timeout
        )
        
        print(f"📊 Exit code: {result.returncode}")
        
        if result.returncode == 0:
            print("✅ Python script executed successfully!")
            
            # Parse output
            try:
                output_data = json.loads(result.stdout)
                print(f"📈 Generated {len(output_data)} hash records")
                
                # Check for custom hashes
                custom_hash_count = 0
                for record in output_data:
                    if 'custom_CUSTOM_TLS_01' in record and record['custom_CUSTOM_TLS_01'] is not None:
                        custom_hash_count += 1
                
                print(f"🎯 Custom hashes generated: {custom_hash_count}")
                
                if custom_hash_count > 0:
                    print("✅ Custom hash generation is working correctly!")
                    return True
                else:
                    print("❌ No custom hashes were generated")
                    return False
                    
            except json.JSONDecodeError as e:
                print(f"❌ Failed to parse JSON output: {e}")
                print(f"Raw output: {result.stdout[:500]}...")
                return False
        else:
            print("❌ Python script failed!")
            print(f"Error output: {result.stderr}")
            return False
            
    except subprocess.TimeoutExpired:
        print("❌ Python script timed out after 5 minutes")
        return False
    except Exception as e:
        print(f"❌ Error running Python script: {e}")
        return False

def test_database_connection():
    """Test if we can connect to the database API"""
    import requests
    
    base_url = os.environ.get('LARAVEL_BASE_URL', 'http://localhost:8000')
    api_key = os.environ.get('LARAVEL_API_KEY', 'python_hash_generator_key_base64:4R8IeTr48CfBRNHIZTos1t12f/gElEnkxmJTOwjIY4k=')
    
    try:
        response = requests.post(
            f"{base_url}/api/custom-hash-types/python-generator",
            headers={'Authorization': f'Bearer {api_key}'},
            json={'names': ['CUSTOM_TLS_01']},
            timeout=10
        )
        
        if response.status_code == 200:
            data = response.json()
            print(f"✅ Database connection successful! Found {len(data)} custom hash types")
            return True
        else:
            print(f"❌ Database connection failed: {response.status_code}")
            print(f"Response: {response.text}")
            return False
            
    except Exception as e:
        print(f"❌ Database connection error: {e}")
        return False

if __name__ == "__main__":
    print("🧪 Custom Hash GUI Test")
    print("=" * 50)
    
    # Test database connection first
    print("\n1️⃣ Testing database connection...")
    db_ok = test_database_connection()
    
    if not db_ok:
        print("\n❌ Database connection failed. Cannot proceed with hash generation test.")
        sys.exit(1)
    
    # Test custom hash generation
    print("\n2️⃣ Testing custom hash generation...")
    hash_ok = test_custom_hash_generation()
    
    if hash_ok:
        print("\n🎉 All tests passed! Custom hash generation should work in GUI.")
    else:
        print("\n💥 Tests failed! Custom hash generation will not work in GUI.")
        sys.exit(1)
