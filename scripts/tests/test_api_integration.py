#!/usr/bin/env python3
"""
 * @file test_api_integration.py
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 * 
 * Test skript pre overenie API integrácie bez scapy dependencies
"""

import os
import sys
import json
import requests

def test_api_endpoint():
    """Test API endpoint pre custom hash types"""
    print("Testing API endpoint...")
    
    base_url = os.getenv('LARAVEL_BASE_URL', 'http://localhost:8000')
    api_key = os.getenv('LARAVEL_API_KEY', 'python_hash_generator_key_1234567890')
    
    url = f"{base_url}/api/custom-hash-types/python-generator"
    headers = {
        'Content-Type': 'application/json',
        'Authorization': f'Bearer {api_key}'
    }
    data = {
        'names': ['CUSTOM_TLS_SIMPLE', 'CUSTOM_IP_HASH']
    }
    
    try:
        response = requests.post(url, json=data, headers=headers, timeout=10)
        print(f"Status Code: {response.status_code}")
        
        if response.status_code == 200:
            result = response.json()
            print(f"Response: {json.dumps(result, indent=2)}")
            return True
        else:
            print(f"Error: {response.text}")
            return False
            
    except requests.exceptions.RequestException as e:
        print(f"Request failed: {e}")
        return False

def test_environment():
    """Test environment variables"""
    print("\nTesting environment variables...")
    
    base_url = os.getenv('LARAVEL_BASE_URL', 'http://localhost:8000')
    api_key = os.getenv('LARAVEL_API_KEY')
    
    print(f"LARAVEL_BASE_URL: {base_url}")
    print(f"LARAVEL_API_KEY: {'Set' if api_key else 'Not set'}")
    
    return True

def main():
    """Hlavná testovacia funkcia"""
    print("=== API Integration Test ===\n")
    
    # Test environment
    test_environment()
    
    # Test API endpoint
    api_success = test_api_endpoint()
    
    print("\n=== Test Results ===")
    print(f"API endpoint: {'PASS' if api_success else 'FAIL'}")
    
    if api_success:
        print("\n✓ API integration is working!")
        print("Note: Empty generators list is expected if no custom hash types exist in database.")
        return 0
    else:
        print("\n✗ API integration failed.")
        return 1

if __name__ == "__main__":
    sys.exit(main())
