import sys

import pyshark as pyshark
from scapy.all import *
from scapy.layers.tls.record import TLS
import hashlib
import os

tls_supported_groups = []
tls_ecf = []

hash_strings = []
hashes = []


def process_ciphers(message):
    return message.ciphers


def process_version(message):
    return message.version


def process_extensions(message):
    ext_types = []
    for extension in message.ext:
        ext_types.append(extension.type)
        process_supported_groups(extension)
        process_point_format(extension)
    return ext_types


def process_supported_groups(extension):
    if extension.type == 10:
        global tls_supported_groups
        tls_supported_groups = extension.groups


def process_point_format(extension):
    if extension.type == 11:
        global tls_ecf
        tls_ecf = extension.ecpl


def add_to_string(full_string, items):
    index = 0
    for item in items:
        full_string = full_string + str(item)
        if index != len(items) - 1:
            full_string = full_string + "-"
        index += 1
    return full_string


def create_JA3_string(version, ciphers, extensions):
    full_string = "" + str(version) + ","
    full_string = add_to_string(full_string, ciphers) + ","
    full_string = add_to_string(full_string, extensions) + ","
    full_string = add_to_string(full_string, tls_supported_groups) + ","
    full_string = add_to_string(full_string, tls_ecf)
    return full_string

def create_JA3S_string(version, ciphers, extensions):
    full_string = "" + str(version) + ","
    full_string = add_to_string(full_string, ciphers) + ","
    full_string = add_to_string(full_string, extensions)
    return full_string


def create_hash(hash_string):
    result = hashlib.md5(hash_string.encode())
    return result.hexdigest()


def print_values(packet_id, version, ciphers, extensions, full_string, ja3_hash):
    print()
    print(f'Packet {packet_id}')
    print(f'TLS version : {version}')
    print(f'TLS ciphers : {ciphers}')
    print(f'TLS extensions : {extensions}')
    print(f'TLS supported groups : {tls_supported_groups}')
    print(f'TLS ec format : {tls_ecf}')
    print(f'Full string: {full_string}')
    print(f'JA3 hash: {ja3_hash}')



if __name__ == '__main__':
    load_layer('tls')

    scapy_cap = rdpcap(sys.argv[1])
    hash_type = sys.argv[2]

    packet_count = 1
    for packet in scapy_cap:
        version = process_version(packet[TLS].msg[0])
        ciphers = process_ciphers(packet[TLS].msg[0])
        extensions = process_extensions(packet[TLS].msg[0])

        if(hash_type == "JA3"):
            full_string = create_JA3_string(version, ciphers, extensions)
            final_hash = create_hash(full_string)
        elif(hash_type == "JA3S"):
            full_string = create_JA3S_string(version, ciphers, extensions)
            final_hash = create_hash(full_string)

        hash_strings.append(full_string)
        hashes.append(final_hash)

        #print_values(packet_count, version, ciphers, extensions, full_string, ja3_hash)
        packet_count += 1

    final_hash_list = list(dict.fromkeys(hashes))

    #print()
    #print('JA3 hash list :')
    print(final_hash_list)
    #print()
