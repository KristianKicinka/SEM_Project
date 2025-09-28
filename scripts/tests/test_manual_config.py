#!/usr/bin/env python3
"""
 * @file test_manual_config.py
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 * 
 * Test script for manual configuration mode
"""

import os
import sys
import json
# Add parent directory to path
current_dir = os.path.dirname(os.path.abspath(__file__))
scripts_dir = os.path.dirname(current_dir)
sys.path.append(scripts_dir)
from dynamic_hash_loader import load_custom_hash_types_from_database

def test_manual_config():
    """Test manual configuration mode"""
    print("Testing manual configuration mode...")
    
    try:
        # Test loading with manual config enabled
        generators = load_custom_hash_types_from_database(use_manual_config=True)
        
        print(f"Found {len(generators)} generators in manual config:")
        for name, generator in generators.items():
            print(f"  - {name}: {generator.description}")
            print(f"    Type: {type(generator).__name__}")
            print(f"    Required layers: {generator.get_required_layers()}")
        
        return True
        
    except Exception as e:
        print(f"Manual config test failed: {e}")
        return False

def test_specific_manual_generators():
    """Test loading specific generators from manual config"""
    print("\nTesting specific manual generators...")
    
    try:
        # Test loading specific generators
        test_names = ["CUSTOM_TLS_SIMPLE", "CUSTOM_IP_HASH", "CUSTOM_TLS_ADVANCED"]
        generators = load_custom_hash_types_from_database(test_names, use_manual_config=True)
        
        print(f"Requested {len(test_names)} generators, found {len(generators)}:")
        for name in test_names:
            if name in generators:
                print(f"  ✓ {name}: Found")
            else:
                print(f"  ✗ {name}: Not found")
        
        return True
        
    except Exception as e:
        print(f"Specific manual generators test failed: {e}")
        return False

def test_config_file_exists():
    """Test if config file exists and is valid JSON"""
    print("\nTesting config file...")
    
    config_file = os.path.join(os.path.dirname(__file__), 'custom_hash_config.json')
    
    if not os.path.exists(config_file):
        print(f"Config file not found: {config_file}")
        return False
    
    try:
        with open(config_file, 'r', encoding='utf-8') as f:
            config_data = json.load(f)
        
        generators = config_data.get('generators', [])
        print(f"Config file contains {len(generators)} generators")
        
        for gen in generators:
            print(f"  - {gen.get('name', 'Unknown')}: {gen.get('description', 'No description')}")
        
        return True
        
    except Exception as e:
        print(f"Config file test failed: {e}")
        return False

def main():
    """Main test function"""
    print("=== Manual Configuration Test ===\n")
    
    # Test config file
    config_success = test_config_file_exists()
    
    # Test manual config loading
    manual_success = test_manual_config()
    
    # Test specific generators
    specific_success = test_specific_manual_generators()
    
    print("\n=== Test Results ===")
    print(f"Config file: {'PASS' if config_success else 'FAIL'}")
    print(f"Manual config loading: {'PASS' if manual_success else 'FAIL'}")
    print(f"Specific generators: {'PASS' if specific_success else 'FAIL'}")
    
    if config_success and manual_success and specific_success:
        print("\n✓ All manual configuration tests passed!")
        return 0
    else:
        print("\n✗ Some tests failed.")
        return 1

if __name__ == "__main__":
    sys.exit(main())
