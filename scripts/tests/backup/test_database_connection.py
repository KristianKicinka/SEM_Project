#!/usr/bin/env python3
"""
 * @file test_database_connection.py
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 * 
 * Test skript pre overenie pripojenia k databáze
"""

import os
import sys
import json
import requests

# Add parent directory to path
current_dir = os.path.dirname(os.path.abspath(__file__))
scripts_dir = os.path.dirname(current_dir)
sys.path.append(scripts_dir)

def test_api_connection():
    """Test pripojenia k Laravel API"""
    print("=== Test pripojenia k Laravel API ===")
    
    base_url = os.getenv('LARAVEL_BASE_URL', 'http://localhost:8000')
    api_url = f"{base_url}/api/custom-hash-types/python-generator"
    
    print(f"Base URL: {base_url}")
    print(f"API URL: {api_url}")
    
    try:
        # Test GET request
        response = requests.get(api_url, timeout=10)
        print(f"GET Status: {response.status_code}")
        
        if response.status_code == 200:
            data = response.json()
            print(f"Odpoveď: {json.dumps(data, indent=2)}")
        else:
            print(f"Chyba: {response.text}")
            
    except requests.exceptions.RequestException as e:
        print(f"Chyba pri pripojení: {e}")
        return False
    
    try:
        # Test POST request with API key
        api_key = os.getenv('LARAVEL_API_KEY', 'python_hash_generator_key_test')
        headers = {'Authorization': f'Bearer {api_key}'}
        data = {'names': []}
        
        print(f"API Key: {api_key}")
        print(f"Headers: {headers}")
        
        response = requests.post(api_url, json=data, headers=headers, timeout=10)
        print(f"\nPOST Status: {response.status_code}")
        
        if response.status_code == 200:
            result = response.json()
            print(f"Odpoveď: {json.dumps(result, indent=2)}")
            return True
        else:
            print(f"Chyba: {response.text}")
            return False
            
    except requests.exceptions.RequestException as e:
        print(f"Chyba pri POST požiadavke: {e}")
        return False

def test_environment_variables():
    """Test environment premenných"""
    print("\n=== Test environment premenných ===")
    
    env_vars = [
        'LARAVEL_BASE_URL',
        'LARAVEL_API_KEY',
        'USE_MANUAL_CONFIG'
    ]
    
    for var in env_vars:
        value = os.getenv(var, 'NOT SET')
        print(f"{var}: {value}")

def test_manual_config():
    """Test manuálnej konfigurácie"""
    print("\n=== Test manuálnej konfigurácie ===")
    
    config_file = os.path.join(scripts_dir, 'custom_hash_config.json')
    print(f"Config file: {config_file}")
    print(f"Existuje: {os.path.exists(config_file)}")
    
    if os.path.exists(config_file):
        try:
            with open(config_file, 'r', encoding='utf-8') as f:
                config = json.load(f)
            print(f"Config obsah: {json.dumps(config, indent=2)}")
        except Exception as e:
            print(f"Chyba pri čítaní config súboru: {e}")

def main():
    print("Database Connection Test")
    print("=" * 50)
    
    # Test environment premenných
    test_environment_variables()
    
    # Test manuálnej konfigurácie
    test_manual_config()
    
    # Test API pripojenia
    success = test_api_connection()
    
    if success:
        print("\n✅ Test úspešný!")
    else:
        print("\n❌ Test zlyhal!")
        print("\nMožné riešenia:")
        print("1. Skontroluj, či Laravel server beží na localhost:8000")
        print("2. Skontroluj, či existuje endpoint /api/custom-hash-types/python-generator")
        print("3. Skontroluj, či sú správne nastavené environment premenné")

if __name__ == '__main__':
    main()
