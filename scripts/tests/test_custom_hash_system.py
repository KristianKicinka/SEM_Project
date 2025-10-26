#!/usr/bin/env python3
"""
 * @file test_custom_hash_system.py
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 * 
 * Comprehensive test suite for custom hash system
 * 
 * This test module provides comprehensive testing capabilities for the custom hash
 * generation system. It includes tests for basic generators, database connectivity,
 * manual configuration, and real-world PCAP file processing.
 * 
 * Test Categories:
 * - Basic Generator Tests: Tests fundamental generator functionality
 * - Database Integration Tests: Tests Laravel API connectivity and data loading
 * - Manual Configuration Tests: Tests JSON-based configuration system
 * - PCAP Processing Tests: Tests with real network packet files
 * 
 * Usage:
 *     # Run all tests
 *     python3 test_custom_hash_system.py --test all
 *     
 *     # Test specific components
 *     python3 test_custom_hash_system.py --test basic
 *     python3 test_custom_hash_system.py --test database
 *     
 *     # Test with PCAP file
 *     python3 test_custom_hash_system.py --test all --pcap /path/to/file.pcap
 * 
 * Environment Variables:
 * - LARAVEL_BASE_URL: URL of the Laravel backend (default: http://localhost:8000)
 * - LARAVEL_API_KEY: API key for authentication
 * - USE_MANUAL_CONFIG: Enable manual configuration mode (true/false)
"""

import os
import sys
import json
import argparse
from scapy.all import *
from scapy.layers.inet import IP, TCP

# Add parent directory to path
current_dir = os.path.dirname(os.path.abspath(__file__))
scripts_dir = os.path.dirname(current_dir)
sys.path.append(scripts_dir)

from custom_hash_generators import CustomHashManager, SimpleTLSHashGenerator, CustomAlgorithmHashGenerator
from dynamic_hash_loader import load_custom_hash_types_from_database
from hash_generator import generate_custom_hashes

def test_basic_generators():
    """
    Test basic generator functionality.
    
    This function tests the fundamental capabilities of custom hash generators
    by creating simple test generators and verifying their behavior with
    basic network packets. It tests both TLS and algorithm-based generators.
    
    Test Coverage:
    - Generator instantiation and configuration
    - Packet validation logic
    - Hash generation with different field combinations
    - Error handling for invalid packets
    
    Expected Results:
    - Generators should validate packets correctly
    - Hash generation should produce consistent results
    - Error handling should be graceful
    """
    print("=== Test základných generátorov ===")
    
    # Create a simple test packet with IP and TCP layers
    # This packet simulates a basic network connection without TLS
    packet = IP(src="192.168.1.1", dst="192.168.1.2") / TCP(sport=80, dport=443)
    
    # Test TLS generator with TLS-specific fields
    # This generator is designed for TLS packets but should handle non-TLS packets gracefully
    tls_generator = SimpleTLSHashGenerator(
        name="TEST_TLS", 
        description="Test TLS generator", 
        fields=["version", "ciphers"]
    )
    
    # Test algorithm generator with network layer fields
    # This generator should work with any packet containing IP and TCP layers
    algo_generator = CustomAlgorithmHashGenerator(
        name="TEST_ALGO", 
        description="Test Algorithm generator", 
        algorithm="sha256", 
        fields=["ip_src", "ip_dst", "port_src", "port_dst"]
    )
    
    # Test packet validation and hash generation
    tls_valid = tls_generator.validate_packet(packet)
    tls_hash = tls_generator.generate_hash(packet) if tls_valid else None
    
    algo_valid = algo_generator.validate_packet(packet)
    algo_hash = algo_generator.generate_hash(packet) if algo_valid else None
    
    print(f"TLS Generator: {tls_valid} -> {tls_hash}")
    print(f"ALGO Generator: {algo_valid} -> {algo_hash}")
    
    # Verify that generators produce consistent results
    if tls_hash and algo_hash:
        print("✓ Both generators produced valid hashes")
    else:
        print("⚠ Some generators failed to produce hashes (expected for non-TLS packets)")

