import sys

import pyshark as pyshark
from scapy.all import *
from scapy.layers.tls.record import TLS
from scapy.layers.tls.extensions import TLS_Ext_SupportedGroups
from scapy.layers.tls.extensions import TLS_Ext_SupportedVersion_CH, TLS_Ext_SupportedVersion_SH
from scapy.layers.tls.extensions import TLS_Ext_ALPN
from scapy.layers.tls.extensions import TLS_Ext_SupportedPointFormat
from scapy.layers.tls.extensions import TLS_Ext_ServerName
from scapy.layers.tls.extensions import TLS_Ext_SignatureAlgorithms
from scapy.layers.inet import IP , TCP

from scapy.layers.tls.handshake import TLSClientHello
from scapy.layers.tls.handshake import TLSServerHello

import hashlib
import os
import json

import warnings
warnings.filterwarnings('ignore')

BLACK_LIST_FILE_1 = "./black_lists/domain_black_list.txt"
BLACK_LIST_FILE_2 = "./black_lists/ad-list.txt"

black_list_files = [BLACK_LIST_FILE_1]

# source : https://www.rfc-editor.org/rfc/rfc8701.html
RESERVED_GREASE_VALUES = [
    2570, 6682, 10794, 14906, 19018, 23130, 27242, 31354,
    35466, 39578, 43690, 47802, 51914, 56026, 60138, 64250
]

script_dir = os.path.dirname(os.path.abspath(__file__))

hash_strings = []
hashes = []

def remove_reserved_grease_values(array):
    return [item for item in array if item not in RESERVED_GREASE_VALUES]

def process_JA3_ciphers(packet):
    ciphers = []
    if packet.haslayer(TLS):
        tls_layers = packet[TLS]
        if tls_layers.haslayer(TLSClientHello):  # Check if TLS layer contains Client Hello
            ciphers_field = tls_layers[TLSClientHello].ciphers
            if ciphers_field:
                for cipher in ciphers_field:
                    ciphers.append(cipher)

    ciphers = remove_reserved_grease_values(ciphers)
    return ciphers

def process_JA3S_ciphers(packet):
    if packet.haslayer(TLS):
        tls_layers = packet[TLS]
        if tls_layers.haslayer(TLSServerHello):  # Check if TLS layer contains Client Hello
            ciphers_field = tls_layers[TLSServerHello].cipher
            if ciphers_field not in RESERVED_GREASE_VALUES:
                return ciphers_field
                    
    return None

def get_client_hello_version(packet):
    if packet.haslayer(TLS):
        tls_layers = packet[TLS]
        if tls_layers.haslayer(TLSClientHello):  # Client Hello
            version = tls_layers[TLSClientHello].version
            return version

    return None

def get_server_hello_version(packet):
    if packet.haslayer(TLS):
        tls_layers = packet[TLS]
        if tls_layers.haslayer(TLSServerHello):  # Client Hello
            version = tls_layers[TLSServerHello].version
            return version

    return None


def get_client_hello_extensions(packet):
    extensions = []
    if packet.haslayer(TLS):
        tls_layers = packet[TLS]
        if tls_layers.haslayer(TLSClientHello):  # Check if TLS layer contains Client Hello
            extensions_field = tls_layers[TLSClientHello].ext
            if extensions_field:
                for ext in extensions_field:
                    extensions.append(ext.type)

    extensions = remove_reserved_grease_values(extensions)
    return extensions

def get_server_hello_extensions(packet):
    extensions = []
    if packet.haslayer(TLS):
        tls_layers = packet[TLS]
        if tls_layers.haslayer(TLSServerHello):  # Check if TLS layer contains Client Hello
            extensions_field = tls_layers[TLSServerHello].ext
            if extensions_field:
                for ext in extensions_field:
                    extensions.append(ext.type)

    extensions = remove_reserved_grease_values(extensions)
    return extensions

