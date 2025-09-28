"""
 * @file custom_hash_generators.py
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 * 
 * Modular system for custom hash generators
 * Allows users to create custom mobile application fingerprint types
"""

import json
import os
import hashlib
from abc import ABC, abstractmethod
from typing import Dict, List, Any, Optional
from scapy.all import *
from scapy.layers.tls.record import TLS
from scapy.layers.tls.handshake import TLSClientHello, TLSServerHello

class CustomHashGenerator(ABC):
    """
    Abstract base class for custom hash generators
    """
    
    def __init__(self, name: str, description: str = ""):
        self.name = name
        self.description = description
    
    @abstractmethod
    def generate_hash(self, packet, sni: Optional[str] = None, **kwargs) -> Optional[str]:
        """
        Generate hash for given packet
        
        Args:
            packet: Scapy packet object
            sni: Server Name Indicator (optional)
            **kwargs: Additional parameters specific to generator
            
        Returns:
            Generated hash or None if hash cannot be generated
        """
        pass
    
    @abstractmethod
    def get_required_layers(self) -> List[str]:
        """
        Return list of layers that generator needs
        
        Returns:
            List of layer names (e.g. ['TLS', 'TCP'])
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
            return SimpleTLSHashGenerator(
                name=config["name"],
                description=config.get("description", ""),
                fields=config.get("fields", [])
            )
        elif generator_type == "custom_algorithm":
            return CustomAlgorithmHashGenerator(
                name=config["name"],
                description=config.get("description", ""),
                algorithm=config.get("algorithm", "md5"),
                fields=config.get("fields", [])
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
        
        # Vytvorí hash z hodnôt
        hash_string = "-".join(values)
        return hashlib.md5(hash_string.encode()).hexdigest()
    
    def _extract_field_value(self, packet, field: str) -> Optional[Any]:
        """Extract value from TLS packet based on field name"""
        try:
            if packet.haslayer(TLS):
                tls_layer = packet[TLS]
                
                # TLS Client Hello fields
                if field == "version" and tls_layer.haslayer(TLSClientHello):
                    return tls_layer[TLSClientHello].version
                elif field == "ciphers" and tls_layer.haslayer(TLSClientHello):
                    ciphers = tls_layer[TLSClientHello].ciphers
                    return ",".join(map(str, ciphers)) if ciphers else None
                elif field == "extensions" and tls_layer.haslayer(TLSClientHello):
                    extensions = tls_layer[TLSClientHello].ext
                    return ",".join(map(str, [ext.type for ext in extensions])) if extensions else None
                elif field == "compression_methods" and tls_layer.haslayer(TLSClientHello):
                    comp_methods = tls_layer[TLSClientHello].comp
                    return ",".join(map(str, comp_methods)) if comp_methods else None
                elif field == "supported_versions" and tls_layer.haslayer(TLSClientHello):
                    # Extract supported versions from extensions
                    if hasattr(tls_layer[TLSClientHello], 'ext'):
                        for ext in tls_layer[TLSClientHello].ext:
                            if hasattr(ext, 'versions'):
                                return ",".join(map(str, ext.versions))
                    return None
                elif field == "signature_algorithms" and tls_layer.haslayer(TLSClientHello):
                    # Extract signature algorithms from extensions
                    if hasattr(tls_layer[TLSClientHello], 'ext'):
                        for ext in tls_layer[TLSClientHello].ext:
                            if hasattr(ext, 'algs'):
                                return ",".join(map(str, ext.algs))
                    return None
                elif field == "elliptic_curves" and tls_layer.haslayer(TLSClientHello):
                    # Extract elliptic curves from extensions
                    if hasattr(tls_layer[TLSClientHello], 'ext'):
                        for ext in tls_layer[TLSClientHello].ext:
                            if hasattr(ext, 'groups'):
                                return ",".join(map(str, ext.groups))
                    return None
                elif field == "ec_point_formats" and tls_layer.haslayer(TLSClientHello):
                    # Extract EC point formats from extensions
                    if hasattr(tls_layer[TLSClientHello], 'ext'):
                        for ext in tls_layer[TLSClientHello].ext:
                            if hasattr(ext, 'point_formats'):
                                return ",".join(map(str, ext.point_formats))
                    return None
                elif field == "alpn_protocols" and tls_layer.haslayer(TLSClientHello):
                    # Extract ALPN protocols from extensions
                    if hasattr(tls_layer[TLSClientHello], 'ext'):
                        for ext in tls_layer[TLSClientHello].ext:
                            if hasattr(ext, 'protocols'):
                                return ",".join(ext.protocols)
                    return None
                elif field == "sni" and sni:
                    return sni
                elif field == "timestamp":
                    return int(packet.time)
                
        except Exception as e:
            print(f"Error extracting field {field}: {e}")
        
        return None
    
    def get_required_layers(self) -> List[str]:
        return ['TLS']

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
        
        # Vytvorí hash z hodnôt
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
            # Network layer fields
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
            elif field == "sni" and sni:
                return sni
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
            print(f"Error extracting field {field}: {e}")
        
        return None
    
    def get_required_layers(self) -> List[str]:
        return ['IP']  # Minimálne IP vrstva

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
            # Importuje a spustí externý skript
            import importlib.util
            spec = importlib.util.spec_from_file_location("custom_script", self.script_path)
            module = importlib.util.module_from_spec(spec)
            spec.loader.exec_module(module)
            
            # Volá funkciu generate_hash ak existuje
            if hasattr(module, 'generate_hash'):
                return module.generate_hash(packet, sni, **kwargs)
            
        except Exception as e:
            print(f"Chyba pri spúšťaní skriptu {self.script_path}: {e}")
        
        return None
    
    def get_required_layers(self) -> List[str]:
        return []  # Externý skript si určí sám

# Globálna inštancia správcu
custom_hash_manager = CustomHashManager()

