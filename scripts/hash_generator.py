"""
 * @file hash_generator.py
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
"""

import sys
import subprocess

from scapy.all import *
from scapy.layers.tls.record import TLS
from scapy.layers.tls.extensions import TLS_Ext_SupportedGroups
from scapy.layers.tls.extensions import TLS_Ext_SupportedVersion_CH, TLS_Ext_SupportedVersion_SH
from scapy.layers.tls.extensions import TLS_Ext_ALPN
from scapy.layers.tls.extensions import TLS_Ext_SupportedPointFormat
from scapy.layers.tls.extensions import TLS_Ext_ServerName
from scapy.layers.tls.extensions import TLS_Ext_SignatureAlgorithms
from scapy.layers.inet import IP , TCP, UDP

from scapy.layers.tls.handshake import TLSClientHello
from scapy.layers.tls.handshake import TLSServerHello

from cryptography import x509
from cryptography.x509.oid import ExtensionOID, NameOID
from hashlib import sha256

from filter_manager import should_filter, get_sni_flag
from custom_hash_generators import custom_hash_manager
from dynamic_hash_loader import load_custom_hash_types_from_database

import hashlib
import os
import json

import warnings
warnings.filterwarnings('ignore')

BLACK_LIST_FILE_1 = "./black_lists/domain_black_list.txt"

black_list_files = [BLACK_LIST_FILE_1]

# source : https://www.rfc-editor.org/rfc/rfc8701.html
RESERVED_GREASE_VALUES = [
    2570, 6682, 10794, 14906, 19018, 23130, 27242, 31354,
    35466, 39578, 43690, 47802, 51914, 56026, 60138, 64250
]

script_dir = os.path.dirname(os.path.abspath(__file__))

hash_strings = []
hashes = []

def remove_reserved_grease_values(data):
    """
    The function ensures removing reserved grease values from data list

    Parameters:
    data (list): list of values to process.

    Returns:
    list: data list without grease values.
    """
    return [item for item in data if item not in RESERVED_GREASE_VALUES]

def process_JA3_ciphers(packet):
    """
    The function ensures extraction of ciphers from client hello packet

    Parameters:
    packet (Packet): packet to process.

    Returns:
    list: ciphers values list.
    """
    ciphers = []

    if packet.haslayer(TLS):
        tls_layers = packet[TLS]
        if tls_layers.haslayer(TLSClientHello):
            ciphers_field = tls_layers[TLSClientHello].ciphers
            if ciphers_field:
                for cipher in ciphers_field:
                    ciphers.append(cipher)

    ciphers = remove_reserved_grease_values(ciphers)
    return ciphers

def process_JA3S_ciphers(packet):
    """
    The function ensures extraction of ciphers from server hello packet

    Parameters:
    packet (Packet): packet to process.

    Returns:
    string: ciphers field value.
    """
    if packet.haslayer(TLS):
        tls_layers = packet[TLS]
        if tls_layers.haslayer(TLSServerHello):
            ciphers_field = tls_layers[TLSServerHello].cipher
            if ciphers_field not in RESERVED_GREASE_VALUES:
                return ciphers_field

    return None

def get_client_hello_version(packet):
    """
    The function ensures extraction of version from client hello packet

    Parameters:
    packet (Packet): packet to process.

    Returns:
    string: client hello version.
    """
    if packet.haslayer(TLS):
        tls_layers = packet[TLS]
        if tls_layers.haslayer(TLSClientHello):
            version = tls_layers[TLSClientHello].version
            return version

    return None

def get_server_hello_version(packet):
    """
    The function ensures extraction of version from server hello packet

    Parameters:
    packet (Packet): packet to process.

    Returns:
    string: server hello version.
    """
    if packet.haslayer(TLS):
        tls_layers = packet[TLS]
        if tls_layers.haslayer(TLSServerHello):
            version = tls_layers[TLSServerHello].version
            return version

    return None

def get_client_hello_extensions(packet):
    """
    The function ensures extraction of extensions from client hello packet

    Parameters:
    packet (Packet): packet to process.

    Returns:
    list: list of extensions.
    """
    extensions = []

    if packet.haslayer(TLS):
        tls_layers = packet[TLS]
        if tls_layers.haslayer(TLSClientHello):
            extensions_field = tls_layers[TLSClientHello].ext
            if extensions_field:
                for ext in extensions_field:
                    extensions.append(ext.type)

    extensions = remove_reserved_grease_values(extensions)
    return extensions

