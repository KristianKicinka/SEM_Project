import sys

import pyshark as pyshark
from scapy.all import *
from scapy.layers.tls.record import TLS
import hashlib
import os

BLACK_LIST_FILE = './domain_black_list.txt'


if __name__ == '__main__':
    load_layer('tls')

    scapy_cap = rdpcap(sys.argv[1])
             
    for packet in scapy_cap:
        
        # row from : https://github.com/hsouna/tls-servername/blob/main/get_servername_from_tls.py
        domain_name = packet['TLS']['TLS_Ext_ServerName'].servernames[0].servername.decode("utf-8")

        if domain_name in open(BLACK_LIST_FILE).read():
            continue

        print(domain_name)