import sys

import pyshark as pyshark
from scapy.all import *
from scapy.layers.tls.record import TLS
import hashlib
import os

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

def check_useless_domain_name(packet):
    # row from : https://github.com/hsouna/tls-servername/blob/main/get_servername_from_tls.py
    if (packet['TLS_Ext_ServerName'].servernames):
        domain_name = packet['TLS_Ext_ServerName'].servernames[0].servername.decode("utf-8")

        if domain_name in open(os.path.join(script_dir, BLACK_LIST_FILE), 'r').read():
            return True
        
    return False

def remove_reserved_grease_values(array):
    return [item for item in array if item not in RESERVED_GREASE_VALUES]

def process_ciphers(message):
    if(hash_type == 'JA3'):
        return remove_reserved_grease_values(message.ciphers)
    elif(hash_type == 'JA3S'):
        return message.cipher


def process_version(message):
    return message.version


def process_extensions(message):
    ext_types = []
    supported_groups = []
    point_format = []

    for extension in message.ext:
        ext_types.append(extension.type)
        if extension.type == 10:
            supported_groups = extension.groups
        if extension.type == 11:
            point_format = extension.ecpl

    ext_types = remove_reserved_grease_values(ext_types)
    supported_groups = remove_reserved_grease_values(supported_groups)

    return ext_types, supported_groups, point_format


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
            if check_useless_domain_name(packet[TLS]):
                continue
        
        version = process_version(packet[TLS].msg[0])
        ciphers = process_ciphers(packet[TLS].msg[0])
        extensions, supported_groups, point_format = process_extensions(packet[TLS].msg[0])

        if(hash_type == "JA3"):
            full_string = create_JA3_string(version, ciphers, extensions, supported_groups, point_format)
            final_hash = create_hash(full_string)
        elif(hash_type == "JA3S"):
            full_string = create_JA3S_string(version, ciphers, extensions)
            final_hash = create_hash(full_string)

        hash_strings.append(full_string)
        hashes.append(final_hash)

        packet_count += 1

    final_hash_list = list(dict.fromkeys(hashes))

    print(final_hash_list)
