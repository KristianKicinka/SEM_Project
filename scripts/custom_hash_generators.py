"""
 * @file custom_hash_generators.py
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 * 
 * Modular system for custom hash generators
 * Allows users to create custom mobile application fingerprint types
 * 
 * This module provides a comprehensive framework for creating custom hash generators
 * that can extract specific fields from network packets and generate unique hashes.
 * The system supports multiple generator types including TLS-based, algorithm-based,
 * and Python script-based generators.
 * 
 * Key Components:
 * - CustomHashGenerator: Abstract base class for all generators
 * - SimpleTLSHashGenerator: TLS-specific field extraction and hashing
 * - CustomAlgorithmHashGenerator: Algorithm-based hashing with custom fields
 * - PythonScriptHashGenerator: External Python script execution
 * - CustomHashManager: Central management system for all generators
 * 
 * Features:
 * - Fallback support for non-TLS packets
 * - Comprehensive field extraction from multiple network layers
 * - Support for various hash algorithms (MD5, SHA1, SHA256, SHA512)
 * - Integration with Laravel backend via API
 * - Manual configuration support for development
 * 
 * Usage:
 *     # Create a TLS generator
 *     generator = SimpleTLSHashGenerator(
 *         name="CUSTOM_TLS_01",
 *         description="Custom TLS fingerprint",
 *         fields=["version", "ciphers", "extensions", "sni"]
 *     )
 *     
 *     # Generate hash from packet
 *     hash_value = generator.generate_hash(packet, sni)
"""

import json
import os
import sys
import hashlib
from abc import ABC, abstractmethod
from typing import Dict, List, Any, Optional
from scapy.all import *
from scapy.layers.tls.record import TLS
from scapy.layers.tls.handshake import TLSClientHello, TLSServerHello
from scapy.layers.tls.extensions import TLS_Ext_ServerName
from scapy.layers.inet import IP, TCP, UDP
from scapy.layers.l2 import Ether
from scapy.packet import Raw

class CustomHashGenerator(ABC):
    """
    Abstract base class for custom hash generators.
    
    This class defines the interface that all custom hash generators must implement.
    It provides common functionality for packet validation and hash generation,
    while allowing specific implementations to define their own field extraction
    and hashing logic.
    
    Attributes:
        name (str): Unique identifier for the generator
        description (str): Human-readable description of the generator's purpose
        
    Methods:
        generate_hash: Abstract method for hash generation (must be implemented)
        validate_packet: Validates packet compatibility with generator requirements
        get_required_layers: Abstract method for layer requirements (must be implemented)
    """
    
    def __init__(self, name: str, description: str = ""):
        """
        Initialize the custom hash generator.
        
        Args:
            name (str): Unique identifier for the generator
            description (str): Human-readable description of the generator's purpose
        """
        self.name = name
        self.description = description
    
    @abstractmethod
    def generate_hash(self, packet, sni: Optional[str] = None, **kwargs) -> Optional[str]:
        """
        Generate hash from packet data.
        
        This method must be implemented by all concrete generator classes.
        It should extract relevant fields from the packet and generate a hash
        based on the generator's specific logic.
        
        Args:
            packet: Scapy packet object to process
            sni (str, optional): Server Name Indicator from TLS handshake
            **kwargs: Additional keyword arguments for generator-specific options
            
        Returns:
            str or None: Generated hash string, or None if generation fails
        """
        pass
    
    @abstractmethod
    def get_required_layers(self) -> List[str]:
        """
        Get list of required network layers for this generator.
        
        This method must be implemented by all concrete generator classes.
        It should return a list of layer names that the packet must contain
        for the generator to work properly.
        
        Returns:
            List[str]: List of required layer names (e.g., ['IP', 'TCP', 'TLS'])
        """
        pass
    
    def validate_packet(self, packet) -> bool:
        """
        Validate if packet contains required layers
        
        Args:
            packet: Scapy packet object
            
        Returns:
            True if packet is valid, False otherwise
        """
        required_layers = self.get_required_layers()
        for layer in required_layers:
            if not packet.haslayer(layer):
                return False
        return True