def get_server_hello_extensions(packet):
    """
    The function ensures extraction of extensions from server hello packet

    Parameters:
    packet (Packet): packet to process.

    Returns:
    list: list of extensions.
    """
    extensions = []

    if packet.haslayer(TLS):
        tls_layers = packet[TLS]
        if tls_layers.haslayer(TLSServerHello):
            extensions_field = tls_layers[TLSServerHello].ext
            if extensions_field:
                for ext in extensions_field:
                    extensions.append(ext.type)

    extensions = remove_reserved_grease_values(extensions)
    return extensions

def get_supported_groups(packet):
    """
    The function ensures extraction of supported groups from packet

    Parameters:
    packet (Packet): packet to process.

    Returns:
    list: list of supported groups.
    """
    supported_groups = []

    if packet.haslayer(TLS):
        tls_layers = packet[TLS]
        if tls_layers.haslayer(TLS_Ext_SupportedGroups):
            supported_groups_field = tls_layers[TLS_Ext_SupportedGroups].groups
            if supported_groups_field:
                for group in supported_groups_field:
                    supported_groups.append(group)

    supported_groups = remove_reserved_grease_values(supported_groups)
    return supported_groups

def get_supported_versions_CH(packet):
    """
    The function ensures extraction of supported versions from client hello packet

    Parameters:
    packet (Packet): packet to process.

    Returns:
    list: list of supported versions.
    """
    supported_versions = []

    if packet.haslayer(TLS):
        tls_layers = packet[TLS]
        if tls_layers.haslayer(TLS_Ext_SupportedVersion_CH):
            supported_versions_field = tls_layers[TLS_Ext_SupportedVersion_CH].versions
            if supported_versions_field:
                for version in supported_versions_field:
                    supported_versions.append(version)
        if tls_layers.version:
            supported_versions.append(tls_layers.version)

    supported_versions = remove_reserved_grease_values(supported_versions)
    return supported_versions

def get_supported_version_SH(packet):
    """
    The function ensures extraction of supported versions from server hello packet

    Parameters:
    packet (Packet): packet to process.

    Returns:
    list: list of supported versions.
    """
    if packet.haslayer(TLS):
        tls_layers = packet[TLS]
        if tls_layers.haslayer(TLS_Ext_SupportedVersion_SH):
            supported_versions_field = tls_layers[TLS_Ext_SupportedVersion_SH].version
            if supported_versions_field:
                return supported_versions_field
        if tls_layers.version:
            return tls_layers.version

    return None

def get_signature_algorithms(packet):
    """
    The function ensures extraction of signature alorithms values from packet

    Parameters:
    packet (Packet): packet to process.

    Returns:
    list: list of signature algorithms values.
    """
    signature_algorithms = []

    if packet.haslayer(TLS):
        tls_layers = packet[TLS]
        if tls_layers.haslayer(TLS_Ext_SignatureAlgorithms):
            signature_algorithms_field = tls_layers[TLS_Ext_SignatureAlgorithms].sig_algs
            if signature_algorithms_field:
                for sig_alg in signature_algorithms_field:
                    signature_algorithms.append(sig_alg)

    signature_algorithms = remove_reserved_grease_values(signature_algorithms)
    return signature_algorithms

def get_ec_point_formats(packet):
    """
    The function ensures ec point format extraction from packet

    Parameters:
    packet (Packet): packet to process.

    Returns:
    list: list of ec point formats values.
    """
    ec_point_formats = []

    if packet.haslayer(TLS):
        tls_layers = packet[TLS]
        if tls_layers.haslayer(TLS_Ext_SupportedPointFormat):
            ec_point_formats_field = tls_layers[TLS_Ext_SupportedPointFormat].ecpl
            if ec_point_formats_field:
                for format_code in ec_point_formats_field:
                    ec_point_formats.append(format_code)

    return ec_point_formats

def get_sni(packet):
    """
    The function ensures server name indicator extraction

    Parameters:
    packet (Packet): packet to process.

    Returns:
    string: server name indicator.
    """
    sni = None

    if packet.haslayer(TLS):
        tls_layers = packet[TLS]
        if tls_layers.haslayer(TLS_Ext_ServerName):
            sni_field = tls_layers[TLS_Ext_ServerName].servernames
            if sni_field:
                sni = sni_field[0].servername.decode()

    return sni

def remove_adds(res_array):
    """
    The function ensures removing hashes of advertisements servers

    Parameters:
    res_array (list): list of mobile apps hashes.

    Returns:
    list: list of mobile apps hashes without ad servers.
    """
    filtered = []

    for black_list_file in black_list_files:
        with open(os.path.join(script_dir, black_list_file), "r") as file:
            domain_names = file.read().splitlines()

            filtered = [res for res in res_array if not (res["sni"] in domain_names)]

    return filtered