# Get supported groups
def get_supported_groups(packet):
    supported_groups = []
    if packet.haslayer(TLS):
        tls_layers = packet[TLS]
        if tls_layers.haslayer(TLS_Ext_SupportedGroups):  # Check if TLS layer contains Supported Groups extension
            supported_groups_field = tls_layers[TLS_Ext_SupportedGroups].groups
            if supported_groups_field:
                for group in supported_groups_field:
                    supported_groups.append(group)

    supported_groups = remove_reserved_grease_values(supported_groups)                
    return supported_groups

def get_supported_versions_CH(packet):
    supported_versions = []
    if packet.haslayer(TLS):
        tls_layers = packet[TLS]
        if tls_layers.haslayer(TLS_Ext_SupportedVersion_CH):
            supported_versions_field = tls_layers[TLS_Ext_SupportedVersion_CH].versions
            if supported_versions_field:
                for version in supported_versions_field:
                    
                    supported_versions.append(version)

    supported_versions = remove_reserved_grease_values(supported_versions)                
    return supported_versions

def get_supported_version_SH(packet):
    if packet.haslayer(TLS):
        tls_layers = packet[TLS]
        if tls_layers.haslayer(TLS_Ext_SupportedVersion_SH):
            supported_versions_field = tls_layers[TLS_Ext_SupportedVersion_SH].version
            if supported_versions_field:
                return supported_versions_field
                              
    return None

def get_signature_algorithms(packet):
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

# Get EC point formats
def get_ec_point_formats(packet):
    ec_point_formats = []
    if packet.haslayer(TLS):
        tls_layers = packet[TLS]
        if tls_layers.haslayer(TLS_Ext_SupportedPointFormat):  # Check if TLS layer contains EC Point Formats extension
            ec_point_formats_field = tls_layers[TLS_Ext_SupportedPointFormat].ecpl
            if ec_point_formats_field:
                for format_code in ec_point_formats_field:
                    ec_point_formats.append(format_code)

    return ec_point_formats

# Get SNI
def get_sni(packet):
    sni = None
    if packet.haslayer(TLS):
        tls_layers = packet[TLS]
        if tls_layers.haslayer(TLS_Ext_ServerName):  # Check if TLS layer contains Client Hello
            sni_field = tls_layers[TLS_Ext_ServerName].servernames
            if sni_field:
                sni = sni_field[0].servername.decode()  # Decode SNI to string
    return sni


def check_useless_domain_name(packet):
    # row from : https://github.com/hsouna/tls-servername/blob/main/get_servername_from_tls.py
    sni = get_sni(packet)

    for black_list_file in black_list_files:
        with open(os.path.join(script_dir, black_list_file), "r") as file:
            for line in file:
                if sni.strip().lower() == line.strip().lower():
                    return True
        
    return False


def is_in_black_list(sni, black_list):
    return sni in black_list


def remove_adds (res_array):

    filtered = []

    for black_list_file in black_list_files:
        with open(os.path.join(script_dir, black_list_file), "r") as file:
            domain_names = file.read().splitlines()

            filtered = [res for res in res_array if not is_in_black_list(res["sni"], domain_names)]
                        
    return filtered

def add_to_string(full_string, items):
    index = 0
    for item in items:
        full_string = full_string + str(item)
        if index != len(items) - 1:
            full_string = full_string + "-"
        index += 1
    return full_string

def create_JA4_hash(packet, ciphers, extensions, sni):
    tls_version = "00"
    tls_versions = get_supported_versions_CH(packet)
    if(tls_versions):
        tls_version = process_version(tls_versions[0])
    
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

    #print(cipher_in.encode())
    #print(ext_in.encode())

    ja4_b = hashlib.sha256(cipher_in.encode()).hexdigest()[0:12]
    ja4_c = hashlib.sha256(ext_in.encode()).hexdigest()[0:12]

    return ja4_a+"_"+ja4_b+"_"+ja4_c

def create_JA4S_hash(packet, ciphers, extensions):
    tls_version = process_version(get_supported_version_SH(packet))
    ext_cnt = format(len(extensions), "02d")

    ja4s_a = "t"+tls_version+ext_cnt+get_alpn(packet)
    ja4s_b = format(ciphers, "04X").lower()

    hex_extensions = [format(extension, "04X").lower() for extension in extensions]
    ext_in = ','.join(str(e) for e in hex_extensions)
    
    ja4s_c = hashlib.sha256(ext_in.encode()).hexdigest()[0:12]

    return ja4s_a+"_"+ja4s_b+"_"+ja4s_c

