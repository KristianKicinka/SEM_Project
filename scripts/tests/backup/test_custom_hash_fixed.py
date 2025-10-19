#!/usr/bin/env python3
"""
 * @file test_custom_hash_fixed.py
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 * 
 * Opravený test skript pre custom hash types
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

def test_with_pcap_file_fixed(pcap_file, custom_names=None):
    """Test s reálnym PCAP súborom - opravená verzia"""
    print(f"=== Test s PCAP súborom: {pcap_file} ===")
    
    if not os.path.exists(pcap_file):
        print(f"PCAP súbor neexistuje: {pcap_file}")
        return
    
    try:
        # Načítanie PCAP súboru
        packets = rdpcap(pcap_file)
        print(f"Načítaných {len(packets)} packetov")
        
        # Načítanie custom generátorov
        print("Načítavam custom generátory...")
        generators = load_custom_hash_types_from_database()
        
        if not generators:
            print("Žiadne custom generátory na testovanie")
            return
        
        print(f"Našiel {len(generators)} custom generátorov:")
        for name, generator in generators.items():
            print(f"  - {name}: {generator.description}")
        
        # Testovanie na prvých 10 packetoch
        test_packets = packets[:10]
        results = {}
        
        print(f"\nTestovanie na {len(test_packets)} packetoch...")
        
        for i, packet in enumerate(test_packets):
            print(f"\n--- Packet {i+1} ---")
            print(f"Packet: {packet.summary()}")
            print(f"Vrstvy: {packet.layers()}")
            
            # Testovanie custom generátorov
            for name, generator in generators.items():
                if custom_names and name not in custom_names:
                    continue
                    
                try:
                    # Opravená validácia - ak packet nemá TLS vrstvu, skúsime ho spracovať aj tak
                    if generator.validate_packet(packet):
                        hash_result = generator.generate_hash(packet)
                        print(f"  {name}: {hash_result}")
                        if name not in results:
                            results[name] = []
                        results[name].append(hash_result)
                    else:
                        # Ak packet nie je validný pre TLS generátor, skúsime ho spracovať aj tak
                        print(f"  {name}: Packet nie je validný pre TLS generátor")
                        # Môžeme skúsiť spracovať packet aj bez TLS vrstvy
                        try:
                            # Vytvoríme fiktívny TLS packet pre testovanie
                            test_packet = create_test_packet()
                            if generator.validate_packet(test_packet):
                                hash_result = generator.generate_hash(test_packet)
                                print(f"  {name} (test): {hash_result}")
                                if name not in results:
                                    results[name] = []
                                results[name].append(hash_result)
                        except Exception as e:
                            print(f"  {name} (test): Chyba - {e}")
                except Exception as e:
                    print(f"  {name}: Chyba - {e}")
        
        # Zhrnutie výsledkov
        print(f"\n=== Zhrnutie výsledkov ===")
        for name, hashes in results.items():
            unique_hashes = set(hashes)
            print(f"{name}: {len(hashes)} hashov, {len(unique_hashes)} unikátnych")
            
    except Exception as e:
        print(f"Chyba pri spracovaní PCAP súboru: {e}")
        import traceback
        traceback.print_exc()

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
        import traceback
        traceback.print_exc()
        return {}

def main():
    parser = argparse.ArgumentParser(description='Opravený test custom hash types s PCAP súborom')
    parser.add_argument('--pcap', required=True, help='PCAP súbor na testovanie')
    parser.add_argument('--names', nargs='+', help='Konkrétne názvy generátorov na testovanie')
    
    args = parser.parse_args()
    
    print("Custom Hash Types - Opravený test s PCAP súborom")
    print("=" * 50)
    
    # Test pripojenia k databáze
    generators = test_database_connection()
    
    if not generators:
        print("Žiadne generátory na testovanie")
        return
    
    # Test s PCAP súborom
    test_with_pcap_file_fixed(args.pcap, args.names)

if __name__ == '__main__':
    main()
