import sys

from scapy.all import *
from scapy.layers.tls.record import TLS
import hashlib

tls_supported_groups = []
tls_ecf = []

JA3_strings = []
JA3_hashes = []


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


def create_ja3_string(version, ciphers, extensions):
    full_string = "" + str(version) + ","
    full_string = add_to_string(full_string, ciphers) + ","
    full_string = add_to_string(full_string, extensions) + ","
    full_string = add_to_string(full_string, tls_supported_groups) + ","
    full_string = add_to_string(full_string, tls_ecf)
    return full_string


def create_md5_hash(ja3_string):
    result = hashlib.md5(ja3_string.encode())
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
    packet_count = 1
    for packet in scapy_cap:
        #print(packet[TLS].show())
        version = process_version(packet[TLS].msg[0])
        ciphers = process_ciphers(packet[TLS].msg[0])
        extensions = process_extensions(packet[TLS].msg[0])

        full_string = create_ja3_string(version, ciphers, extensions)
        ja3_hash = create_md5_hash(full_string)

        JA3_strings.append(full_string)
        JA3_hashes.append(ja3_hash)

        print_values(packet_count, version, ciphers, extensions, full_string, ja3_hash)
        packet_count += 1