def add_to_string(full_string, items):
    """
    The function ensures adding items to full string

    Parameters:
    full_string (string): full string intended for hash creation.
    items (list): list of items intended for add to full string.

    Returns:
    string: full string.
    """
    index = 0

    for item in items:
        full_string = full_string + str(item)
        if index != len(items) - 1:
            full_string = full_string + "-"
        index += 1

    return full_string

def create_JA4_hash(packet, sni):
    """
    The function ensures creation JA4 hashes

    Parameters:
    packet (Packet): TLS Client hello packet.
    sni (string): Server name indicator.

    Returns:
    string: JA4 hash.
    """
    ciphers = process_JA3_ciphers(packet)
    extensions = get_client_hello_extensions(packet)

    tls_version = "00"
    tls_versions = get_supported_versions_CH(packet)
    if(tls_versions):
        tls_version = process_version(max(tls_versions))

    sni = "d" if sni else "i"
    cip_cnt = format(len(ciphers), "02d")
    ext_cnt = format(len(extensions), "02d")
    ja4_a = "t"+tls_version+sni+cip_cnt+ext_cnt+get_alpn(packet)

    hex_ciphers = [format(cipher, "04X").lower() for cipher in ciphers]
    hex_ciphers.sort()
    cipher_in = ','.join(str(c) for c in hex_ciphers)

    hex_extensions = [format(extension, "04X").lower() for extension in extensions]
    hex_extensions.sort()

    if "0000" in hex_extensions:
        hex_extensions.remove("0000")
    if "0010" in hex_extensions:
        hex_extensions.remove("0010")

    ext_in = ','.join(str(e) for e in hex_extensions)

    signature_algorithms  = get_signature_algorithms(packet)
    hex_sig_algorithms = [format(sig_alg, "04X").lower() for sig_alg in signature_algorithms]
    signature_algorithms_str = ','.join(str(s) for s in hex_sig_algorithms)
    ext_in = ext_in+"_"+ signature_algorithms_str

    ja4_b = hashlib.sha256(cipher_in.encode()).hexdigest()[0:12]
    ja4_c = hashlib.sha256(ext_in.encode()).hexdigest()[0:12]

    return ja4_a+"_"+ja4_b+"_"+ja4_c

def create_JA4S_hash(packet):
    """
    The function ensures creation JA4S hashes

    Parameters:
    packet (Packet): TLS Server hello packet.

    Returns:
    string: JA4S hash.
    """
    extensions = get_server_hello_extensions(packet)
    ciphers = process_JA3S_ciphers(packet)

    tls_version = process_version(get_supported_version_SH(packet))
    ext_cnt = format(len(extensions), "02d")

    ja4s_a = "t"+tls_version+ext_cnt+get_alpn(packet)
    ja4s_b = format(ciphers, "04X").lower()

    hex_extensions = [format(extension, "04X").lower() for extension in extensions]

    if "0000" in hex_extensions:
        hex_extensions.remove("0000")
    if "0010" in hex_extensions:
        hex_extensions.remove("0010")

    ext_in = ','.join(str(e) for e in hex_extensions)

    ja4s_c = hashlib.sha256(ext_in.encode()).hexdigest()[0:12]

    return ja4s_a+"_"+ja4s_b+"_"+ja4s_c

def process_version(version):
    """
    The function ensures processing TLS handshake version for JA4 and JA4S fingerprints

    Parameters:
    version (int): TLS handshake version.

    Returns:
    string: version intended for JA4 and JA4S format.
    """
    if version == 772:
        return "13"
    elif version == 771:
        return "12"
    elif version == 770:
        return "11"
    elif version == 769:
        return "10"
    elif version == 768:
        return "s3"
    elif version == 767:
        return "s2"
    elif version == 766:
        return "s1"

    return "00"

def get_alpn(packet):
    """
    The function ensures getting alpn number value

    Parameters:
    packet (Packet): packet to process.

    Returns:
    string: alpn number value.
    """
    alpn = "00"
    if packet.haslayer(TLS):
        tls_layers = packet[TLS]
        if tls_layers.haslayer(TLS_Ext_ALPN):
            alpn_field = tls_layers[TLS_Ext_ALPN].protocols
            if alpn_field:
                alpn = alpn_field[0].protocol.decode()
                alpn = alpn[0]+alpn[-1]

    return alpn