class CustomHashManager:
    """
    Správca vlastných hash generátorov
    """
    
    def __init__(self, config_file: str = None):
        self.generators: Dict[str, CustomHashGenerator] = {}
        self.config_file = config_file or os.path.join(
            os.path.dirname(__file__), 'custom_hash_config.json'
        )
        self.load_config()
        self.load_generators()
    
    def load_config(self):
        """Načíta konfiguráciu vlastných hash generátorov"""
        if os.path.exists(self.config_file):
            try:
                with open(self.config_file, 'r', encoding='utf-8') as f:
                    self.config = json.load(f)
            except (json.JSONDecodeError, IOError) as e:
                print(f"Chyba pri načítavaní konfigurácie: {e}")
                self.config = {"generators": []}
        else:
            self.config = {"generators": []}
    
    def save_config(self):
        """Uloží konfiguráciu vlastných hash generátorov"""
        try:
            with open(self.config_file, 'w', encoding='utf-8') as f:
                json.dump(self.config, f, indent=2, ensure_ascii=False)
        except IOError as e:
            print(f"Chyba pri ukladaní konfigurácie: {e}")
    
    def load_generators(self):
        """Načíta registrované generátory"""
        for generator_config in self.config.get("generators", []):
            try:
                generator = self._create_generator_from_config(generator_config)
                if generator:
                    self.generators[generator.name] = generator
            except Exception as e:
                print(f"Chyba pri načítavaní generátora {generator_config.get('name', 'unknown')}: {e}")
    
    def _create_generator_from_config(self, config: Dict) -> Optional[CustomHashGenerator]:
        """Vytvorí generátor z konfigurácie"""
        generator_type = config.get("type")
        
        if generator_type == "simple_tls":
            configuration = config.get("configuration", {})
            if isinstance(configuration, str):
                try:
                    configuration = json.loads(configuration)
                except (json.JSONDecodeError, TypeError):
                    configuration = {}
            if not isinstance(configuration, dict):
                configuration = {}
            fields = configuration.get("fields", [])
            if isinstance(fields, str):
                try:
                    fields = json.loads(fields)
                except (json.JSONDecodeError, TypeError):
                    fields = []
            return SimpleTLSHashGenerator(
                name=config["name"],
                description=config.get("description", ""),
                fields=fields or []
            )
        elif generator_type == "custom_algorithm":
            configuration = config.get("configuration", {})
            if isinstance(configuration, str):
                try:
                    configuration = json.loads(configuration)
                except (json.JSONDecodeError, TypeError):
                    configuration = {}
            if not isinstance(configuration, dict):
                configuration = {}
            fields = configuration.get("fields", [])
            if isinstance(fields, str):
                try:
                    fields = json.loads(fields)
                except (json.JSONDecodeError, TypeError):
                    fields = []
            return CustomAlgorithmHashGenerator(
                name=config["name"],
                description=config.get("description", ""),
                algorithm=configuration.get("algorithm", "md5"),
                fields=fields or []
            )
        elif generator_type == "python_script":
            return PythonScriptHashGenerator(
                name=config["name"],
                description=config.get("description", ""),
                script_path=config.get("script_path")
            )
        
        return None
    
    def register_generator(self, generator: CustomHashGenerator):
        """Registruje nový generátor"""
        self.generators[generator.name] = generator
    
    def get_generator(self, name: str) -> Optional[CustomHashGenerator]:
        """Získa generátor podľa názvu"""
        return self.generators.get(name)
    
    def list_generators(self) -> List[str]:
        """Vráti zoznam názvov registrovaných generátorov"""
        return list(self.generators.keys())
    
    def generate_hashes(self, packet, sni: Optional[str] = None, 
                       generator_names: List[str] = None) -> Dict[str, Optional[str]]:
        """
        Generuje hashe pre daný packet pomocou zadaných generátorov
        
        Args:
            packet: Scapy packet objekt
            sni: Server Name Indicator
            generator_names: Zoznam názvov generátorov (ak None, použijú sa všetky)
            
        Returns:
            Slovník s názvami generátorov a ich hashami
        """
        results = {}
        
        if generator_names is None:
            generator_names = self.list_generators()
        
        for name in generator_names:
            generator = self.get_generator(name)
            if generator and generator.validate_packet(packet):
                try:
                    hash_value = generator.generate_hash(packet, sni)
                    results[name] = hash_value
                except Exception as e:
                    print(f"Chyba pri generovaní hash pre {name}: {e}")
                    results[name] = None
            else:
                results[name] = None
        
        return results

