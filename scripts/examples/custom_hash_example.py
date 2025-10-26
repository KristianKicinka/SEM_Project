"""
 * @file custom_hash_example.py
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 * 
 * Example of custom hash generator
 * This file demonstrates how to create a custom hash generator
"""

import hashlib
from scapy.all import *
from scapy.layers.tls.record import TLS
from scapy.layers.tls.handshake import TLSClientHello, TLSServerHello

def generate_hash(packet, sni=None, **kwargs):
    """
    Example of custom hash generator
    
    Args:
        packet: Scapy packet object
        sni: Server Name Indicator
        **kwargs: Additional parameters
        
    Returns:
        Generated hash or None
    """
    try:
        # Check if packet contains TLS layer
        if not packet.haslayer(TLS):
            return None
        
        tls_layer = packet[TLS]
        values = []
        
        # Extract TLS version
        if tls_layer.haslayer(TLSClientHello):
            version = tls_layer[TLSClientHello].version
            values.append(f"v{version}")
            
            # Extract cipher suites
            ciphers = tls_layer[TLSClientHello].ciphers
            if ciphers:
                cipher_str = ",".join(map(str, ciphers[:5]))  # First 5 cipher suites
                values.append(f"c{cipher_str}")
            
            # Extract extensions
            extensions = tls_layer[TLSClientHello].ext
            if extensions:
                ext_types = [str(ext.type) for ext in extensions[:10]]  # First 10 extensions
                values.append(f"e{','.join(ext_types)}")
        
        # Add SNI if available
        if sni:
            values.append(f"s{sni}")
        
        # Add timestamp
        timestamp = int(packet.time)
        values.append(f"t{timestamp}")
        
        # Create hash
        if values:
            hash_string = "|".join(values)
            return hashlib.sha256(hash_string.encode()).hexdigest()[:16]  # First 16 characters
        
        return None
        
    except Exception as e:
        print(f"Error in custom hash generator: {e}")
        return None

def get_required_layers():
    """
    Returns list of layers that generator needs
    
    Returns:
        List of layer names
    """
    return ['TLS']

# Example usage in configuration:
# {
#   "name": "CUSTOM_EXAMPLE",
#   "type": "python_script",
#   "description": "Example of custom hash generator",
#   "script_path": "scripts/examples/custom_hash_example.py"
# }