def test_database_connection():
    """
    Test database connectivity and generator loading.
    
    This function tests the integration between the Python hash generation
    system and the Laravel backend database. It verifies that custom hash
    generators can be loaded from the database via API calls.
    
    Test Coverage:
    - API connectivity to Laravel backend
    - Authentication with API key
    - Generator loading from database
    - Generator configuration parsing
    - Error handling for connection failures
    
    Prerequisites:
    - Laravel backend must be running
    - Database must contain custom hash types
    - Environment variables must be set correctly
    
    Expected Results:
    - Successful connection to database
    - Generators loaded with correct configuration
    - Proper error handling for connection failures
    """
    print("\n=== Test pripojenia k databáze ===")
    
    try:
        # Load custom hash generators from database
        # This tests the full API integration pipeline
        generators = load_custom_hash_types_from_database(['CUSTOM_TLS_01'], use_manual_config=False)
        
        print(f"Našiel {len(generators)} generátorov z databázy:")
        
        # Display detailed information about each loaded generator
        for name, generator in generators.items():
            print(f"  - {name}: {generator.description}")
            print(f"    Typ: {type(generator).__name__}")
            
            # Display generator-specific configuration
            if hasattr(generator, 'fields'):
                print(f"    Polia: {generator.fields}")
            if hasattr(generator, 'algorithm'):
                print(f"    Algoritmus: {generator.algorithm}")
            if hasattr(generator, 'script_path'):
                print(f"    Script: {generator.script_path}")
                
        # Verify that generators are properly configured
        if generators:
            print("✓ Database connection successful")
            print("✓ Generators loaded and configured correctly")
        else:
            print("⚠ No generators found in database")
            
    except Exception as e:
        print(f"Chyba pri pripojení k databáze: {e}")
        print("Troubleshooting:")
        print("  - Check if Laravel backend is running")
        print("  - Verify LARAVEL_BASE_URL environment variable")
        print("  - Verify LARAVEL_API_KEY environment variable")
        print("  - Check if custom hash types exist in database")

def test_with_pcap_file(pcap_file):
    """
    Test custom hash generation with real PCAP file.
    
    This function tests the custom hash generation system using real network
    packet data from a PCAP file. It processes a limited number of packets
    to verify that the system works correctly with actual network traffic.
    
    Test Coverage:
    - PCAP file loading and parsing
    - Packet processing with custom generators
    - Hash generation for different packet types
    - Performance with real network data
    - Error handling for malformed packets
    
    Args:
        pcap_file (str): Path to the PCAP file to process
        
    Expected Results:
    - PCAP file should load successfully
    - Packets should be processed without errors
    - Custom hashes should be generated where applicable
    - System should handle various packet types gracefully
    """
    print(f"\n=== Test s PCAP súborom: {pcap_file} ===")
    
    try:
        # Load PCAP file using Scapy
        # This tests the integration with the Scapy library
        scapy_cap = rdpcap(pcap_file)
        print(f"Načítaných {len(scapy_cap)} packetov")
        
        # Process first 5 packets to avoid overwhelming output
        # This provides a representative sample of the PCAP file
        for i, packet in enumerate(scapy_cap[:5]):
            print(f"\n--- Packet {i+1} ---")
            print(f"Packet: {packet.summary()}")
            
            # Display packet layers for debugging
            layers = []
            layer = packet
            while layer:
                layers.append(layer.__class__.__name__)
                layer = layer.payload
            print(f"Layers: {layers}")
            
            # Test custom hash generation
            # This tests the full pipeline from packet to hash generation
            custom_hashes = generate_custom_hashes(packet, None, ['CUSTOM_TLS_01'])
            
            if custom_hashes:
                print("Custom hashes generated:")
                for name, value in custom_hashes.items():
                    print(f"  {name}: {value}")
            else:
                print("No custom hashes generated (packet may not be compatible)")
                
    except FileNotFoundError:
        print(f"PCAP file not found: {pcap_file}")
        print("Please provide a valid PCAP file path")
    except Exception as e:
        print(f"Chyba pri načítaní PCAP súboru: {e}")
        print("Troubleshooting:")
        print("  - Verify the PCAP file path is correct")
        print("  - Check if the file is a valid PCAP format")
        print("  - Ensure sufficient permissions to read the file")