class SimpleTLSHashGenerator(CustomHashGenerator):
    """
    Jednoduchý TLS hash generátor založený na konfigurovateľných poliach
    """
    
    def __init__(self, name: str, description: str = "", fields: List[str] = None):
        super().__init__(name, description)
        self.fields = fields or []
    
    def generate_hash(self, packet, sni: Optional[str] = None, **kwargs) -> Optional[str]:
        """Generuje hash na základe konfigurovaných TLS polí"""
        if not self.validate_packet(packet):
            return None
        
        values = []
        
        for field in self.fields:
            value = self._extract_field_value(packet, field)
            if value is not None:
                values.append(str(value))
        
        if not values:
            return None
        
        # Create hash from values
        hash_string = "-".join(values)
        return hashlib.md5(hash_string.encode()).hexdigest()
    
    def _extract_field_value(self, packet, field: str) -> Optional[Any]:
        """Extract value from TLS packet based on field name"""
        try:
            if packet.haslayer(TLS):
                tls_layers = packet[TLS]
                
                # TLS Client Hello fields
                if field == "version" and tls_layers.haslayer(TLSClientHello):
                    return tls_layers[TLSClientHello].version
                elif field == "ciphers" and tls_layers.haslayer(TLSClientHello):
                    ciphers = tls_layers[TLSClientHello].ciphers
                    return ",".join(map(str, ciphers)) if ciphers else None
                elif field == "extensions" and tls_layers.haslayer(TLSClientHello):
                    extensions = tls_layers[TLSClientHello].ext
                    return ",".join(map(str, [ext.type for ext in extensions])) if extensions else None
                elif field == "compression_methods" and tls_layers.haslayer(TLSClientHello):
                    comp_methods = tls_layers[TLSClientHello].comp
                    return ",".join(map(str, comp_methods)) if comp_methods else None
                elif field == "supported_versions" and tls_layers.haslayer(TLSClientHello):
                    # Extract supported versions from extensions
                    if hasattr(tls_layers[TLSClientHello], 'ext'):
                        for ext in tls_layers[TLSClientHello].ext:
                            if hasattr(ext, 'versions'):
                                return ",".join(map(str, ext.versions))
                    return None
                elif field == "signature_algorithms" and tls_layers.haslayer(TLSClientHello):
                    # Extract signature algorithms from extensions
                    if hasattr(tls_layers[TLSClientHello], 'ext'):
                        for ext in tls_layers[TLSClientHello].ext:
                            if hasattr(ext, 'algs'):
                                return ",".join(map(str, ext.algs))
                    return None
                elif field == "elliptic_curves" and tls_layers.haslayer(TLSClientHello):
                    # Extract elliptic curves from extensions
                    if hasattr(tls_layers[TLSClientHello], 'ext'):
                        for ext in tls_layers[TLSClientHello].ext:
                            if hasattr(ext, 'groups'):
                                return ",".join(map(str, ext.groups))
                    return None
                elif field == "ec_point_formats" and tls_layers.haslayer(TLSClientHello):
                    # Extract EC point formats from extensions
                    if hasattr(tls_layers[TLSClientHello], 'ext'):
                        for ext in tls_layers[TLSClientHello].ext:
                            if hasattr(ext, 'point_formats'):
                                return ",".join(map(str, ext.point_formats))
                    return None
                elif field == "alpn_protocols" and tls_layers.haslayer(TLSClientHello):
                    # Extract ALPN protocols from extensions
                    if hasattr(tls_layers[TLSClientHello], 'ext'):
                        for ext in tls_layers[TLSClientHello].ext:
                            if hasattr(ext, 'protocols'):
                                return ",".join(ext.protocols)
                    return None
                elif field == "sni":
                    # Extract SNI from extensions - same as get_sni function
                    if tls_layers.haslayer(TLS_Ext_ServerName):
                        sni_field = tls_layers[TLS_Ext_ServerName].servernames
                        if sni_field:
                            return sni_field[0].servername.decode()
                    return None
                elif field == "timestamp":
                    return int(packet.time)
                
                return None
            else:
                # Fallback for non-TLS packets - try to extract basic network info
                if field == "ip_src" and packet.haslayer(IP):
                    return packet[IP].src
                elif field == "ip_dst" and packet.haslayer(IP):
                    return packet[IP].dst
                elif field == "port_src" and packet.haslayer(TCP):
                    return packet[TCP].sport
                elif field == "port_dst" and packet.haslayer(TCP):
                    return packet[TCP].dport
                elif field == "timestamp":
                    return int(packet.time)
                elif field == "packet_size":
                    return len(packet)
                elif field == "sni":
                    return None  # No SNI for non-TLS packets
                elif field == "version":
                    # Fallback for version - use IP version
                    return packet[IP].version if packet.haslayer(IP) else None
                elif field == "ciphers":
                    # Fallback for ciphers - use TCP ports
                    if packet.haslayer(TCP):
                        return f"{packet[TCP].sport}_{packet[TCP].dport}"
                    return None
                elif field == "extensions":
                    # Fallback for extensions - use IP options
                    return f"IP_{packet[IP].src}_{packet[IP].dst}" if packet.haslayer(IP) else None
                
                return None
                
        except Exception as e:
            print(f"Error extracting field {field}: {e}", file=sys.stderr)
        
        return None
    
    def get_required_layers(self) -> List[str]:
        return ['IP', 'TCP', 'TLS']  # TLS generators require TLS layer