def create_JA3_string(version, ciphers, extensions, supported_groups, point_format):
    """
    The function ensures processing TLS handshake version for JA4 and JA4S fingerprints

    Parameters:
    version (int): Client hello version value.
    ciphers (int): Client hello ciphers.
    extensions (int): Client hello extensions.
    supported_groups (array): Client hello supported groups.
    point_format (array): Client hello EC point format.

    Returns:
    string: JA3 full string.
    """
    full_string = "" + str(version) + ","
    full_string = add_to_string(full_string, ciphers) + ","
    full_string = add_to_string(full_string, extensions) + ","
    full_string = add_to_string(full_string, supported_groups) + ","
    full_string = add_to_string(full_string, point_format)

    return full_string

def create_JA3S_string(version, ciphers, extensions):
    """
    The function ensures JA3S full string creation

    Parameters:
    version (int): Server hello version value.
    ciphers (int): Server hello ciphers.
    extensions (int): Server hello extensions.

    Returns:
    string: JA3S full string.
    """
    full_string = "" + str(version) + ","
    full_string = full_string + str(ciphers) + ","
    full_string = add_to_string(full_string, extensions)

    return full_string

def create_JA3_hash(packet):
    """
    The function ensures JA3 hash generation

    Parameters:
    packet (Packet): Client hello packet.

    Returns:
    string: JA3 hash.
    """
    version = get_client_hello_version(packet)
    extensions = get_client_hello_extensions(packet)
    ciphers = process_JA3_ciphers(packet)
    supported_groups = get_supported_groups(packet)
    point_format = get_ec_point_formats(packet)

    full_string = create_JA3_string(version, ciphers, extensions, supported_groups, point_format)

    result = hashlib.md5(full_string.encode())

    return result.hexdigest()

def create_JA3S_hash(packet):
    """
    The function ensures JA3S hash generation

    Parameters:
    packet (Packet): Server hello packet.

    Returns:
    string: JA3S hash.
    """
    version = get_server_hello_version(packet)
    extensions = get_server_hello_extensions(packet)
    ciphers = process_JA3S_ciphers(packet)

    full_string = create_JA3S_string(version, ciphers, extensions)

    result = hashlib.md5(full_string.encode())

    return result.hexdigest()

def encode_variable_length_quantity(v):
    """
        The function ensures encoding variable length

        Parameters:
        v (int): oid variable.

        Returns:
        array: encoded variable length.

        source: https://github.com/FoxIO-LLC/ja4/blob/main/python/ja4x.py#L16
    """
    m = 0x00
    output = []
    while v >= 0x80:
        output.insert(0, (v & 0x7F) | m)
        v = v >> 7
        m = 0x80
    output.insert(0, v | m)
    return output

def oid_to_hex(oid):
    """
        The function ensures encoding oid to hex

        Parameters:
        oid (string): oid value.

        Returns:
        string: oid in hex format.

        source: https://github.com/FoxIO-LLC/ja4/blob/main/python/ja4x.py#L26
    """
    a = [int(x) for x in oid.split(".")]
    oid = [a[0] * 40 + a[1]]
    for n in a[2:]:
        oid.extend(encode_variable_length_quantity(n))
    oid.insert(0, len(oid))
    oid.insert(0, 0x06)
    return "".join("{:02x}".format(num) for num in oid)[4:]

def get_cert_extensions(cert):
    """
    The function ensures getting extension values from tls certificate

    Parameters:
    cert (Certificate): X509 certificate object.

    Returns:
    string: SHA256 hash of extensions items.
    """
    extensions = []
    for ext in cert.extensions:
        extensions.append(oid_to_hex(ext.oid.dotted_string))

    extensions_hash = sha256(','.join(extensions).encode('utf8')).hexdigest()[:12]
    return extensions_hash

def get_cert_issuer_rdns(cert):
    """
    The function ensures getting subject rdns values from tls certificate

    Parameters:
    cert (Certificate): X509 certificate object.

    Returns:
    string: SHA256 hash of issuer rdns items.
    """
    issuers = []

    for rdn in cert.issuer:
       issuers.append(oid_to_hex(rdn.oid.dotted_string))

    issuer_hash = sha256(','.join(issuers).encode('utf8')).hexdigest()[:12]
    return issuer_hash

def get_cert_subject_rdns(cert):
    """
    The function ensures getting subject rdns values from tls certificate

    Parameters:
    cert (Certificate): X509 certificate object.

    Returns:
    string: SHA256 hash of subject rdns items.
    """
    subjects = []

    for rdn in cert.subject:
       subjects.append(oid_to_hex(rdn.oid.dotted_string))

    subject_hash = sha256(','.join(subjects).encode('utf8')).hexdigest()[:12]
    return subject_hash

