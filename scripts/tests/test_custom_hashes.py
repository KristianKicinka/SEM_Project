#!/usr/bin/env python3
"""
 * @file test_custom_hashes.py
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 * 
 * Testovací skript pre vlastné hash generátory
"""

import sys
import os
from scapy.all import *
from scapy.layers.tls.record import TLS
from scapy.layers.tls.handshake import TLSClientHello

# Pridanie cesty k modulom
current_dir = os.path.dirname(os.path.abspath(__file__))
scripts_dir = os.path.dirname(current_dir)
sys.path.append(scripts_dir)

from custom_hash_generators import custom_hash_manager

def create_test_packet():
    """Vytvorí testovací TLS packet"""
    # Vytvorenie základného IP/TCP packetu
    ip = IP(src="192.168.1.100", dst="192.168.1.1")
    tcp = TCP(sport=12345, dport=443)
    
    # Vytvorenie TLS ClientHello packetu
    tls_client_hello = TLSClientHello(
        version=0x0303,  # TLS 1.2
        ciphers=[0x1301, 0x1302, 0x1303],  # Príklad cipher suites
        ext=[{"type": 0, "data": b""}]  # Príklad extension
    )
    
    tls = TLS(msg=[tls_client_hello])
    
    # Zloženie packetu
    packet = ip / tcp / tls
    return packet

def test_custom_generators():
    """Testuje vlastné hash generátory"""
    print("=== Test vlastných hash generátorov ===\n")
    
    # Vytvorenie testovacieho packetu
    packet = create_test_packet()
    sni = "example.com"
    
    print(f"Testovací packet: {packet.summary()}")
    print(f"SNI: {sni}\n")
    
    # Získanie zoznamu dostupných generátorov
    available_generators = custom_hash_manager.list_generators()
    print(f"Dostupné generátory: {available_generators}\n")
    
    # Testovanie jednotlivých generátorov
    for generator_name in available_generators:
        print(f"--- Testovanie {generator_name} ---")
        
        try:
            generator = custom_hash_manager.get_generator(generator_name)
            if generator:
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
            else:
                print("Generátor sa nenašiel")
                
        except Exception as e:
            print(f"Chyba pri testovaní generátora: {e}")
        
        print()
    
    # Testovanie všetkých generátorov naraz
    print("--- Testovanie všetkých generátorov naraz ---")
    try:
        all_results = custom_hash_manager.generate_hashes(packet, sni)
        print("Výsledky všetkých generátorov:")
        for name, result in all_results.items():
            print(f"  {name}: {result}")
    except Exception as e:
        print(f"Chyba pri testovaní všetkých generátorov: {e}")

def test_configuration():
    """Testuje konfiguráciu"""
    print("\n=== Test konfigurácie ===\n")
    
    print(f"Konfiguračný súbor: {custom_hash_manager.config_file}")
    print(f"Konfigurácia:")
    
    import json
    print(json.dumps(custom_hash_manager.config, indent=2, ensure_ascii=False))

def main():
    """Hlavná funkcia"""
    print("Testovanie systému vlastných hash generátorov\n")
    
    try:
        test_configuration()
        test_custom_generators()
        print("\n=== Test dokončený ===")
        
    except Exception as e:
        print(f"Chyba pri testovaní: {e}")
        return 1
    
    return 0

if __name__ == "__main__":
    sys.exit(main())

