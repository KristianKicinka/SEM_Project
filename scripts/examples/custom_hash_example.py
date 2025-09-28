"""
 * @file custom_hash_example.py
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 * 
 * Príklad vlastného hash generátora
 * Tento súbor demonštruje, ako vytvoriť vlastný hash generátor
"""

import hashlib
from scapy.all import *
from scapy.layers.tls.record import TLS
from scapy.layers.tls.handshake import TLSClientHello, TLSServerHello

def generate_hash(packet, sni=None, **kwargs):
    """
    Príklad vlastného hash generátora
    
    Args:
        packet: Scapy packet objekt
        sni: Server Name Indicator
        **kwargs: Ďalšie parametre
        
    Returns:
        Vygenerovaný hash alebo None
    """
    try:
        # Kontrola, či packet obsahuje TLS vrstvu
        if not packet.haslayer(TLS):
            return None
        
        tls_layer = packet[TLS]
        values = []
        
        # Extrakcia TLS verzie
        if tls_layer.haslayer(TLSClientHello):
            version = tls_layer[TLSClientHello].version
            values.append(f"v{version}")
            
            # Extrakcia cipher suites
            ciphers = tls_layer[TLSClientHello].ciphers
            if ciphers:
                cipher_str = ",".join(map(str, ciphers[:5]))  # Prvých 5 cipher suites
                values.append(f"c{cipher_str}")
            
            # Extrakcia extensions
            extensions = tls_layer[TLSClientHello].ext
            if extensions:
                ext_types = [str(ext.type) for ext in extensions[:10]]  # Prvých 10 extensions
                values.append(f"e{','.join(ext_types)}")
        
        # Pridanie SNI ak je dostupné
        if sni:
            values.append(f"s{sni}")
        
        # Pridanie timestamp
        timestamp = int(packet.time)
        values.append(f"t{timestamp}")
        
        # Vytvorenie hash
        if values:
            hash_string = "|".join(values)
            return hashlib.sha256(hash_string.encode()).hexdigest()[:16]  # Prvých 16 znakov
        
        return None
        
    except Exception as e:
        print(f"Chyba v custom hash generátore: {e}")
        return None

def get_required_layers():
    """
    Vracia zoznam vrstiev, ktoré generátor potrebuje
    
    Returns:
        Zoznam názvov vrstiev
    """
    return ['TLS']

# Príklad použitia v konfigurácii:
# {
#   "name": "CUSTOM_EXAMPLE",
#   "type": "python_script",
#   "description": "Príklad vlastného hash generátora",
#   "script_path": "scripts/examples/custom_hash_example.py"
# }