def create_ja4X_hash(cert):
    """
    The function ensures ja4x hashes creation

    Parameters:
    cert (Certificate): X509 certificate object.

    Returns:
    string: JA4X hash.
    """
    ja4x_a_hash = get_cert_issuer_rdns(cert)
    ja4x_b_hash = get_cert_subject_rdns(cert)
    ja4x_c_hash = get_cert_extensions(cert)

    ja4x = f"{ja4x_a_hash}_{ja4x_b_hash}_{ja4x_c_hash}"
    return ja4x

def get_CN_ON_values(attribute):
    """
    The function ensures getting additional data from x509 certificates

    Parameters:
    cert (Certificate): X509 certificate object.

    Returns:
    string: JA4X hash.
    """
    cn = None
    on = None
    c = None

    if len(attribute.get_attributes_for_oid(NameOID.COMMON_NAME)) != 0:
        cn = attribute.get_attributes_for_oid(NameOID.COMMON_NAME)[0].value

    if len(attribute.get_attributes_for_oid(NameOID.ORGANIZATION_NAME)) != 0:
        on = attribute.get_attributes_for_oid(NameOID.ORGANIZATION_NAME)[0].value

    if len(attribute.get_attributes_for_oid(NameOID.COUNTRY_NAME)) != 0:
        c = attribute.get_attributes_for_oid(NameOID.COUNTRY_NAME)[0].value

    return cn, on, c

def get_results_with_ja4x(pcap_file, results):
    """
    The function ensures tls certificate processing

    Parameters:
    pcap_file (file): captured pcap file.
    results (list): list of mobile apps hashes.

    Returns:
    list: results list.
    """
    command = [
        "tshark", "-2", "-R", "tls.handshake.certificates", "-T", "json",
        "-e", "ip.src", "-e", "ip.dst", "-e", "tcp.srcport", "-e", "tcp.dstport",
        "-e", "tls.handshake.certificate", "-r", pcap_file,
    ]

    result = subprocess.run(command, capture_output=True, text=True, check=False, encoding="utf-8")
    
    # Check if tshark failed completely
    if result.returncode != 0:
        # If tshark failed, return original results without JA4X
        return results
    
    json_string = re.sub(r'(in tap )?pkt\[\d+\]:.*\n', '', result.stdout)

    try:
        packets = json.loads(json_string)
    except json.JSONDecodeError:
        # If JSON parsing fails, return original results without JA4X
        return results

    for packet in packets:
        layers = packet["_source"]["layers"]

        try:
            certs = layers["tls.handshake.certificate"]

            for cert_str in certs:
                certificate_bytes = bytes.fromhex(cert_str.replace(":", "").replace(" ", ""))
                cert = x509.load_der_x509_certificate(certificate_bytes)

                # Get certificate issuer and subject data
                issuer_cn, issuer_on, issuer_c = get_CN_ON_values(cert.issuer)
                subject_cn, subject_on, subject_c = get_CN_ON_values(cert.subject)

                ja4x = create_ja4X_hash(cert)

                ip_src = layers["ip.src"][0]
                port_src = int(layers["tcp.srcport"][0])
                ip_dest = layers["ip.dst"][0]
                port_dest = int(layers["tcp.dstport"][0])

                key = (ip_dest, port_dest, ip_src, port_src)

                if key in results:
                    results[key]["ja4x_hash"].append({
                        "issuer": f"CN={issuer_cn}, ON={issuer_on}, C={issuer_c}",
                        "subject":  f"CN={subject_cn}, ON={subject_on}, C={subject_c}",
                        "ja4x": ja4x
                    })

        except Exception as e:
            print(f"Error extracting certificate: {e}", file=sys.stderr)

    return results


# Global cache for custom hash generators
# This cache stores loaded custom hash generators to prevent repeated API calls
# and eliminate rate limiting issues. Each cache entry is keyed by the combination
# of manual config flag and sorted generator names.
_custom_generators_cache = {}