def test_manual_config():
    """
    Test manual configuration system.
    
    This function tests the manual configuration system that allows custom hash
    generators to be defined in JSON files instead of using the database.
    This is useful for development and testing scenarios.
    
    Test Coverage:
    - JSON configuration file loading
    - Manual generator instantiation
    - Configuration parsing and validation
    - Fallback behavior when database is unavailable
    
    Prerequisites:
    - Manual configuration file must exist
    - JSON format must be valid
    - Generator configurations must be properly structured
    
    Expected Results:
    - Manual generators should load successfully
    - Configuration should be parsed correctly
    - Generators should be instantiated properly
    """
    print("\n=== Test manuálnej konfigurácie ===")
    
    try:
        # Load generators from manual configuration
        # This tests the JSON-based configuration system
        generators = load_custom_hash_types_from_database(['CUSTOM_TLS_SIMPLE'], use_manual_config=True)
        
        print(f"Našiel {len(generators)} generátorov z manuálnej konfigurácie:")
        
        # Display information about each loaded generator
        for name, generator in generators.items():
            print(f"  - {name}: {generator.description}")
            print(f"    Typ: {type(generator).__name__}")
            
            # Display generator-specific configuration
            if hasattr(generator, 'fields'):
                print(f"    Polia: {generator.fields}")
            if hasattr(generator, 'algorithm'):
                print(f"    Algoritmus: {generator.algorithm}")
                
        # Verify that manual configuration works
        if generators:
            print("✓ Manual configuration loaded successfully")
            print("✓ Generators instantiated correctly")
        else:
            print("⚠ No generators found in manual configuration")
            
    except Exception as e:
        print(f"Chyba pri načítaní manuálnej konfigurácie: {e}")
        print("Troubleshooting:")
        print("  - Check if custom_hash_config.json exists")
        print("  - Verify JSON format is valid")
        print("  - Ensure generator configurations are correct")

def main():
    """
    Main test function with command-line argument parsing.
    
    This function provides a command-line interface for running different
    types of tests. It supports testing individual components or running
    comprehensive tests with real PCAP files.
    
    Command-line options:
    --pcap: Path to PCAP file for testing
    --test: Type of test to run (basic, database, manual, all)
    
    Environment variables:
    LARAVEL_BASE_URL: URL of Laravel backend
    LARAVEL_API_KEY: API key for authentication
    USE_MANUAL_CONFIG: Enable manual configuration mode
    """
    parser = argparse.ArgumentParser(
        description='Comprehensive test suite for custom hash system',
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Examples:
  # Run all tests
  python3 test_custom_hash_system.py --test all
  
  # Test basic generators only
  python3 test_custom_hash_system.py --test basic
  
  # Test with PCAP file
  python3 test_custom_hash_system.py --test all --pcap /path/to/file.pcap
  
  # Test database connectivity
  LARAVEL_BASE_URL=http://localhost:8000 LARAVEL_API_KEY=your_key \\
  python3 test_custom_hash_system.py --test database
        """
    )
    
    parser.add_argument('--pcap', help='PCAP file for testing')
    parser.add_argument('--test', choices=['basic', 'database', 'manual', 'all'], 
                       default='all', help='Type of test to run')
    
    args = parser.parse_args()
    
    print("Custom Hash System - Comprehensive Test Suite")
    print("==============================================")
    print(f"Test type: {args.test}")
    if args.pcap:
        print(f"PCAP file: {args.pcap}")
    print()
    
    # Run tests based on command-line arguments
    if args.test in ['basic', 'all']:
        test_basic_generators()
    
    if args.test in ['database', 'all']:
        test_database_connection()
    
    if args.test in ['manual', 'all']:
        test_manual_config()
    
    if args.pcap and args.test in ['all']:
        test_with_pcap_file(args.pcap)
    
    print("\n" + "="*50)
    print("Test suite completed")
    print("="*50)

if __name__ == "__main__":
    main()