def process_protocol():
    pass

def process_version(version):
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
    full_string = "" + str(version) + ","
    full_string = add_to_string(full_string, ciphers) + ","
    full_string = add_to_string(full_string, extensions) + ","
    full_string = add_to_string(full_string, supported_groups) + ","
    full_string = add_to_string(full_string, point_format)
    return full_string

def create_JA3S_string(version, ciphers, extensions):
    full_string = "" + str(version) + ","
    full_string = full_string + str(ciphers) + ","
    full_string = add_to_string(full_string, extensions)
    return full_string


def create_hash(hash_string):
    result = hashlib.md5(hash_string.encode())
    return result.hexdigest()

if __name__ == '__main__':
    load_layer('tls')

    scapy_cap = rdpcap(sys.argv[1])

    results = {}

    packet_count = 1
    for packet in scapy_cap:

        if packet.haslayer(TLS) and packet.haslayer(TCP):

            ip_src = packet[IP].src
            ip_dest = packet[IP].dst
            port_src = packet[TCP].sport
            port_dest = packet[TCP].dport

            tls_layers = packet[TLS]

            supported_groups = get_supported_groups(packet)
            point_format = get_ec_point_formats(packet)

            if tls_layers.haslayer(TLSClientHello):
                
                version = get_client_hello_version(packet)
                extensions = get_client_hello_extensions(packet)
                ciphers = process_JA3_ciphers(packet)
                sni = get_sni(packet)

                full_string = create_JA3_string(version, ciphers, extensions, supported_groups, point_format)
                ja3_hash = create_hash(full_string)

                ja4_hash = create_JA4_hash(packet, ciphers, extensions, sni)

                key = (ip_src, port_src, ip_dest, port_dest)

                if key not in results:
                    results[key] = {
                        "ip_src" : ip_src, "port_src":port_src, 
                        "ip_dest":ip_dest, "port_dest":port_dest, 
                        "ja3_hash": ja3_hash, "sni": sni, "ja3s_hash": None,
                        "ja4_hash": ja4_hash, "ja4s_hash": None
                    }
                else:
                    results[key]["ja3_hash"] = ja3_hash
                    results[key]["sni"] = sni
                    results[key]["ja4_hash"] = ja4_hash

            if tls_layers.haslayer(TLSServerHello):
                version = get_server_hello_version(packet)
                extensions = get_server_hello_extensions(packet)
                ciphers = process_JA3S_ciphers(packet)

                full_string = create_JA3S_string(version, ciphers, extensions)
                ja3s_hash = create_hash(full_string)

                ja4s_hash = create_JA4S_hash(packet, ciphers, extensions)

                key = (ip_dest, port_dest, ip_src, port_src)

                if key not in results:
                    results[key] = {
                        "ip_src" : ip_src, "port_src":port_src, 
                        "ip_dest":ip_dest, "port_dest":port_dest, 
                        "ja3_hash": None, "sni": None, "ja3s_hash": ja3s_hash,
                        "ja4_hash": None, "ja4s_hash": ja4s_hash
                    }
                else:
                    results[key]["ja3s_hash"] = ja3s_hash
                    results[key]["ja4s_hash"] = ja4s_hash

        packet_count += 1

    array_results = []

    for key in results:
        obj = {
            "ja3_hash": results[key]["ja3_hash"],
            "sni": results[key]["sni"],
            "ja3s_hash": results[key]["ja3s_hash"],
            "ja4_hash": results[key]["ja4_hash"],
            "ja4s_hash": results[key]["ja4s_hash"],
        }
        array_results.append(obj)


    array_results = remove_adds(array_results)

    # Remove duplicities
    tuple_of_results = [tuple(sorted(res.items())) for res in array_results]
    unique_tuples = set(tuple_of_results)
    array_results = [dict(tp) for tp in unique_tuples]

    print(json.dumps(array_results))
