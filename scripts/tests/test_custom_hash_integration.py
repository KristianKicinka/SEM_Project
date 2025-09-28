#!/usr/bin/env python3
"""
 * @file test_custom_hash_integration.py
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 * 
 * Test skript pre overenie integrácie custom hash types
"""

import os
import sys
import json
# Add parent directory to path
current_dir = os.path.dirname(os.path.abspath(__file__))
scripts_dir = os.path.dirname(current_dir)
sys.path.append(scripts_dir)
from dynamic_hash_loader import load_custom_hash_types_from_database

def test_database_connection():
    """Test pripojenia k databáze"""
    print("Testing database connection...")
    
    try:
        # Test načítania custom hash types
        generators = load_custom_hash_types_from_database()
        
        print(f"Found {len(generators)} custom hash generators:")
        for name, generator in generators.items():
            print(f"  - {name}: {generator.description}")
            print(f"    Type: {type(generator).__name__}")
            print(f"    Required layers: {generator.get_required_layers()}")
        
        return True
        
    except Exception as e:
        print(f"Database connection failed: {e}")
        return False

def test_specific_generators():
    """Test načítania konkrétnych generátorov"""
    print("\nTesting specific generators...")
    
    try:
        # Test načítania konkrétnych generátorov
        test_names = ["CUSTOM_TLS_SIMPLE", "CUSTOM_IP_HASH"]
        generators = load_custom_hash_types_from_database(test_names)
        
        print(f"Requested {len(test_names)} generators, found {len(generators)}:")
        for name in test_names:
            if name in generators:
                print(f"  ✓ {name}: Found")
            else:
                print(f"  ✗ {name}: Not found")
        
        return True
        
    except Exception as e:
        print(f"Specific generators test failed: {e}")
        return False

def test_environment_variables():
    """Test environment variables"""
    print("\nTesting environment variables...")
    
    base_url = os.getenv('LARAVEL_BASE_URL', 'http://localhost:8000')
    api_key = os.getenv('LARAVEL_API_KEY')
    
    print(f"LARAVEL_BASE_URL: {base_url}")
    print(f"LARAVEL_API_KEY: {'Set' if api_key else 'Not set'}")
    
    if not api_key:
        print("Warning: LARAVEL_API_KEY not set, using fallback mode")
    
    return True

def main():
    """Hlavná testovacia funkcia"""
    print("=== Custom Hash Types Integration Test ===\n")
    
    # Test environment variables
    test_environment_variables()
    
    # Test database connection
    db_success = test_database_connection()
    
    # Test specific generators
    specific_success = test_specific_generators()
    
    print("\n=== Test Results ===")
    print(f"Database connection: {'PASS' if db_success else 'FAIL'}")
    print(f"Specific generators: {'PASS' if specific_success else 'FAIL'}")
    
    if db_success and specific_success:
        print("\n✓ All tests passed! Custom hash types integration is working.")
        return 0
    else:
        print("\n✗ Some tests failed. Check the configuration.")
        return 1

if __name__ == "__main__":
    sys.exit(main())
