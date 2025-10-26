#!/usr/bin/env python3
"""
 * @file test_custom_hash_manual.py
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 * 
 * Manuálny test skript pre custom hash types
"""

import os
import sys
import json
import argparse
from scapy.all import *

# Add parent directory to path
current_dir = os.path.dirname(os.path.abspath(__file__))
scripts_dir = os.path.dirname(current_dir)
sys.path.append(scripts_dir)

from dynamic_hash_loader import load_custom_hash_types_from_database
from custom_hash_generators import custom_hash_manager

def create_test_packet():
    """Vytvorí testovací TLS packet"""
    # IP layer
    ip = IP(src="192.168.1.100", dst="192.168.1.1")
    
    # TCP layer
    tcp = TCP(sport=12345, dport=443, flags="S")
    
    # TLS layer (simplified)
    tls = TLS(version=0x0303, type=0x16)  # TLS 1.2, Handshake
    
    # Zloženie packetu
    packet = ip / tcp / tls
    return packet

def test_database_connection():
    """Test pripojenia k databáze"""
    print("=== Test pripojenia k databáze ===")
    
    try:
        # Test načítania custom hash types
        generators = load_custom_hash_types_from_database()
        
        print(f"Našiel {len(generators)} custom hash generátorov:")
        for name, generator in generators.items():
            print(f"  - {name}: {generator.description}")
            print(f"    Typ: {type(generator).__name__}")
            print(f"    Potrebné vrstvy: {generator.get_required_layers()}")
        
        return generators
        
    except Exception as e:
        print(f"Chyba pri pripojení k databáze: {e}")
        return {}

def test_specific_generators(generators, custom_names=None):
    """Test konkrétnych generátorov"""
    print("\n=== Test konkrétnych generátorov ===")
    
    if not generators:
        print("Žiadne generátory na testovanie")
        return
    
    # Vytvorenie testovacieho packetu
    packet = create_test_packet()
    sni = "example.com"
    
    print(f"Testovací packet: {packet.summary()}")
    print(f"SNI: {sni}\n")
    
    # Testovanie generátorov
    for name, generator in generators.items():
        if custom_names and name not in custom_names:
            continue
            
        print(f"--- Testovanie {name} ---")
        
        try:
            print(f"Popis: {generator.description}")
            print(f"Potrebné vrstvy: {generator.get_required_layers()}")
            
            # Validácia packetu
            is_valid = generator.validate_packet(packet)
            print(f"Packet je validný: {is_valid}")
            
            if is_valid:
                # Generovanie hash
                hash_result = generator.generate_hash(packet, sni)
                print(f"Výsledný hash: {hash_result}")
            else:
                print("Packet nie je validný pre tento generátor")
                
        except Exception as e:
            print(f"Chyba pri testovaní generátora: {e}")
        
        print()

def test_with_pcap_file(pcap_file, custom_names=None):
    """Test s reálnym PCAP súborom"""
    print(f"\n=== Test s PCAP súborom: {pcap_file} ===")
    
    if not os.path.exists(pcap_file):
        print(f"PCAP súbor neexistuje: {pcap_file}")
        return
    
    try:
        # Načítanie PCAP súboru
        packets = rdpcap(pcap_file)
        print(f"Načítaných {len(packets)} packetov")
        
        # Načítanie custom generátorov
        generators = load_custom_hash_types_from_database()
        
        if not generators:
            print("Žiadne custom generátory na testovanie")
            return
        
        # Testovanie na prvých 10 packetoch
        test_packets = packets[:10]
        results = {}
        
        for i, packet in enumerate(test_packets):
            print(f"\n--- Packet {i+1} ---")
            print(f"Packet: {packet.summary()}")
            
            # Testovanie custom generátorov
            for name, generator in generators.items():
                if custom_names and name not in custom_names:
                    continue
                    
                try:
                    if generator.validate_packet(packet):
                        hash_result = generator.generate_hash(packet)
                        print(f"  {name}: {hash_result}")
                        if name not in results:
                            results[name] = []
                        results[name].append(hash_result)
                    else:
                        print(f"  {name}: Packet nie je validný")
                except Exception as e:
                    print(f"  {name}: Chyba - {e}")
        
        # Zhrnutie výsledkov
        print(f"\n=== Zhrnutie výsledkov ===")
        for name, hashes in results.items():
            unique_hashes = set(hashes)
            print(f"{name}: {len(hashes)} hashov, {len(unique_hashes)} unikátnych")
            
    except Exception as e:
        print(f"Chyba pri spracovaní PCAP súboru: {e}")

def main():
    parser = argparse.ArgumentParser(description='Test custom hash types')
    parser.add_argument('--pcap', help='PCAP súbor na testovanie')
    parser.add_argument('--names', nargs='+', help='Konkrétne názvy generátorov na testovanie')
    parser.add_argument('--database-only', action='store_true', help='Test len pripojenia k databáze')
    
    args = parser.parse_args()
    
    print("Custom Hash Types - Manuálny test")
    print("=" * 50)
    
    # Test pripojenia k databáze
    generators = test_database_connection()
    
    if args.database_only:
        return
    
    # Test konkrétnych generátorov
    if generators:
        test_specific_generators(generators, args.names)
    
    # Test s PCAP súborom
    if args.pcap:
        test_with_pcap_file(args.pcap, args.names)
    else:
        print("\nPoužitie s PCAP súborom:")
        print("python test_custom_hash_manual.py --pcap /path/to/file.pcap")

if __name__ == '__main__':
    main()
