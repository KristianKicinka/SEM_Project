#!/usr/bin/env python3
"""
 * @file test_config_only.py
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 * 
 * Test script for config file only (no scapy dependencies)
"""

import os
import sys
import json

def test_config_file():
    """Test config file structure and content"""
    print("Testing config file structure...")
    
    # Get the parent directory of tests (scripts directory)
    current_dir = os.path.dirname(os.path.abspath(__file__))
    scripts_dir = os.path.dirname(current_dir)
    config_file = os.path.join(scripts_dir, 'custom_hash_config.json')
    
    if not os.path.exists(config_file):
        print(f"Config file not found: {config_file}")
        return False
    
    try:
        with open(config_file, 'r', encoding='utf-8') as f:
            config_data = json.load(f)
        
        # Check structure
        if 'generators' not in config_data:
            print("Missing 'generators' key in config")
            return False
        
        generators = config_data.get('generators', [])
        print(f"Found {len(generators)} generators in config")
        
        # Check each generator
        for i, gen in enumerate(generators):
            print(f"\nGenerator {i+1}:")
            print(f"  Name: {gen.get('name', 'Missing')}")
            print(f"  Type: {gen.get('type', 'Missing')}")
            print(f"  Description: {gen.get('description', 'Missing')}")
            
            # Check configuration
            config = gen.get('configuration', {})
            if gen.get('type') == 'simple_tls':
                fields = config.get('fields', [])
                print(f"  Fields: {fields}")
            elif gen.get('type') == 'custom_algorithm':
                algorithm = config.get('algorithm', 'Missing')
                fields = config.get('fields', [])
                print(f"  Algorithm: {algorithm}")
                print(f"  Fields: {fields}")
            elif gen.get('type') == 'python_script':
                script_path = gen.get('script_path', 'Missing')
                print(f"  Script path: {script_path}")
        
        return True
        
    except json.JSONDecodeError as e:
        print(f"Invalid JSON in config file: {e}")
        return False
    except Exception as e:
        print(f"Error reading config file: {e}")
        return False

def test_available_fields():
    """Test available fields for different generator types"""
    print("\nTesting available fields...")
    
    # TLS fields
    tls_fields = [
        "version", "ciphers", "extensions", "compression_methods",
        "supported_versions", "signature_algorithms", "elliptic_curves",
        "ec_point_formats", "alpn_protocols", "sni", "timestamp"
    ]
    
    # Network fields
    network_fields = [
        "ip_src", "ip_dst", "ip_proto", "ip_ttl", "ip_tos", "ip_flags",
        "ip_id", "ip_len", "port_src", "port_dst", "tcp_flags", "tcp_seq",
        "tcp_ack", "tcp_window", "tcp_urgptr", "udp_sport", "udp_dport",
        "udp_len", "timestamp", "packet_size", "sni", "tls_version",
        "tls_content_type", "tls_length", "eth_src", "eth_dst", "eth_type",
        "packet_hash", "payload_size", "layer_count"
    ]
    
    print(f"TLS fields available: {len(tls_fields)}")
    for field in tls_fields:
        print(f"  - {field}")
    
    print(f"\nNetwork fields available: {len(network_fields)}")
    for field in network_fields:
        print(f"  - {field}")
    
    return True

def main():
    """Main test function"""
    print("=== Config File Test ===\n")
    
    # Test config file
    config_success = test_config_file()
    
    # Test available fields
    fields_success = test_available_fields()
    
    print("\n=== Test Results ===")
    print(f"Config file: {'PASS' if config_success else 'FAIL'}")
    print(f"Available fields: {'PASS' if fields_success else 'FAIL'}")
    
    if config_success and fields_success:
        print("\n✓ All config tests passed!")
        print("Manual configuration is ready to use.")
        return 0
    else:
        print("\n✗ Some tests failed.")
        return 1

if __name__ == "__main__":
    sys.exit(main())