class CustomAlgorithmHashGenerator(CustomHashGenerator):
    """
    Hash generátor s vlastným algoritmom
    """
    
    def __init__(self, name: str, description: str = "", algorithm: str = "md5", 
                 fields: List[str] = None):
        super().__init__(name, description)
        self.algorithm = algorithm.lower()
        self.fields = fields or []
    
    def generate_hash(self, packet, sni: Optional[str] = None, **kwargs) -> Optional[str]:
        """Generuje hash pomocou vlastného algoritmu"""
        if not self.validate_packet(packet):
            return None
        
        values = []
        
        for field in self.fields:
            value = self._extract_field_value(packet, field)
            if value is not None:
                values.append(str(value))
        
        if not values:
            return None
        
        # Create hash from values
        hash_string = "|".join(values)
        
        if self.algorithm == "md5":
            return hashlib.md5(hash_string.encode()).hexdigest()
        elif self.algorithm == "sha1":
            return hashlib.sha1(hash_string.encode()).hexdigest()
        elif self.algorithm == "sha256":
            return hashlib.sha256(hash_string.encode()).hexdigest()
        elif self.algorithm == "sha512":
            return hashlib.sha512(hash_string.encode()).hexdigest()
        else:
            # Fallback na MD5
            return hashlib.md5(hash_string.encode()).hexdigest()
    
    def _extract_field_value(self, packet, field: str) -> Optional[Any]:
        """Extract value from packet based on field name"""
        try:
            # TLS layer fields (same logic as SimpleTLSHashGenerator)
            if packet.haslayer(TLS) and field in ["version", "ciphers", "extensions", "compression_methods", "supported_versions", "signature_algorithms", "elliptic_curves", "ec_point_formats", "alpn_protocols", "sni"]:
                tls_layers = packet[TLS]
                
                # TLS Client Hello fields
                if field == "version" and tls_layers.haslayer(TLSClientHello):
                    return tls_layers[TLSClientHello].version
                elif field == "ciphers" and tls_layers.haslayer(TLSClientHello):
                    ciphers = tls_layers[TLSClientHello].ciphers
                    return ",".join(map(str, ciphers)) if ciphers else None
                elif field == "extensions" and tls_layers.haslayer(TLSClientHello):
                    extensions = tls_layers[TLSClientHello].ext
                    return ",".join(map(str, [ext.type for ext in extensions])) if extensions else None
                elif field == "compression_methods" and tls_layers.haslayer(TLSClientHello):
                    comp_methods = tls_layers[TLSClientHello].comp
                    return ",".join(map(str, comp_methods)) if comp_methods else None
                elif field == "supported_versions" and tls_layers.haslayer(TLSClientHello):
                    # Extract supported versions from extensions
                    if hasattr(tls_layers[TLSClientHello], 'ext'):
                        for ext in tls_layers[TLSClientHello].ext:
                            if hasattr(ext, 'versions'):
                                return ",".join(map(str, ext.versions))
                    return None
                elif field == "signature_algorithms" and tls_layers.haslayer(TLSClientHello):
                    # Extract signature algorithms from extensions
                    if hasattr(tls_layers[TLSClientHello], 'ext'):
                        for ext in tls_layers[TLSClientHello].ext:
                            if hasattr(ext, 'algs'):
                                return ",".join(map(str, ext.algs))
                    return None
                elif field == "elliptic_curves" and tls_layers.haslayer(TLSClientHello):
                    # Extract elliptic curves from extensions
                    if hasattr(tls_layers[TLSClientHello], 'ext'):
                        for ext in tls_layers[TLSClientHello].ext:
                            if hasattr(ext, 'groups'):
                                return ",".join(map(str, ext.groups))
                    return None
                elif field == "ec_point_formats" and tls_layers.haslayer(TLSClientHello):
                    # Extract EC point formats from extensions
                    if hasattr(tls_layers[TLSClientHello], 'ext'):
                        for ext in tls_layers[TLSClientHello].ext:
                            if hasattr(ext, 'point_formats'):
                                return ",".join(map(str, ext.point_formats))
                    return None
                elif field == "alpn_protocols" and tls_layers.haslayer(TLSClientHello):
                    # Extract ALPN protocols from extensions
                    if hasattr(tls_layers[TLSClientHello], 'ext'):
                        for ext in tls_layers[TLSClientHello].ext:
                            if hasattr(ext, 'protocols'):
                                return ",".join(ext.protocols)
                    return None
                elif field == "sni":
                    # Extract SNI from extensions - same as get_sni function
                    if tls_layers.haslayer(TLS_Ext_ServerName):
                        sni_field = tls_layers[TLS_Ext_ServerName].servernames
                        if sni_field:
                            return sni_field[0].servername.decode()
                    return None
            
            # Network layer fields (work for both TLS and non-TLS packets)
            if field == "ip_src" and packet.haslayer(IP):
                return packet[IP].src
            elif field == "ip_dst" and packet.haslayer(IP):
                return packet[IP].dst
            elif field == "ip_proto" and packet.haslayer(IP):
                return packet[IP].proto
            elif field == "ip_ttl" and packet.haslayer(IP):
                return packet[IP].ttl
            elif field == "ip_tos" and packet.haslayer(IP):
                return packet[IP].tos
            elif field == "ip_flags" and packet.haslayer(IP):
                return packet[IP].flags
            elif field == "ip_id" and packet.haslayer(IP):
                return packet[IP].id
            elif field == "ip_len" and packet.haslayer(IP):
                return packet[IP].len
            
            # Transport layer fields
            elif field == "port_src" and packet.haslayer(TCP):
                return packet[TCP].sport
            elif field == "port_dst" and packet.haslayer(TCP):
                return packet[TCP].dport
            elif field == "tcp_flags" and packet.haslayer(TCP):
                return packet[TCP].flags
            elif field == "tcp_seq" and packet.haslayer(TCP):
                return packet[TCP].seq
            elif field == "tcp_ack" and packet.haslayer(TCP):
                return packet[TCP].ack
            elif field == "tcp_window" and packet.haslayer(TCP):
                return packet[TCP].window
            elif field == "tcp_urgptr" and packet.haslayer(TCP):
                return packet[TCP].urgptr
            elif field == "udp_sport" and packet.haslayer(UDP):
                return packet[UDP].sport
            elif field == "udp_dport" and packet.haslayer(UDP):
                return packet[UDP].dport
            elif field == "udp_len" and packet.haslayer(UDP):
                return packet[UDP].len
            
            # Application layer fields
            elif field == "timestamp":
                return int(packet.time)
            elif field == "packet_size":
                return len(packet)
            elif field == "sni":
                return None  # No SNI for non-TLS packets
            elif field == "tls_version" and packet.haslayer(TLS):
                return packet[TLS].version
            elif field == "tls_content_type" and packet.haslayer(TLS):
                return packet[TLS].type
            elif field == "tls_length" and packet.haslayer(TLS):
                return packet[TLS].len
            
            # Ethernet layer fields
            elif field == "eth_src" and packet.haslayer(Ether):
                return packet[Ether].src
            elif field == "eth_dst" and packet.haslayer(Ether):
                return packet[Ether].dst
            elif field == "eth_type" and packet.haslayer(Ether):
                return packet[Ether].type
            
            # Custom fields
            elif field == "packet_hash":
                return hashlib.md5(str(packet).encode()).hexdigest()
            elif field == "payload_size":
                if packet.haslayer(Raw):
                    return len(packet[Raw])
                return 0
            elif field == "layer_count":
                return len(packet.layers())
            
        except Exception as e:
            print(f"Error extracting field {field}: {e}", file=sys.stderr)
        
        return None
    
    def get_required_layers(self) -> List[str]:
        return ['IP']  # Minimum IP layer

class PythonScriptHashGenerator(CustomHashGenerator):
    """
    Hash generátor založený na externom Python skripte
    """
    
    def __init__(self, name: str, description: str = "", script_path: str = None):
        super().__init__(name, description)
        self.script_path = script_path
    
    def generate_hash(self, packet, sni: Optional[str] = None, **kwargs) -> Optional[str]:
        """Spustí externý Python skript pre generovanie hash"""
        if not self.script_path or not os.path.exists(self.script_path):
            return None
        
        try:
            # Import and execute external script
            import importlib.util
            spec = importlib.util.spec_from_file_location("custom_script", self.script_path)
            module = importlib.util.module_from_spec(spec)
            spec.loader.exec_module(module)
            
            # Call generate_hash function if it exists
            if hasattr(module, 'generate_hash'):
                return module.generate_hash(packet, sni, **kwargs)
            
        except Exception as e:
            print(f"Chyba pri spúšťaní skriptu {self.script_path}: {e}")
        
        return None
    
    def get_required_layers(self) -> List[str]:
        return []  # External script determines its own requirements

# Global manager instance
custom_hash_manager = CustomHashManager()