def load_custom_generators_once(custom_generators, use_manual_config):
    """
    Load custom generators only once and cache them for performance optimization.
    
    This function implements a caching mechanism to prevent repeated API calls
    to the Laravel backend when processing multiple packets. The cache key is
    based on the manual config flag and the sorted list of generator names.
    
    Performance benefits:
    - Eliminates rate limiting (429 Too Many Requests errors)
    - Reduces API load on Laravel backend
    - Improves processing speed for large PCAP files
    - Memory efficient - generators loaded only once per session
    
    Args:
        custom_generators (list): List of generator names to load from database
        use_manual_config (bool): Whether to use manual JSON config instead of database
        
    Returns:
        dict: Dictionary mapping generator names to generator instances
        
    Example:
        >>> generators = load_custom_generators_once(['CUSTOM_TLS_01'], False)
        >>> print(generators['CUSTOM_TLS_01'].description)
        'Test 01'
    """
    # Create unique cache key based on config mode and generator names
    # Sorting ensures consistent cache keys regardless of input order
    if isinstance(custom_generators, dict):
        custom_generators = list(custom_generators.values())
    if not isinstance(custom_generators, (list, tuple)):
        custom_generators = [custom_generators]

    cache_key = f"{use_manual_config}_{','.join(sorted(str(name) for name in custom_generators))}"
    
    # Check if generators are already cached
    if cache_key not in _custom_generators_cache:
        # Debug: Loading custom generators (commented out to avoid stdout pollution)
        # print(f"Loading custom generators for: {custom_generators}")
        # Load generators from database or manual config
        _custom_generators_cache[cache_key] = load_custom_hash_types_from_database(custom_generators, use_manual_config)
    
    return _custom_generators_cache[cache_key]

def _has_relevant_data_for_generator(packet, generator):
    """
    Check if packet has relevant data for the specific generator type.
    
    This function validates whether a packet contains the necessary data
    for a specific generator type (TLS, network, or combination).
    
    Args:
        packet: Scapy packet object to validate
        generator: CustomHashGenerator instance to check against
        
    Returns:
        bool: True if packet has relevant data for this generator type
    """
    # Strict validation: only generate hashes for packets with relevant data
    if hasattr(generator, 'fields') and generator.fields:
        # For generators with specific fields, check if packet has relevant data
        if hasattr(generator, '__class__') and 'TLS' in generator.__class__.__name__:
            # TLS generators require TLS layer with valid data
            if not packet.haslayer(TLS):
                return False
            # Check if TLS layer has meaningful data (not just encrypted content)
            if packet.haslayer(TLSClientHello) or packet.haslayer(TLSServerHello):
                return True
            return False
        elif hasattr(generator, '__class__') and 'Algorithm' in generator.__class__.__name__:
            # Algorithm generators can work with any packet that has basic network data
            return packet.haslayer(IP) and (packet.haslayer(TCP) or packet.haslayer(UDP))
        else:
            # Default: require basic network data
            return packet.haslayer(IP)
    
    # For generators without specific fields, use basic validation
    return packet.haslayer(IP)

def generate_custom_hashes(packet, sni=None, custom_generators=None):
    """
    Generate custom hashes using cached generators for optimal performance.
    
    This function is the main entry point for custom hash generation. It uses
    the cached generator system to efficiently process packets and generate
    custom hash values. The function handles both TLS and non-TLS packets
    with appropriate fallback mechanisms.
    
    Key features:
    - Uses cached generators to avoid repeated API calls
    - Supports both TLS and non-TLS packet processing
    - Implements fallback system for database failures
    - Handles packet validation and error recovery
    - Returns results with 'custom_' prefix to avoid conflicts
    
    Args:
        packet (scapy.packet.Packet): Scapy packet object to process
        sni (str, optional): Server Name Indicator from TLS handshake
        custom_generators (list, optional): List of generator names to use
        
    Returns:
        dict: Dictionary with custom hash results, keys prefixed with 'custom_'
               Values are either hash strings or None for failed generations
        
    Example:
        >>> packet = IP()/TCP()
        >>> hashes = generate_custom_hashes(packet, None, ['CUSTOM_TLS_01'])
        >>> print(hashes)
        {'custom_CUSTOM_TLS_01': 'a1b2c3d4e5f6...'}
        
    Performance notes:
    - First call loads generators from database (slower)
    - Subsequent calls use cached generators (faster)
    - Memory usage scales with number of unique generator combinations
    """
    # Early return for empty generator list
    if not custom_generators:
        return {}

    if isinstance(custom_generators, dict):
        custom_generators = list(custom_generators.values())
    if not isinstance(custom_generators, (list, tuple)):
        custom_generators = [custom_generators]
    
    try:
        # Determine configuration mode from environment variable
        # Manual config mode uses JSON files instead of database
        use_manual_config = os.getenv('USE_MANUAL_CONFIG', 'false').lower() == 'true'
        
        # Load custom generators using caching mechanism
        # This will only make API calls on first use for each generator set
        db_generators = load_custom_generators_once(custom_generators, use_manual_config)
        
        # Fallback to existing system if no generators found
        # This handles cases where database is unavailable or generators don't exist
        if not db_generators:
            # Debug: No custom generators found (commented out to avoid stdout pollution)
            # print("No custom generators found, using fallback system")
            fallback_results = custom_hash_manager.generate_hashes(packet, sni, custom_generators)
            return {f'custom_{name}': value for name, value in fallback_results.items()}
        
        # Process each generator and generate hashes
        results = {}
        for name, generator in db_generators.items():
            # Validate packet compatibility with generator requirements
            if generator.validate_packet(packet):
                # Additional validation: check if packet has relevant data for this generator type
                if _has_relevant_data_for_generator(packet, generator):
                    try:
                        # Generate hash using the specific generator
                        hash_value = generator.generate_hash(packet, sni)
                        results[f'custom_{name}'] = hash_value
                    except Exception as e:
                        # Handle generator-specific errors gracefully
                        # Debug: Error generating hash (commented out to avoid stdout pollution)
                        # print(f"Error generating hash for {name}: {e}")
                        results[f'custom_{name}'] = None
                else:
                    # Packet doesn't have relevant data for this generator type
                    results[f'custom_{name}'] = None
            else:
                # Packet doesn't meet generator requirements
                results[f'custom_{name}'] = None
            
        return results
            
    except Exception as e:
        # Handle any unexpected errors in the generation process
        # Debug: Error generating custom hashes (commented out to avoid stdout pollution)
        # print(f"Error generating custom hashes: {e}")
        return {}

