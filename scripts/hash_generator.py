import sys

import pyshark as pyshark
from scapy.all import *
from scapy.layers.tls.record import TLS
from scapy.layers.tls.extensions import TLS_Ext_SupportedGroups
from scapy.layers.tls.extensions import TLS_Ext_SupportedPointFormat
from scapy.layers.tls.extensions import TLS_Ext_ServerName

from scapy.layers.tls.handshake import TLSClientHello
from scapy.layers.tls.handshake import TLSServerHello

import hashlib
import os
import json

import warnings
warnings.filterwarnings('ignore')

BLACK_LIST_FILE = 'domain_black_list.txt'

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
    if (sni):
        if sni in open(os.path.join(script_dir, BLACK_LIST_FILE), 'r').read():
            return True
        
    return False


def add_to_string(full_string, items):
    index = 0
    for item in items:
        full_string = full_string + str(item)
        if index != len(items) - 1:
            full_string = full_string + "-"
        index += 1
    return full_string


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
    hash_type = sys.argv[2]

    packet_count = 1
    for packet in scapy_cap:

        # check domains for JA3 only  
        if hash_type == 'JA3':
            if check_useless_domain_name(packet):
                continue

        supported_groups = get_supported_groups(packet)
        point_format = get_ec_point_formats(packet)
        sni = get_sni(packet)

        if(hash_type == "JA3"):
            if packet.haslayer(TLS):
                tls_layers = packet[TLS]
                if tls_layers.haslayer(TLSClientHello):
                    
                    version = get_client_hello_version(packet)
                    extensions = get_client_hello_extensions(packet)
                    ciphers = process_JA3_ciphers(packet)

                    full_string = create_JA3_string(version, ciphers, extensions, supported_groups, point_format)
                    final_hash = create_hash(full_string)

                    new_hash = { 'hash' : final_hash, 'sni' : sni }
                    hashes.append(new_hash)

        elif(hash_type == "JA3S"):
            if packet.haslayer(TLS):
                tls_layers = packet[TLS]
                if tls_layers.haslayer(TLSServerHello):

                    version = get_server_hello_version(packet)
                    extensions = get_server_hello_extensions(packet)
                    ciphers = process_JA3S_ciphers(packet)

                    full_string = create_JA3S_string(version, ciphers, extensions)
                    final_hash = create_hash(full_string)

                    new_hash = { 'hash' : final_hash, 'sni' : sni }

                    hashes.append(new_hash)

        #packet.show()

        #print("################")
        #print(f"IP SRC : {packet[IP].src}")
        #print(f"IP DST : {packet[IP].dst}")
        #print(f"TLS Version : {version}")
        #print(f"TLS Ciphers : {ciphers}")
        #print(f"TLS Extensions : {extensions}")
        #print(f"TLS Supported groups : {supported_groups}")
        #print(f"TLS EC Point format : {point_format}")
        #print(f"JA3S Full string : {full_string}")
        #print(f"JA3S Hash : {final_hash}")
        #print(f"SNI : {get_sni_client_hello(packet)}")
        #print("################")

        packet_count += 1
    
    hash_dict = {}

    for hash_item in hashes:
        hash_value = hash_item["hash"]
        if hash_value not in hash_dict:
            hash_dict[hash_value] = hash_item

    final_hash_list = list(hash_dict.values())

    print(json.dumps(final_hash_list))
