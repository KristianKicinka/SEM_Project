#!/usr/bin/env python3
"""
 * @file test_fixed_generators.py
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 * 
 * Test opravených custom hash generátorov
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

from custom_hash_generators_fixed import SimpleTLSHashGenerator, CustomAlgorithmHashGenerator, CustomHashManager

def create_test_packet():
    """Vytvorí testovací packet"""
    # IP layer
    ip = IP(src="192.168.1.100", dst="192.168.1.1")
    
    # TCP layer
    tcp = TCP(sport=12345, dport=443, flags="S")
    
    # Zloženie packetu (bez TLS vrstvy)
    packet = ip / tcp
    return packet

def test_fixed_generators():
    """Test opravených generátorov"""
    print("=== Test opravených generátorov ===")
    
    # Vytvorenie testovacieho packetu
    packet = create_test_packet()
    sni = "example.com"
    
    print(f"Testovací packet: {packet.summary()}")
    print(f"SNI: {sni}\n")
    
    # Vytvorenie SimpleTLSHashGenerator
    tls_generator = SimpleTLSHashGenerator(
        name="TEST_TLS",
        description="Test TLS generator",
        fields=["version", "ciphers", "extensions", "sni"]
    )
    
    print(f"--- Testovanie {tls_generator.name} ---")
    print(f"Popis: {tls_generator.description}")
    print(f"Potrebné vrstvy: {tls_generator.get_required_layers()}")
    
    # Validácia packetu
    is_valid = tls_generator.validate_packet(packet)
    print(f"Packet je validný: {is_valid}")
    
    if is_valid:
        # Generovanie hash
        hash_result = tls_generator.generate_hash(packet, sni)
        print(f"Výsledný hash: {hash_result}")
    else:
        print("Packet nie je validný pre tento generátor")
    
    print()
    
    # Vytvorenie CustomAlgorithmHashGenerator
    algo_generator = CustomAlgorithmHashGenerator(
        name="TEST_ALGO",
        description="Test Algorithm generator",
        algorithm="sha256",
        fields=["ip_src", "ip_dst", "port_src", "port_dst"]
    )
    
    print(f"--- Testovanie {algo_generator.name} ---")
    print(f"Popis: {algo_generator.description}")
    print(f"Potrebné vrstvy: {algo_generator.get_required_layers()}")
    
    # Validácia packetu
    is_valid = algo_generator.validate_packet(packet)
    print(f"Packet je validný: {is_valid}")
    
    if is_valid:
        # Generovanie hash
        hash_result = algo_generator.generate_hash(packet, sni)
        print(f"Výsledný hash: {hash_result}")
    else:
        print("Packet nie je validný pre tento generátor")
    
    print()
    
    # Testovanie s reálnym packetom bez TLS vrstvy
    print("--- Testovanie s reálnym packetom bez TLS vrstvy ---")
    real_packet = IP(src="192.168.1.100", dst="192.168.1.1") / TCP(sport=12345, dport=443)
    print(f"Reálny packet: {real_packet.summary()}")
    print(f"Vrstvy: {real_packet.layers()}")
    
    # Test TLS generátora s reálnym packetom
    is_valid = tls_generator.validate_packet(real_packet)
    print(f"TLS generátor - Packet je validný: {is_valid}")
    
    if is_valid:
        hash_result = tls_generator.generate_hash(real_packet, sni)
        print(f"TLS generátor - Výsledný hash: {hash_result}")
    
    # Test Algorithm generátora s reálnym packetom
    is_valid = algo_generator.validate_packet(real_packet)
    print(f"Algorithm generátor - Packet je validný: {is_valid}")
    
    if is_valid:
        hash_result = algo_generator.generate_hash(real_packet, sni)
        print(f"Algorithm generátor - Výsledný hash: {hash_result}")

def test_with_pcap_file(pcap_file):
    """Test s reálnym PCAP súborom"""
    print(f"\n=== Test s PCAP súborom: {pcap_file} ===")
    
    if not os.path.exists(pcap_file):
        print(f"PCAP súbor neexistuje: {pcap_file}")
        return
    
    try:
        # Načítanie PCAP súboru
        packets = rdpcap(pcap_file)
        print(f"Načítaných {len(packets)} packetov")
        
        # Vytvorenie generátorov
        tls_generator = SimpleTLSHashGenerator(
            name="PCAP_TLS",
            description="PCAP TLS generator",
            fields=["version", "ciphers", "extensions", "sni"]
        )
        
        algo_generator = CustomAlgorithmHashGenerator(
            name="PCAP_ALGO",
            description="PCAP Algorithm generator",
            algorithm="sha256",
            fields=["ip_src", "ip_dst", "port_src", "port_dst"]
        )
        
        # Testovanie na prvých 10 packetoch
        test_packets = packets[:10]
        results = {}
        
        print(f"\nTestovanie na {len(test_packets)} packetoch...")
        
        for i, packet in enumerate(test_packets):
            print(f"\n--- Packet {i+1} ---")
            print(f"Packet: {packet.summary()}")
            print(f"Vrstvy: {packet.layers()}")
            
            # Testovanie TLS generátora
            try:
                if tls_generator.validate_packet(packet):
                    hash_result = tls_generator.generate_hash(packet, "test.com")
                    print(f"  TLS: {hash_result}")
                    if "TLS" not in results:
                        results["TLS"] = []
                    results["TLS"].append(hash_result)
                else:
                    print(f"  TLS: Packet nie je validný")
            except Exception as e:
                print(f"  TLS: Chyba - {e}")
            
            # Testovanie Algorithm generátora
            try:
                if algo_generator.validate_packet(packet):
                    hash_result = algo_generator.generate_hash(packet, "test.com")
                    print(f"  ALGO: {hash_result}")
                    if "ALGO" not in results:
                        results["ALGO"] = []
                    results["ALGO"].append(hash_result)
                else:
                    print(f"  ALGO: Packet nie je validný")
            except Exception as e:
                print(f"  ALGO: Chyba - {e}")
        
        # Zhrnutie výsledkov
        print(f"\n=== Zhrnutie výsledkov ===")
        for name, hashes in results.items():
            unique_hashes = set(hashes)
            print(f"{name}: {len(hashes)} hashov, {len(unique_hashes)} unikátnych")
            
    except Exception as e:
        print(f"Chyba pri spracovaní PCAP súboru: {e}")
        import traceback
        traceback.print_exc()

def main():
    parser = argparse.ArgumentParser(description='Test opravených custom hash generátorov')
    parser.add_argument('--pcap', help='PCAP súbor na testovanie')
    
    args = parser.parse_args()
    
    print("Opravené Custom Hash Generátory - Test")
    print("=" * 50)
    
    # Test opravených generátorov
    test_fixed_generators()
    
    # Test s PCAP súborom
    if args.pcap:
        test_with_pcap_file(args.pcap)
    else:
        print("\nPoužitie s PCAP súborom:")
        print("python test_fixed_generators.py --pcap /path/to/file.pcap")

if __name__ == '__main__':
    main()