def remove_duplicities(data):
    """
    The function ensures removing duplicities from results

    Parameters:
    data (lit): list with results.

    Returns:
    list: updated results list.
    """
    unique_keys = set()
    results = []

    for item in data:
        # Include custom hashes in uniqueness check
        custom_hash_values = []
        for key, value in item.items():
            if key.startswith('custom_') and value is not None:
                custom_hash_values.append(value)
        
        key = (item["ja3_hash"], item["ja3s_hash"], item["ja4_hash"], item["ja4s_hash"], item["sni"]) + tuple(custom_hash_values)

        if key not in unique_keys:
            unique_keys.add(key)
            results.append(item)

    return results

if __name__ == '__main__':
    load_layer('tls')

    pcap_file = sys.argv[1]
    
    # Debug: Log start of processing
    print(f"Starting hash generation for file: {pcap_file}", file=sys.stderr)
    
    # Parse custom generators from command line arguments
    custom_generators = []
    if len(sys.argv) > 2:
        try:
            custom_generators = json.loads(sys.argv[2])
        except (json.JSONDecodeError, IndexError):
            custom_generators = []
    
    try:
        scapy_cap = rdpcap(pcap_file)
        print(f"Loaded {len(scapy_cap)} packets from PCAP file", file=sys.stderr)
    except Exception as e:
        print(f"Error loading PCAP file: {e}", file=sys.stderr)
        sys.exit(1)

    results = {}

    # Process all packets in pcap file
    packet_count = 0
    tls_packet_count = 0
    for packet in scapy_cap:
        packet_count += 1
        if packet_count % 1000 == 0:
            print(f"Processed {packet_count} packets, found {tls_packet_count} TLS packets", file=sys.stderr)
        # Process TLS layer
        if packet.haslayer(TLS) and packet.haslayer(TCP):
            tls_packet_count += 1
            # Get source/destination port and IP address
            ip_src = packet[IP].src
            ip_dest = packet[IP].dst
            port_src = packet[TCP].sport
            port_dest = packet[TCP].dport

            tls_layers = packet[TLS]

            # Process ClientHello packets
            if tls_layers.haslayer(TLSClientHello):

                sni = get_sni(packet)

                ja3_hash = create_JA3_hash(packet)
                ja4_hash = create_JA4_hash(packet, sni)
                
                # Generate custom hashes for TLS packets
                custom_hashes = generate_custom_hashes(packet, sni, custom_generators)

                key = (ip_src, port_src, ip_dest, port_dest)

                # Insert from client hello packets data to results
                if key not in results:
                    result_entry = {
                        "ip_src" : ip_src, "port_src":port_src,
                        "ip_dest":ip_dest, "port_dest":port_dest,
                        "ja3_hash": ja3_hash, "sni": sni, "ja3s_hash": None,
                        "ja4_hash": ja4_hash, "ja4s_hash": None, "ja4x_hash": []
                    }
                    # Initialize custom hashes to None for all generators
                    if custom_generators:
                        for gen_name in custom_generators:
                            result_entry[f"custom_{gen_name}"] = None
                    # Add custom hashes
                    for custom_name, custom_value in custom_hashes.items():
                        clean_name = custom_name.replace('custom_', '') if custom_name.startswith('custom_') else custom_name
                        result_entry[f"custom_{clean_name}"] = custom_value
                    results[key] = result_entry
                else:
                    results[key]["ja3_hash"] = ja3_hash
                    results[key]["sni"] = sni
                    results[key]["ja4_hash"] = ja4_hash
                    # Update custom hashes
                    for custom_name, custom_value in custom_hashes.items():
                        clean_name = custom_name.replace('custom_', '') if custom_name.startswith('custom_') else custom_name
                        results[key][f"custom_{clean_name}"] = custom_value

            # Process ServerHello packets
            if tls_layers.haslayer(TLSServerHello):

                ja3s_hash = create_JA3S_hash(packet)
                ja4s_hash = create_JA4S_hash(packet)
                
                key = (ip_dest, port_dest, ip_src, port_src)
                
                # Generate custom hashes for server hello
                # Use SNI from existing ClientHello entry if available, otherwise None
                sni = None
                if key in results and "sni" in results[key]:
                    sni = results[key]["sni"]
                custom_hashes = generate_custom_hashes(packet, sni, custom_generators)

                # Insert from server hello packets data to results
                if key not in results:
                    result_entry = {
                        "ip_src" : ip_src, "port_src":port_src,
                        "ip_dest":ip_dest, "port_dest":port_dest,
                        "ja3_hash": None, "sni": None, "ja3s_hash": ja3s_hash,
                        "ja4_hash": None, "ja4s_hash": ja4s_hash, "ja4x_hash": []
                    }
                    # Add custom hashes
                    for custom_name, custom_value in custom_hashes.items():
                        clean_name = custom_name.replace('custom_', '') if custom_name.startswith('custom_') else custom_name
                        result_entry[f"custom_{clean_name}"] = custom_value
                    results[key] = result_entry
                else:
                    results[key]["ja3s_hash"] = ja3s_hash
                    results[key]["ja4s_hash"] = ja4s_hash
                    # Update custom hashes only if they are not None (don't overwrite valid ClientHello hashes)
                    for custom_name, custom_value in custom_hashes.items():
                        clean_name = custom_name.replace('custom_', '') if custom_name.startswith('custom_') else custom_name
                        # Only update if the new value is not None (don't overwrite valid hashes with None)
                        if custom_value is not None:
                            results[key][f"custom_{clean_name}"] = custom_value
        
        # Custom hashes are only generated for TLS packets (ClientHello/ServerHello)
        # Non-TLS packets don't get custom hashes to avoid pollution

    # Add ja4x hashes to results
    results = get_results_with_ja4x(pcap_file, results)
    array_results = []

    # get final list of mobile app hash items
    for key in results:
        obj = {
            "ja3_hash": results[key]["ja3_hash"],
            "sni": results[key]["sni"],
            "ip_src": results[key]["ip_src"],
            "port_src": results[key]["port_src"],
            "ip_dest": results[key]["ip_dest"],
            "port_dest": results[key]["port_dest"],
            "ja3s_hash": results[key]["ja3s_hash"],
            "ja4_hash": results[key]["ja4_hash"],
            "ja4s_hash": results[key]["ja4s_hash"],
            "ja4x_hash": results[key]["ja4x_hash"]
        }
        
        # Add custom hashes to the result object
        for result_key, result_value in results[key].items():
            if result_key.startswith('custom_'):
                obj[result_key] = result_value
        
        array_results.append(obj)

    # Add SNI flags instead of filtering completely
    for res in array_results:
        sni = res.get("sni")
        if sni:
            # Get flag for this SNI
            flag = get_sni_flag(sni)
            if flag:
                res["sni_flag"] = flag
                res["is_flagged"] = True
            else:
                res["sni_flag"] = None
                res["is_flagged"] = False
        else:
            res["sni_flag"] = None
            res["is_flagged"] = False
    
    # Optional: Still filter if needed (can be controlled by environment variable)
    # array_results = [res for res in array_results if not should_filter(res.get("sni"))]

    # Filter out duplicate dictionaries
    filtered_results = remove_duplicities(array_results)

    # Debug: Log completion
    print(f"Hash generation completed. Generated {len(filtered_results)} unique hashes", file=sys.stderr)
    
    # Send output with error handling
    try:
        output_json = json.dumps(filtered_results)
        print(output_json)
    except Exception as e:
        print(f"Error serializing results to JSON: {e}", file=sys.stderr)
        print("[]")  # Return empty array on error
        sys.exit(1)
